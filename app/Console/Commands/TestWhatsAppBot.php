<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsAppService;
use App\Services\ChatAIService;
use App\Models\Service;

class TestWhatsAppBot extends Command
{
    protected $signature = 'whatsapp:test {phone} {message}';
    protected $description = 'Test WhatsApp bot dengan pesan tertentu';

    public function handle()
    {
        $phone = $this->argument('phone');
        $message = $this->argument('message');

        $this->info("Testing WhatsApp bot...");
        $this->info("Phone: {$phone}");
        $this->info("Message: {$message}");

        try {
            $whatsappService = app(WhatsAppService::class);
            $chatAIService = app(ChatAIService::class);

            // Test AI response
            $aiResponse = $chatAIService->ask($message);
            $this->info("AI Response: {$aiResponse}");

            // Test WhatsApp sending (uncomment jika ingin test kirim pesan)
            // $result = $whatsappService->sendMessage($phone, $aiResponse);
            // $this->info("WhatsApp Send Result: " . ($result ? 'Success' : 'Failed'));

            $this->info("Test completed successfully!");

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
} 