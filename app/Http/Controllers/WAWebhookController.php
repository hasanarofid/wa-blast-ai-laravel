<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ChatAIService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WAWebhookController extends Controller
{
    public function handle(Request $request, ChatAIService $ai)
    {
        $payload = $request->all();

        // Ganti sesuai format dari WA Gateway kamu
        $from = $payload['from'] ?? null;
        $message = $payload['message'] ?? null;

        if (!$from || !$message) {
            Log::warning('Webhook data invalid', $payload);
            return response()->json(['status' => 'invalid'], 400);
        }

        // Panggil AI
        $response = $ai->ask($message);

        // Kirim ke WA Gateway
        $this->sendToWhatsApp($from, $response);

        return response()->json(['status' => 'ok']);
    }

    private function sendToWhatsApp($to, $message)
    {
        try {
            Http::post(env('WA_GATEWAY_SEND_URL'), [
                'to' => $to,
                'message' => $message,
                'token' => env('WA_GATEWAY_TOKEN'),
            ]);
        } catch (\Exception $e) {
            Log::error('WA Send Failed', ['message' => $e->getMessage()]);
        }
    }
}
