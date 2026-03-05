<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\User;
use App\Models\CategoryBill;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
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
            
            // Busca categorias e monta lista de cartões com IDs (ex: "ID 1: Nubank, ID 2: Itaú")
            $categories = CategoryBill::pluck('name')->implode(', ');
            $userCards = $user->cards->map(fn($card) => "ID {$card->id}: {$card->name}")->implode(' | ');

            $prompt = "Você é um assistente financeiro especializado em extração de dados.
            Analise o texto do usuário e extraia os detalhes da despesa para um formato JSON estrito.

            Texto do Usuário: \"$body\"
            Data de Hoje: \"$currentDate\"
            Categorias Disponíveis: $categories
            Cartões do Usuário: $userCards

            Extraia e mapeie para as seguintes chaves JSON:
            - \"name\": Título curto e claro da despesa.
            - \"description\": Detalhes adicionais mencionados (ou null).
            - \"amount\": Apenas número (float), use ponto para decimais (ex: 150.50).
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
                    'temperature' => 0.1, // Temperatura baixa para respostas mais determinísticas/precisas
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

            // Higienização do valor monetário
            $amount = str_replace(',', '.', (string)$data['amount']);
            $amount = preg_replace('/[^0-9.]/', '', $amount);

            // Encontra ou cria a categoria associada
            $categoryName = $data['category'] ?? 'Outros';
            $category = CategoryBill::firstOrCreate(['name' => $categoryName]);

            // Cria o registro da despesa preenchendo o máximo de dados que a IA extraiu
            $bill = Bill::create([
                'user_id'           => $user->id,
                'name'              => $data['name'] ?? 'Despesa WhatsApp',
                'description'       => $data['description'] ?? "Registrado via WhatsApp: $body",
                'amount'            => $amount,
                'category_bill_id'  => $category->id,
                'due_date'          => $data['due_date'] ?? $currentDate,
                'is_recurring'      => $data['is_recurring'] ?? false,
                'paid'              => $data['paid'] ?? false,
                'payment_method'    => $data['payment_method'] ?? null,
                'is_installment'    => $data['is_installment'] ?? false,
                'installment_count' => $data['installment_count'] ?? null,
                'credit_card_id'    => $data['credit_card_id'] ?? null,
            ]);

            // Monta mensagem de confirmação enriquecida
            $statusPago = $bill->paid ? "✅ (Pago)" : "⏳ (Pendente)";
            $parcelas = $bill->is_installment ? "\n🔢 Parcelas: {$bill->installment_count}x" : "";
            
            $message = "✅ *Despesa registrada!*\n"
                     . "🏷️ {$bill->name}\n"
                     . "💰 R$ " . number_format((float)$bill->amount, 2, ',', '.') . " $statusPago\n"
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
