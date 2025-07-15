<?php

// app/Services/ChatAIService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatAIService
{
    public function ask($message)
    {
        $apiKey = env('OPENAI_API_KEY');
        $model = env('OPENAI_MODEL', 'gpt-3.5-turbo');

        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
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

        \Log::debug('OpenAI Response:', $response->json());

        // if ($response->successful()) {
        //     return $response->json()['choices'][0]['message']['content'];
        // }

        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'];
        } else {
            \Log::error('Gagal OpenAI', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
        
        return 'Maaf, sistem sedang mengalami gangguan.';

    }
}
