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

        // Find user
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            // Optional: send a message saying user not found,
            // but usually we don't want to spam unregistered numbers if it's a wrong hit.
            // But since user initiated, it's fine.
            $this->sendWhatsAppMessage($from, "Olá! Não encontrei seu número ($phone) cadastrado no sistema. Por favor, adicione-o no seu perfil.");
            return response()->json(['status' => 'user_not_found']);
        }

        try {
            $categories = CategoryBill::pluck('name')->implode(', ');
            $prompt = "You are a financial assistant. User says: \"$body\". Extract: name (short description), amount (number only), category (best match from: $categories), due_date (YYYY-MM-DD, default to today if not explicit). Return JSON only.";

            $result = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => "Current date: " . now()->toDateString() . ". You generally output JSON."],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.1,
            ]);

            $responseContent = $result->choices[0]->message->content;

            // Clean markdown if present
            $responseContent = str_replace('```json', '', $responseContent);
            $responseContent = str_replace('```', '', $responseContent);

            $data = json_decode($responseContent, true);

            if (!$data || !isset($data['amount'])) {
                $this->sendWhatsAppMessage($from, "Não consegui entender a compra. Tente: 'Gastei 50 no almoço'.");
                return response()->json(['status' => 'parsed_error']);
            }

            // Find or create category
            $categoryName = $data['category'] ?? 'Outros';
            $category = CategoryBill::firstOrCreate(['name' => $categoryName]);

            $bill = Bill::create([
                'user_id' => $user->id,
                'name' => $data['name'] ?? 'Compra via WhatsApp',
                'amount' => $data['amount'],
                'category_bill_id' => $category->id,
                'due_date' => $data['due_date'] ?? now()->toDateString(),
                'description' => "Registrado via WhatsApp: $body",
                // Defaults
                'is_recurring' => false,
                'paid' => false,
            ]);

            $message = "✅ Compra ragistrada!\nItem: {$bill->name}\nValor: R$ " . number_format($bill->amount, 2, ',', '.') . "\nCategoria: {$category->name}\nVencimento: " . \Carbon\Carbon::parse($bill->due_date)->format('d/m/Y');

            $this->sendWhatsAppMessage($from, $message);

            return response()->json(['status' => 'success', 'bill_id' => $bill->id]);

        } catch (\Exception $e) {
            Log::error("WhatsApp Error: " . $e->getMessage());
            $this->sendWhatsAppMessage($from, "Ocorreu um erro ao processar sua mensagem.");
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function sendWhatsAppMessage($to, $message)
    {
        try {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $waNumber = config('services.twilio.whatsapp_number');

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
