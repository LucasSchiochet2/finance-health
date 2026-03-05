<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\User;
use App\Models\CategoryBill;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    public function handle(Request $request)
    {
        $from = $request->input('From');
        $body = $request->input('Body');

        if (!$from || !$body) {
            return response()->json(['status' => 'invalid_request'], 400);
        }

        // Clean phone number (remove 'whatsapp:')
        $phone = str_replace('whatsapp:', '', $from);

        // Try to match phone with or without +
        $user = User::where('phone', $phone)
                    ->orWhere('phone', '+'.$phone)
                    ->orWhere('phone', ltrim($phone, '+'))
                    ->first();

        if (!$user) {
            Log::warning("WhatsApp: User not found for phone $phone");
            $this->sendWhatsAppMessage($from, "Olá! Não encontrei seu número ($phone) cadastrado. Por favor, adicione-o no seu perfil.");
            return response()->json(['status' => 'user_not_found']);
        }

        try {
            $apiKey = config('openai.api_key');
            if (!$apiKey) {
                // Determine if it might be in env directly (in case config cache missed it)
                $apiKey = env('OPENAI_API_KEY');
                if (!$apiKey) {
                   throw new \Exception("OpenAI API Key not configured.");
                }
            }

            $categories = CategoryBill::pluck('name')->implode(', ');
            $prompt = "Você é um assistente financeiro especializado em extração de dados.
                        Analise o texto do usuário e extraia os detalhes da despesa para um formato JSON estrito.
                        Texto do Usuário: \"$body\"
                        Data de Hoje: \"$current_date\"
                        Categorias Disponíveis: $categories
                        Catões do Usario: {$user->cards->pluck('name')->implode(', ')}
                        Extraia e mapeie para as seguintes chaves JSON:
                        - \"name\": Título curto e claro da despesa.
                        - \"description\": Detalhes adicionais mencionados (ou null).
                        - \"amount\": Apenas número (float), use ponto para decimais (ex: 150.50).
                        - \"due_date\": Formato YYYY-MM-DD. Calcule dias relativos (\"amanhã\", \"dia 15\") com base na Data de Hoje. Use a Data de Hoje se nenhuma for sugerida.
                        - \"category\": A que melhor se encaixar nas Categorias Disponíveis, ou \"Outros\".
                        - \"paid\": true se o texto disser que já foi pago, false caso contrário.
                        - \"payment_method\": Método citado (ex: \"pix\", \"credit_card\", \"boleto\") ou null.
                        - \"is_recurring\": true se for uma conta fixa mensal/recorrente, false caso contrário.
                        - \"is_installment\": true se for uma compra parcelada (ex: \"em 10x\").
                        - \"installment_count\": Número total de parcelas (integer) ou null.
                        - \"credit_card_id\": ID do cartão mencionado, se aplicável (verifique os cartões do usuário).
                        REGRAS OBRIGATÓRIAS:
                        1. Retorne APENAS o JSON válido.
                        2. NÃO use formatação markdown (sem ```json).
                        3. Não adicione nenhuma palavra antes ou depois do JSON.";

            Log::info("WhatsApp processing for user {$user->id}: $body");

            $result = retry(3, function () use ($prompt) {
                return OpenAI::chat()->create([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => "Current date: " . now()->toDateString() . ". You always output valid JSON."],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.1,
                ]);
            }, 2000);

            Log::info("WhatsApp: OpenAI response: " . json_encode($result->choices[0]->message->content));

            $responseContent = $result->choices[0]->message->content;

            // Clean markdown if present
            $responseContent = str_replace(['```json', '```'], '', $responseContent);

            $data = json_decode($responseContent, true);

            if (!$data || !isset($data['amount'])) {
                Log::error("WhatsApp Parse Fail: $responseContent");
                $this->sendWhatsAppMessage($from, "Não entendi. Tente: 'Mercado 100,50' ou 'Gasolina 50'.");
                return response()->json(['status' => 'parsed_error']);
            }

            // Sanitize amount (replace comma with dot if AI failed to do so)
            $amount = str_replace(',', '.', $data['amount']);
            // Remove non-numeric chars except dot
            $amount = preg_replace('/[^0-9.]/', '', $amount);

            // Find or create category
            $categoryName = $data['category'] ?? 'Outros';
            $category = CategoryBill::firstOrCreate(['name' => $categoryName]);

            $bill = Bill::create([
                'user_id' => $user->id,
                'name' => $data['name'] ?? 'Compra WhatsApp',
                'amount' => $amount,
                'category_bill_id' => $category->id,
                'due_date' => $data['due_date'] ?? now()->toDateString(),
                'description' => "Original: $body",
                'is_recurring' => 0,
                'paid' => 0,
            ]);

            $message = "✅ *Compra registrada!* \n🏷️ {$bill->name}\n💰 R$ " . number_format((float)$bill->amount, 2, ',', '.') . "\n📂 {$category->name}\n📅 " . \Carbon\Carbon::parse($bill->due_date)->format('d/m/Y');

            $this->sendWhatsAppMessage($from, $message);
            Log::info("WhatsApp: Bill created and message queued for $from");

            return response()->json(['status' => 'success', 'bill_id' => $bill->id]);

        } catch (\Exception $e) {
            Log::error("WhatsApp Error: " . $e->getMessage());
            $errorMsg = $e->getMessage();

            if (str_contains(strtolower($errorMsg), 'rate limit')) {
                $this->sendWhatsAppMessage($from, "⚠️ O limite de requisições foi atingido. Por favor, tente novamente em alguns instantes.");
            } elseif (str_contains(strtolower($errorMsg), 'quota')) {
                 $this->sendWhatsAppMessage($from, "⚠️ Erro de cota da API. Verifique os créditos da OpenAI.");
            } else {
                 $this->sendWhatsAppMessage($from, "Erro ao processar: " . substr($errorMsg, 0, 200));
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

            Log::info("WhatsApp: Attempting to send message to $to from $waNumber");

            if (!$sid || !$token || !$waNumber) {
                Log::error("Twilio credentials missing. SID: " . ($sid ? 'Set' : 'Missing') . ", Number: $waNumber");
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
