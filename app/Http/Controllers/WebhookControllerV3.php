<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncomingMessage;
use App\Services\ChatAIService;

class WebhookController extends Controller
{
    private function sendReply($to, $message)
    {
        $token = env('ULTRAMSG_TOKEN');
        $instance = env('ULTRAMSG_INSTANCE');

        $url = "https://api.ultramsg.com/$instance/messages/chat";

        $client = new \GuzzleHttp\Client();
        $client->post($url, [
            'form_params' => [
                'token' => $token,
                'to' => $to,
                'body' => $message,
            ]
        ]);
    }

    // v2
    public function handle(Request $request, ChatAIService $chatAI)
    {
        $data = $request->all();

        // Ambil dari nested 'data' jika tersedia
        // $from = $data['from'] ?? $data['data']['from'] ?? $data['data']['author'] ?? null;
        // $text = $data['body'] ?? $data['data']['body'] ?? null;
        // $type = $data['type'] ?? $data['data']['type'] ?? 'text';

        $from = $data['from'] ?? ($data['data']['author'] ?? $data['data']['from'] ?? null);
        $text = $data['body'] ?? ($data['data']['body'] ?? null);
        $type = $data['type'] ?? ($data['data']['type'] ?? 'text');

        // Validasi minimal
        if (!$from || !$text) {
            \Log::warning('Pesan masuk tanpa nomor pengirim atau teks. Data:', $data);
            return response()->json(['status' => 'ignored']);
        }

        \Log::info('Pesan masuk dari: ' . $from . ' | Isi: ' . $text);

        // Simpan log pesan masuk
        IncomingMessage::create([
            'from' => $from,
            'message' => $text,
            'type' => $type,
            'raw' => json_encode($data)
        ]);

        // Dapatkan jawaban dari AI
        try {
            $aiReply = $chatAI->ask($text);
        } catch (\Exception $e) {
            $aiReply = "Maaf, CS AI sedang gangguan. Mohon tunggu atau hubungi admin.";
        }

        // Kirim balasan ke WhatsApp user
        $this->sendReply($from, $aiReply);

        return response()->json(['status' => 'received']);
    }

    // v1 - berhasil
    // public function handle(Request $request, ChatAIService $chatAI)
    // {
    //     $data = $request->all();
    //     // $from = $data['from'] ?? null;
    //     $from = $data['from'] ?? $data['data']['from'] ?? $data['data']['author'] ?? null;
    //     $text = $data['body'] ?? null;
    //     $type = $data['type'] ?? 'text';

    //     if (!$from || !$text) {
    //         \Log::warning('Pesan masuk tanpa nomor pengirim atau teks. Data:', $data);
    //         return response()->json(['status' => 'ignored']);
    //     }

    //     // Simpan log
    //     IncomingMessage::create([
    //         'from' => $from,
    //         'message' => $text,
    //         'type' => $type,
    //         'raw' => json_encode($data)
    //     ]);

    //     // Jawaban dari ChatGPT
    //     try {
    //         $aiReply = $chatAI->ask($text);
    //     } catch (\Exception $e) {
    //         $aiReply = "Maaf, CS AI sedang gangguan. Mohon tunggu atau hubungi admin.";
    //     }

    //     // Kirim balasan ke user WA
    //     $this->sendReply($from, $aiReply);

    //     return response()->json(['status' => 'received']);
    // }

// 
}
