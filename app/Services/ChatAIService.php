<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Service;
use App\Models\Order;
use App\Services\WhatsAppService;
use App\Services\PricingService;
use App\Services\LocationService;

class ChatAIService
{
    protected $whatsappService;
    protected $pricingService;
    protected $locationService;

    public function __construct()
    {
        $this->whatsappService = app(WhatsAppService::class);
        $this->pricingService = app(PricingService::class);
        $this->locationService = app(LocationService::class);
    }

    public function ask($message)
    {
        $apiKey = env('OPENAI_API_KEY');
        $model = env('OPENAI_MODEL', 'mistralai/mistral-7b-instruct');
        $apiBase = env('OPENAI_API_BASE', 'https://openrouter.ai/api/v1');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'HTTP-Referer' => 'http://localhost',
            'X-Title' => 'WA Delivery Bot',
        ])->post($apiBase . '/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'Kamu adalah asisten layanan delivery lokal. Jawab dalam Bahasa Indonesia.'],
                ['role' => 'user', 'content' => $message],
            ],
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            $json = $response->json();
            if (isset($json['choices'][0]['message']['content'])) {
                return $json['choices'][0]['message']['content'];
            }
        }

        return 'Maaf, sistem sedang mengalami gangguan.';
    }

    public function processMessage(ChatSession $session, ChatMessage $message): void
    {
        try {
            // Identifikasi entity (customer/partner)
            $this->identifyEntity($session, $message);

            // Proses berdasarkan tipe pesan
            if ($message->isText()) {
                $this->processTextMessage($session, $message);
            } elseif ($message->isLocation()) {
                $this->processLocationMessage($session, $message);
            }

        } catch (\Exception $e) {
            Log::error('Error processing message with AI', [
                'session_id' => $session->id,
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);

            $this->sendErrorMessage($session);
        }
    }

    protected function identifyEntity(ChatSession $session, ChatMessage $message): void
    {
        if ($session->entity_type === 'unknown') {
            $whatsappNumber = $session->whatsapp_number;

            // Cek apakah customer
            $customer = Customer::where('whatsapp_number', $whatsappNumber)->first();
            if ($customer) {
                $session->update([
                    'entity_type' => 'customer',
                    'entity_id' => $customer->id
                ]);
                return;
            }

            // Cek apakah partner
            $partner = Partner::where('whatsapp_number', $whatsappNumber)->first();
            if ($partner) {
                $session->update([
                    'entity_type' => 'partner',
                    'entity_id' => $partner->id
                ]);
                return;
            }
        }
    }

    protected function processTextMessage(ChatSession $session, ChatMessage $message): void
    {
        $content = strtolower(trim($message->content));
        $currentStep = $session->getContextValue('current_step', 'welcome');

        switch ($currentStep) {
            case 'welcome':
                $this->handleWelcomeStep($session, $content);
                break;
            case 'service_selection':
                $this->handleServiceSelection($session, $content);
                break;
            case 'pickup_location':
                $this->handlePickupLocation($session, $content);
                break;
            case 'destination_location':
                $this->handleDestinationLocation($session, $content);
                break;
            case 'order_confirmation':
                $this->handleOrderConfirmation($session, $content);
                break;
            case 'partner_menu':
                $this->handlePartnerMenu($session, $content);
                break;
            default:
                $this->handleGeneralMessage($session, $content);
        }
    }

    protected function processLocationMessage(ChatSession $session, ChatMessage $message): void
    {
        $locationData = json_decode($message->content, true);
        $currentStep = $session->getContextValue('current_step');

        if ($currentStep === 'pickup_location') {
            $this->handlePickupLocationData($session, $locationData);
        } elseif ($currentStep === 'destination_location') {
            $this->handleDestinationLocationData($session, $locationData);
        }
    }

    protected function handleWelcomeStep(ChatSession $session, string $content): void
    {
        $welcomeMessage = "Halo! Selamat datang di layanan delivery kami. 🚚\n\n";
        $welcomeMessage .= "Saya siap membantu Anda dengan berbagai layanan:\n";
        $welcomeMessage .= "1️⃣ *Ojek* - Antar jemput\n";
        $welcomeMessage .= "2️⃣ *Pengantaran* - Kirim makanan/barang\n";
        $welcomeMessage .= "3️⃣ *Jasa Belanja* - Belanja dan antar\n";
        $welcomeMessage .= "4️⃣ *Jasa Panggilan* - Tukang, pijat, service\n\n";
        $welcomeMessage .= "Silakan pilih layanan yang Anda butuhkan (ketik 1, 2, 3, atau 4)";

        $this->whatsappService->sendMessage($session->whatsapp_number, $welcomeMessage, $session->session_id);
        
        $session->setContextValue('current_step', 'service_selection');
    }

    protected function handleServiceSelection(ChatSession $session, string $content): void
    {
        $serviceMap = [
            '1' => 'ojek',
            '2' => 'pengantaran',
            '3' => 'belanja',
            '4' => 'panggilan'
        ];

        if (isset($serviceMap[$content])) {
            $serviceType = $serviceMap[$content];
            $session->setContextValue('selected_service', $serviceType);
            
            $message = "Baik, Anda memilih layanan *" . strtoupper($serviceType) . "*\n\n";
            $message .= "Sekarang saya perlu lokasi penjemputan Anda.\n";
            $message .= "Silakan kirim lokasi Anda atau ketik alamat penjemputan.";

            $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
            $session->setContextValue('current_step', 'pickup_location');
        } else {
            $message = "Maaf, pilihan tidak valid. Silakan pilih 1, 2, 3, atau 4.";
            $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
        }
    }

    protected function handlePickupLocation(ChatSession $session, string $content): void
    {
        // Simpan alamat penjemputan
        $session->setContextValue('pickup_address', $content);
        
        $message = "Terima kasih! Lokasi penjemputan: *" . $content . "*\n\n";
        $message .= "Sekarang saya perlu lokasi tujuan.\n";
        $message .= "Silakan kirim lokasi tujuan atau ketik alamat tujuan.";

        $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
        $session->setContextValue('current_step', 'destination_location');
    }

    protected function handlePickupLocationData(ChatSession $session, array $locationData): void
    {
        $session->setContextValue('pickup_latitude', $locationData['latitude']);
        $session->setContextValue('pickup_longitude', $locationData['longitude']);
        $session->setContextValue('pickup_address', $locationData['address'] ?? 'Lokasi yang dikirim');

        $message = "Lokasi penjemputan berhasil disimpan! 📍\n\n";
        $message .= "Sekarang saya perlu lokasi tujuan.\n";
        $message .= "Silakan kirim lokasi tujuan atau ketik alamat tujuan.";

        $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
        $session->setContextValue('current_step', 'destination_location');
    }

    protected function handleDestinationLocation(ChatSession $session, string $content): void
    {
        $session->setContextValue('destination_address', $content);
        $this->calculatePriceAndConfirm($session);
    }

    protected function handleDestinationLocationData(ChatSession $session, array $locationData): void
    {
        $session->setContextValue('destination_latitude', $locationData['latitude']);
        $session->setContextValue('destination_longitude', $locationData['longitude']);
        $session->setContextValue('destination_address', $locationData['address'] ?? 'Lokasi yang dikirim');

        $this->calculatePriceAndConfirm($session);
    }

    protected function calculatePriceAndConfirm(ChatSession $session): void
    {
        $pickupLat = $session->getContextValue('pickup_latitude');
        $pickupLng = $session->getContextValue('pickup_longitude');
        $destLat = $session->getContextValue('destination_latitude');
        $destLng = $session->getContextValue('destination_longitude');

        if ($pickupLat && $pickupLng && $destLat && $destLng) {
            $distance = $this->locationService->calculateDistance($pickupLat, $pickupLng, $destLat, $destLng);
            $serviceType = $session->getContextValue('selected_service');
            $price = $this->pricingService->calculatePrice($serviceType, $distance);

            $session->setContextValue('distance_km', $distance);
            $session->setContextValue('total_price', $price);

            $message = "📋 *RINCIAN PESANAN*\n\n";
            $message .= "🏷️ Layanan: " . strtoupper($serviceType) . "\n";
            $message .= "📍 Penjemputan: " . $session->getContextValue('pickup_address') . "\n";
            $message .= "🎯 Tujuan: " . $session->getContextValue('destination_address') . "\n";
            $message .= "📏 Jarak: " . number_format($distance, 1) . " km\n";
            $message .= "💰 Total: Rp " . number_format($price, 0, ',', '.') . "\n\n";
            $message .= "Apakah Anda ingin melanjutkan pesanan ini?\n";
            $message .= "Ketik *YA* untuk konfirmasi atau *BATAL* untuk membatalkan.";

            $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
            $session->setContextValue('current_step', 'order_confirmation');
        } else {
            $message = "Maaf, saya tidak dapat menghitung jarak. Silakan kirim lokasi yang valid.";
            $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
        }
    }

    protected function handleOrderConfirmation(ChatSession $session, string $content): void
    {
        if (in_array(strtolower($content), ['ya', 'yes', 'ok', 'lanjutkan'])) {
            $this->createOrder($session);
        } elseif (in_array(strtolower($content), ['batal', 'cancel', 'tidak'])) {
            $message = "Pesanan dibatalkan. Terima kasih telah menghubungi kami! 🙏";
            $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
            $session->setContextValue('current_step', 'welcome');
        } else {
            $message = "Silakan ketik *YA* untuk konfirmasi atau *BATAL* untuk membatalkan.";
            $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
        }
    }

    protected function createOrder(ChatSession $session): void
    {
        try {
            // Dapatkan atau buat customer
            $customer = $this->getOrCreateCustomer($session);

            // Dapatkan service
            $serviceType = $session->getContextValue('selected_service');
            $service = Service::where('code', $serviceType)->first();

            if (!$service) {
                throw new \Exception('Service tidak ditemukan');
            }

            // Buat order
            $order = Order::create([
                'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid()),
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'status' => 'pending',
                'pickup_address' => $session->getContextValue('pickup_address'),
                'pickup_latitude' => $session->getContextValue('pickup_latitude'),
                'pickup_longitude' => $session->getContextValue('pickup_longitude'),
                'destination_address' => $session->getContextValue('destination_address'),
                'destination_latitude' => $session->getContextValue('destination_latitude'),
                'destination_longitude' => $session->getContextValue('destination_longitude'),
                'distance_km' => $session->getContextValue('distance_km'),
                'base_price' => $service->base_price,
                'distance_price' => $session->getContextValue('distance_km') * $service->price_per_km,
                'total_price' => $session->getContextValue('total_price'),
            ]);

            $message = "✅ *PESANAN BERHASIL DIBUAT*\n\n";
            $message .= "📋 No. Order: " . $order->order_number . "\n";
            $message .= "💰 Total: " . $order->formatted_total_price . "\n";
            $message .= "📏 Jarak: " . $order->formatted_distance . "\n\n";
            $message .= "Tim kami akan segera menghubungi Anda untuk konfirmasi lebih lanjut.\n";
            $message .= "Terima kasih telah menggunakan layanan kami! 🙏";

            $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
            $session->setContextValue('current_step', 'welcome');

            // Notifikasi ke partner yang tersedia
            $this->notifyAvailablePartners($order);

        } catch (\Exception $e) {
            Log::error('Error creating order', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);

            $message = "Maaf, terjadi kesalahan saat membuat pesanan. Silakan coba lagi.";
            $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
        }
    }

    protected function getOrCreateCustomer(ChatSession $session): Customer
    {
        $customer = Customer::where('whatsapp_number', $session->whatsapp_number)->first();

        if (!$customer) {
            $customer = Customer::create([
                'whatsapp_number' => $session->whatsapp_number,
                'name' => 'Customer ' . substr($session->whatsapp_number, -4),
                'address' => $session->getContextValue('pickup_address'),
                'latitude' => $session->getContextValue('pickup_latitude'),
                'longitude' => $session->getContextValue('pickup_longitude'),
            ]);
        }

        return $customer;
    }

    protected function notifyAvailablePartners(Order $order): void
    {
        $availablePartners = Partner::where('status', 'active')
            ->where('is_online', true)
            ->whereJsonContains('service_types', $order->service->code)
            ->get();

        foreach ($availablePartners as $partner) {
            $message = "🚨 *ORDER BARU*\n\n";
            $message .= "📋 No. Order: " . $order->order_number . "\n";
            $message .= "🏷️ Layanan: " . $order->service->name . "\n";
            $message .= "📍 Penjemputan: " . $order->pickup_address . "\n";
            $message .= "🎯 Tujuan: " . $order->destination_address . "\n";
            $message .= "💰 Total: " . $order->formatted_total_price . "\n\n";
            $message .= "Ketik *AMBIL* untuk mengambil order ini.";

            $this->whatsappService->sendMessage($partner->whatsapp_number, $message);
        }
    }

    protected function handleGeneralMessage(ChatSession $session, string $content): void
    {
        // Gunakan AI untuk merespons pesan umum
        $aiResponse = $this->ask($content);
        $this->whatsappService->sendMessage($session->whatsapp_number, $aiResponse, $session->session_id);
    }

    protected function handlePartnerMenu(ChatSession $session, string $content): void
    {
        $partner = Partner::find($session->entity_id);
        
        if (!$partner) {
            $message = "Maaf, data partner tidak ditemukan.";
            $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
            return;
        }

        switch (strtolower($content)) {
            case 'ambil':
                $this->handleTakeOrder($session, $partner);
                break;
            case 'saldo':
                $this->handleCheckBalance($session, $partner);
                break;
            case 'mutasi':
                $this->handleTransactionHistory($session, $partner);
                break;
            case 'online':
                $this->handleGoOnline($session, $partner);
                break;
            case 'offline':
                $this->handleGoOffline($session, $partner);
                break;
            default:
                $message = "Menu Partner:\n";
                $message .= "• AMBIL - Ambil order baru\n";
                $message .= "• SALDO - Cek saldo\n";
                $message .= "• MUTASI - Riwayat transaksi\n";
                $message .= "• ONLINE - Set status online\n";
                $message .= "• OFFLINE - Set status offline";
                $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
        }
    }

    protected function handleTakeOrder(ChatSession $session, Partner $partner): void
    {
        // Implementasi untuk mengambil order
        $pendingOrder = Order::where('status', 'pending')
            ->whereNull('partner_id')
            ->first();

        if ($pendingOrder) {
            $pendingOrder->update([
                'partner_id' => $partner->id,
                'status' => 'assigned'
            ]);

            $message = "✅ Order berhasil diambil!\n\n";
            $message .= "📋 No. Order: " . $pendingOrder->order_number . "\n";
            $message .= "📍 Penjemputan: " . $pendingOrder->pickup_address . "\n";
            $message .= "🎯 Tujuan: " . $pendingOrder->destination_address . "\n";
            $message .= "💰 Total: " . $pendingOrder->formatted_total_price;

            $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
        } else {
            $message = "Tidak ada order yang tersedia saat ini.";
            $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
        }
    }

    protected function handleCheckBalance(ChatSession $session, Partner $partner): void
    {
        $message = "💰 *SALDO ANDA*\n\n";
        $message .= "Saldo: " . $partner->formatted_balance . "\n";
        $message .= "Status: " . ($partner->is_online ? '🟢 Online' : '🔴 Offline');

        $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
    }

    protected function handleTransactionHistory(ChatSession $session, Partner $partner): void
    {
        $transactions = $partner->transactions()
            ->latest()
            ->take(5)
            ->get();

        if ($transactions->isEmpty()) {
            $message = "Belum ada transaksi.";
        } else {
            $message = "📊 *RIWAYAT TRANSAKSI*\n\n";
            foreach ($transactions as $transaction) {
                $message .= "• " . $transaction->formatted_amount . " - " . $transaction->description . "\n";
            }
        }

        $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
    }

    protected function handleGoOnline(ChatSession $session, Partner $partner): void
    {
        $partner->update(['is_online' => true]);
        $message = "🟢 Status berhasil diubah menjadi ONLINE";
        $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
    }

    protected function handleGoOffline(ChatSession $session, Partner $partner): void
    {
        $partner->update(['is_online' => false]);
        $message = "🔴 Status berhasil diubah menjadi OFFLINE";
        $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
    }

    protected function sendErrorMessage(ChatSession $session): void
    {
        $message = "Maaf, terjadi kesalahan dalam sistem. Silakan coba lagi atau hubungi customer service kami.";
        $this->whatsappService->sendMessage($session->whatsapp_number, $message, $session->session_id);
    }
}
