<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncomingMessage;
use App\Services\ChatAIService;

class WebhookController extends Controller
{
    // 
    public function handle(Request $request, ChatAIService $chatAI)
    {
        $data = $request->all();
        $from = $data['from'] ?? null;
        $text = $data['body'] ?? null;
        $type = $data['type'] ?? 'text';

        // Simpan log
        IncomingMessage::create([
            'from' => $from,
            'message' => $text,
            'type' => $type,
            'raw' => json_encode($data)
        ]);

        // Jawaban dari ChatGPT
        try {
            $aiReply = $chatAI->ask($text);
        } catch (\Exception $e) {
            $aiReply = "Maaf, CS AI sedang gangguan. Mohon tunggu atau hubungi admin.";
        }

        // Kirim balasan ke user WA
        $this->sendReply($from, $aiReply);

        return response()->json(['status' => 'received']);
    }


    // v1
    // public function handle(Request $request)
    // {
    //     $data = $request->all();

    //     // Contoh struktur Ultramsg
    //     $from = $data['from'] ?? null;
    //     $text = $data['body'] ?? null;
    //     $type = $data['type'] ?? 'text';

    //     // Simpan log
    //     IncomingMessage::create([
    //         'from' => $from,
    //         'message' => $text,
    //         'type' => $type,
    //         'raw' => json_encode($data)
    //     ]);

    //     // Kirim ke AI atau logic handler
    //     // Untuk sementara kirim balik pesan otomatis
    //     $this->sendReply($from, "Pesan Anda kami terima: " . $text);

    //     return response()->json(['status' => 'received']);
    // }

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
}
