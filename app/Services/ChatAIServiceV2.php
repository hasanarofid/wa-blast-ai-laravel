<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatAIService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key'); // pastikan ini diisi di config/services.php
        $this->baseUrl = 'https://api.openai.com/v1/chat/completions'; // bisa disesuaikan kalau pakai endpoint lain
    }

    /**
     * Kirim pesan ke ChatGPT dan dapatkan responsenya
     */
    public function ask(string $message, array $history = []): ?string
    {
        try {
            $payload = [
                'model' => 'gpt-3.5-turbo',
                'messages' => array_merge(
                    [['role' => 'system', 'content' => 'Kamu adalah asisten pelanggan yang membantu pengguna memesan layanan antar barang.']],
                    $history,
                    [['role' => 'user', 'content' => $message]]
                ),
                'temperature' => 0.7,
            ];

            $response = Http::withToken($this->apiKey)
                ->post($this->baseUrl, $payload);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'] ?? null;
            }

            Log::error('ChatAIService Error', ['response' => $response->body()]);
            return 'Maaf, terjadi kesalahan saat menghubungi AI.';
        } catch (\Exception $e) {
            Log::error('ChatAIService Exception', ['message' => $e->getMessage()]);
            return 'Maaf, sistem sedang mengalami gangguan.';
        }
    }
}
