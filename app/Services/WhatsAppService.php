<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ChatMessage;
use App\Models\ChatSession;

class WhatsAppService
{
    protected $apiUrl;
    protected $apiToken;
    protected $phoneNumberId;

    public function __construct()
    {
        $this->apiUrl = env('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0');
        $this->apiToken = env('WHATSAPP_API_TOKEN');
        $this->phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');
    }

    public function sendMessage(string $to, string $message, string $sessionId = null): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/' . $this->phoneNumberId . '/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $messageId = $data['messages'][0]['id'] ?? null;

                // Simpan pesan ke database
                if ($sessionId) {
                    $session = ChatSession::where('session_id', $sessionId)->first();
                    if ($session) {
                        ChatMessage::create([
                            'chat_session_id' => $session->id,
                            'message_id' => $messageId,
                            'direction' => 'outbound',
                            'type' => 'text',
                            'content' => $message,
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
                    }
                }

                Log::info('WhatsApp message sent successfully', [
                    'to' => $to,
                    'message_id' => $messageId,
                    'session_id' => $sessionId
                ]);

                return true;
            } else {
                Log::error('Failed to send WhatsApp message', [
                    'to' => $to,
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception sending WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendLocationMessage(string $to, string $message, float $latitude, float $longitude, string $name = null, string $address = null): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/' . $this->phoneNumberId . '/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'location',
                'location' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'name' => $name ?? 'Lokasi',
                    'address' => $address ?? 'Alamat tidak tersedia'
                ]
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp location message sent successfully', [
                    'to' => $to,
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]);
                return true;
            } else {
                Log::error('Failed to send WhatsApp location message', [
                    'to' => $to,
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception sending WhatsApp location message', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendTemplateMessage(string $to, string $templateName, array $parameters = []): bool
    {
        try {
            $messageData = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => 'id'
                    ]
                ]
            ];

            if (!empty($parameters)) {
                $messageData['template']['components'] = [
                    [
                        'type' => 'body',
                        'parameters' => $parameters
                    ]
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/' . $this->phoneNumberId . '/messages', $messageData);

            if ($response->successful()) {
                Log::info('WhatsApp template message sent successfully', [
                    'to' => $to,
                    'template' => $templateName
                ]);
                return true;
            } else {
                Log::error('Failed to send WhatsApp template message', [
                    'to' => $to,
                    'template' => $templateName,
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception sending WhatsApp template message', [
                'to' => $to,
                'template' => $templateName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function verifyWebhook(string $mode, string $token, string $challenge): ?string
    {
        $verifyToken = env('WHATSAPP_VERIFY_TOKEN');
        
        if ($mode === 'subscribe' && $token === $verifyToken) {
            return $challenge;
        }
        
        return null;
    }

    public function processWebhook(array $data): void
    {
        try {
            if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
                $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
                $this->handleIncomingMessage($message);
            }
        } catch (\Exception $e) {
            Log::error('Error processing webhook', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    protected function handleIncomingMessage(array $message): void
    {
        $from = $message['from'];
        $messageId = $message['id'];
        $messageType = $message['type'];
        $timestamp = $message['timestamp'];

        // Dapatkan atau buat session
        $session = $this->getOrCreateSession($from);

        // Simpan pesan masuk
        $chatMessage = ChatMessage::create([
            'chat_session_id' => $session->id,
            'message_id' => $messageId,
            'direction' => 'inbound',
            'type' => $messageType,
            'content' => $this->extractMessageContent($message),
            'metadata' => $message,
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s', $timestamp),
        ]);

        // Update session activity
        $session->updateActivity();

        // Proses pesan dengan AI
        $this->processMessageWithAI($session, $chatMessage);
    }

    protected function getOrCreateSession(string $whatsappNumber): ChatSession
    {
        $session = ChatSession::where('whatsapp_number', $whatsappNumber)
            ->where('status', 'active')
            ->first();

        if (!$session) {
            $session = ChatSession::create([
                'whatsapp_number' => $whatsappNumber,
                'entity_type' => 'unknown',
                'session_id' => uniqid('session_'),
                'status' => 'active',
                'last_activity' => now(),
            ]);
        }

        return $session;
    }

    protected function extractMessageContent(array $message): string
    {
        switch ($message['type']) {
            case 'text':
                return $message['text']['body'];
            case 'location':
                return json_encode([
                    'latitude' => $message['location']['latitude'],
                    'longitude' => $message['location']['longitude'],
                    'name' => $message['location']['name'] ?? '',
                    'address' => $message['location']['address'] ?? ''
                ]);
            default:
                return json_encode($message);
        }
    }

    protected function processMessageWithAI(ChatSession $session, ChatMessage $message): void
    {
        // Implementasi akan dibuat di service terpisah
        app(ChatAIService::class)->processMessage($session, $message);
    }
} 