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
            $prompt = "You are a financial assistant. User says: \"$body\". Extract: name (short description), amount (number only, use dot for decimals), category (best match from: $categories, or 'Outros'), due_date (YYYY-MM-DD, default to today if not explicit). Return JSON only.";

            Log::info("WhatsApp processing for user {$user->id}: $body");

            $result = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => "Current date: " . now()->toDateString() . ". You always output valid JSON."],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.1,
            ]);

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

            return response()->json(['status' => 'success', 'bill_id' => $bill->id]);

        } catch (\Exception $e) {
            Log::error("WhatsApp Error: " . $e->getMessage());
            // Send exact error to user for debugging in production
            $errorMsg = substr($e->getMessage(), 0, 200);
            $this->sendWhatsAppMessage($from, "Erro ao processar: $errorMsg");
            if (!$sid || !$token || !$waNumber) {
                Log::error("Twilio credentials missing");
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
