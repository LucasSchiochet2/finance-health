<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\User;
use App\Models\CategoryBill;
use App\Models\Card; // Importação do model Card
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // Importação para gerar o group_id das parcelas
use Carbon\Carbon;

class WhatsAppController extends Controller
{
    public function handle(Request $request)
    {
        $from = $request->input('From');
        $body = $request->input('Body');

        if (!$from || !$body) {
            return response()->json(['status' => 'invalid_request'], 400);
        }

        // Limpa o número de telefone (remove 'whatsapp:')
        $phone = str_replace('whatsapp:', '', $from);

        // Tenta encontrar o usuário de várias formas
        $user = User::where('phone', $phone)
                    ->orWhere('phone', '+' . $phone)
                    ->orWhere('phone', ltrim($phone, '+'))
                    ->first();

        if (!$user) {
            Log::warning("WhatsApp: User not found for phone $phone");
            $this->sendWhatsAppMessage($from, "Olá! Não encontrei seu número ($phone) cadastrado. Por favor, adicione-o no seu perfil.");
            return response()->json(['status' => 'user_not_found']);
        }

        try {
            $apiKey = config('openai.api_key') ?? env('OPENAI_API_KEY');
            if (!$apiKey) {
                throw new \Exception("OpenAI API Key not configured.");
            }

            // Define a data atual para injeção no prompt
            $currentDate = now()->toDateString();
            
            // Busca categorias
            $categories = CategoryBill::pluck('name')->implode(', ');

            // Busca cartões direto do model Card com where
            $cards = Card::where('user_id', $user->id)->get();
            $userCards = $cards->map(fn($card) => "ID {$card->id}: {$card->name}")->implode(' | ');

            if (empty($userCards)) {
                $userCards = "Nenhum cartão cadastrado.";
            }

            $prompt = "Você é um assistente financeiro especializado em extração de dados.
            Analise o texto do usuário e extraia os detalhes da despesa para um formato JSON estrito.

            Texto do Usuário: \"$body\"
            Data de Hoje: \"$currentDate\"
            Categorias Disponíveis: $categories
            Cartões do Usuário: $userCards

            Extraia e mapeie para as seguintes chaves JSON:
            - \"name\": Título curto e claro da despesa.
            - \"description\": Detalhes adicionais mencionados (ou null).
            - \"amount\": Valor TOTAL da compra. Apenas número (float), use ponto para decimais (ex: 150.50).
            - \"due_date\": Formato YYYY-MM-DD. Calcule dias relativos com base na Data de Hoje. Use a Data de Hoje se nenhuma for sugerida.
            - \"category\": A que melhor se encaixar nas Categorias Disponíveis, ou \"Outros\".
            - \"paid\": true se o texto disser que já foi pago, false caso contrário.
            - \"payment_method\": Método citado (ex: \"pix\", \"credit_card\", \"boleto\", \"dinheiro\") ou null.
            - \"is_recurring\": true se for uma conta fixa mensal/recorrente, false caso contrário.
            - \"is_installment\": true se for uma compra parcelada (ex: \"em 10x\").
            - \"installment_count\": Número total de parcelas (integer) ou null.
            - \"credit_card_id\": O ID numérico correspondente ao cartão mencionado na lista acima (ou null se não for cartão de crédito).

            REGRAS OBRIGATÓRIAS:
            1. Retorne APENAS o JSON válido.
            2. NÃO use formatação markdown (sem ```json).
            3. Não adicione nenhuma palavra antes ou depois do JSON.";

            Log::info("WhatsApp processing for user {$user->id}: $body");

            $result = retry(3, function () use ($prompt, $currentDate) {
                return OpenAI::chat()->create([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => "Current date: $currentDate. You always output valid strict JSON only."],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.1,
                ]);
            }, 2000);

            $responseContent = trim($result->choices[0]->message->content);

            // Limpeza de possíveis formatações markdown residuais
            $responseContent = str_replace(['```json', '```'], '', $responseContent);

            $data = json_decode($responseContent, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['amount'])) {
                Log::error("WhatsApp Parse Fail: $responseContent");
                $this->sendWhatsAppMessage($from, "Não consegui entender completamente. Tente algo como: 'Comprei mercado por 100,50 no pix' ou 'Gasolina 50 reais cartão Nubank'.");
                return response()->json(['status' => 'parsed_error']);
            }

            // Higienização do valor monetário TOTAL
            $amountTotal = str_replace(',', '.', (string)$data['amount']);
            $amountTotal = (float) preg_replace('/[^0-9.]/', '', $amountTotal);

            // Encontra ou cria a categoria associada
            $categoryName = $data['category'] ?? 'Outros';
            $category = CategoryBill::firstOrCreate(['name' => $categoryName]);

            $isInstallment = $data['is_installment'] ?? false;
            $installmentCount = (int) ($data['installment_count'] ?? 1);
            $bill = null; // Guardará a primeira parcela para retornar na mensagem
            $amountForMessage = $amountTotal; // O que vai aparecer no WhatsApp

            // Lógica de Criação: Parcelado vs Normal
            if ($isInstallment && $installmentCount > 1) {
                $groupId = (string) Str::uuid(); // Agrupa todas as parcelas
                $amountPerInstallment = round($amountTotal / $installmentCount, 2);
                $amountForMessage = $amountPerInstallment;

                for ($i = 1; $i <= $installmentCount; $i++) {
                    // Adiciona X meses à data original
                    $dueDate = Carbon::parse($data['due_date'] ?? $currentDate)->addMonths($i - 1)->toDateString();

                    $createdBill = Bill::create([
                        'user_id'           => $user->id,
                        'name'              => ($data['name'] ?? 'Despesa WhatsApp') . " ($i/$installmentCount)",
                        'description'       => $data['description'] ?? "Registrado via WhatsApp: $body",
                        'amount'            => $amountPerInstallment,
                        'category_bill_id'  => $category->id,
                        'due_date'          => $dueDate,
                        'is_recurring'      => $data['is_recurring'] ?? false,
                        'paid'              => ($i === 1) ? ($data['paid'] ?? false) : false, // Só a primeira pode estar paga na criação
                        'payment_method'    => $data['payment_method'] ?? null,
                        'is_installment'    => true,
                        'installment_count' => $installmentCount,
                        'installment_current'=> $i,
                        'group_id'          => $groupId,
                        'credit_card_id'    => $data['credit_card_id'] ?? null,
                    ]);

                    if ($i === 1) {
                        $bill = $createdBill;
                    }
                }
            } else {
                // Registro Normal (À vista / Recorrente não parcelado)
                $bill = Bill::create([
                    'user_id'           => $user->id,
                    'name'              => $data['name'] ?? 'Despesa WhatsApp',
                    'description'       => $data['description'] ?? "Registrado via WhatsApp: $body",
                    'amount'            => $amountTotal,
                    'category_bill_id'  => $category->id,
                    'due_date'          => $data['due_date'] ?? $currentDate,
                    'is_recurring'      => $data['is_recurring'] ?? false,
                    'paid'              => $data['paid'] ?? false,
                    'payment_method'    => $data['payment_method'] ?? null,
                    'is_installment'    => false,
                    'installment_count' => null,
                    'installment_current'=> null,
                    'group_id'          => null,
                    'credit_card_id'    => $data['credit_card_id'] ?? null,
                ]);
            }

            // Monta mensagem de confirmação enriquecida
            $statusPago = $bill->paid ? "✅ (Pago)" : "⏳ (Pendente)";
            $parcelas = ($isInstallment && $installmentCount > 1) 
                ? "\n🔢 Parcelado em: {$installmentCount}x de R$ " . number_format($amountForMessage, 2, ',', '.') . "\n💰 Total: R$ " . number_format($amountTotal, 2, ',', '.') 
                : "";
            
            $message = "✅ *Despesa registrada!*\n"
                     . "🏷️ {$bill->name}\n"
                     . "💳 R$ " . number_format((float) ($isInstallment ? $amountForMessage : $amountTotal), 2, ',', '.') . " $statusPago\n"
                     . "📂 {$category->name}\n"
                     . "📅 " . Carbon::parse($bill->due_date)->format('d/m/Y')
                     . $parcelas;

            $this->sendWhatsAppMessage($from, $message);
            Log::info("WhatsApp: Bill created ({$bill->id}) and message queued for $from");

            return response()->json(['status' => 'success', 'bill_id' => $bill->id]);

        } catch (\Exception $e) {
            Log::error("WhatsApp Error: " . $e->getMessage());
            $errorMsg = $e->getMessage();

            if (str_contains(strtolower($errorMsg), 'rate limit')) {
                $this->sendWhatsAppMessage($from, "⚠️ O limite do sistema foi atingido. Por favor, tente novamente em alguns instantes.");
            } elseif (str_contains(strtolower($errorMsg), 'quota')) {
                $this->sendWhatsAppMessage($from, "⚠️ Erro de comunicação com a inteligência artificial. Entre em contato com o suporte.");
            } else {
                $this->sendWhatsAppMessage($from, "Ocorreu um erro ao processar sua despesa. Tente novamente mais tarde.");
            }

            return response()->json(['status' => 'error', 'message' => $errorMsg], 500);
        }
    }

    private function sendWhatsAppMessage($to, $message)
    {
        try {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $waNumber = config('services.twilio.whatsapp_number');

            if (!$sid || !$token || !$waNumber) {
                Log::error("Twilio credentials missing.");
                return;
            }

            $twilio = new Client($sid, $token);

            $twilio->messages->create(
                $to,
                [
                    'from' => $waNumber,
                    'body' => $message
                ]
            );
        } catch (\Exception $e) {
            Log::error("Twilio Send Error: " . $e->getMessage());
        }
    }
}
