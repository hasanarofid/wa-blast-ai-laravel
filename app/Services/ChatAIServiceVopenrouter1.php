<?php

// app/Services/ChatAIService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatAIService
{
    public function ask($message)
    {
        $apiKey = env('OPENAI_API_KEY');
        $model = env('OPENAI_MODEL', 'openchat/openchat-3.5');
        $apiBase = env('OPENAI_API_BASE', 'https://openrouter.ai/api/v1');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'HTTP-Referer' => 'http://localhost', // ganti sesuai domain kamu kalau perlu
            'X-Title' => 'WA Delivery Bot',
        ])->post($apiBase . '/chat/completions', [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Kamu adalah asisten customer service layanan delivery lokal. Jawablah dengan bahasa Indonesia yang natural dan sopan.',
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ],
            'temperature' => 0.7
        ]);

        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'];
        }

        dd($response->body()); // DEBUG di sini
        return 'Maaf, sistem sedang mengalami gangguan.';


        return 'Maaf, sistem sedang mengalami gangguan.';
    }

// 
}
