<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Service;
use App\Models\Customer;
use App\Models\User;
use App\Models\Order;
use App\Models\ChatHistory;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string|max:1000',
            ]);

            $message = $request->input('message');
            $user = auth()->user();
            
            // Ambil data dari database untuk konteks
            $contextData = $this->getContextData($user);
            
            // Ambil konfigurasi dari .env
            $apiKey = config('services.openai.api_key');
            $apiBase = config('services.openai.api_base');
            $model = config('services.openai.model');

            // Buat system prompt dengan konteks database
            $systemPrompt = $this->createSystemPrompt($contextData, $user);

            // Prepare request ke OpenAI API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($apiBase . '/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'max_tokens' => 800,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $aiResponse = $data['choices'][0]['message']['content'] ?? 'Maaf, saya tidak dapat memberikan jawaban saat ini.';
                
                // Simpan riwayat chat
                ChatHistory::create([
                    'user_id' => $user->id,
                    'message' => $message,
                    'response' => $aiResponse,
                    'role' => $user->role,
                    'session_id' => session()->getId(),
                    'metadata' => [
                        'user_role' => $user->role,
                        'services_queried' => $this->extractServicesFromMessage($message),
                        'response_length' => strlen($aiResponse)
                    ]
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => $aiResponse,
                    'timestamp' => now()->format('H:i')
                ]);
            } else {
                Log::error('OpenAI API Error', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Maaf, terjadi kesalahan dalam memproses permintaan Anda.',
                    'error' => $response->status()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Chat Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi nanti.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getContextData($user)
    {
        $context = [];

        // Ambil data services
        $services = Service::where('is_active', true)->get();
        $context['services'] = $services->map(function($service) {
            return [
                'name' => $service->name,
                'code' => $service->code,
                'description' => $service->description,
                'base_price' => $service->base_price,
                'price_per_km' => $service->price_per_km,
                'minimum_price' => $service->minimum_price,
                'settings' => $service->settings
            ];
        });

        // Ambil data customer jika user adalah customer
        if ($user->role === 'customer') {
            $customer = Customer::where('user_id', $user->id)->first();
            if ($customer) {
                $context['customer'] = [
                    'name' => $customer->name,
                    'whatsapp_number' => $customer->whatsapp_number,
                    'address' => $customer->address,
                    'balance' => $customer->balance,
                    'is_active' => $customer->is_active
                ];

                // Ambil riwayat order customer
                $orders = Order::where('customer_id', $customer->id)
                    ->with('service')
                    ->latest()
                    ->take(5)
                    ->get();

                $context['recent_orders'] = $orders->map(function($order) {
                    return [
                        'id' => $order->id,
                        'service_name' => $order->service->name,
                        'status' => $order->status,
                        'total_amount' => $order->total_amount,
                        'created_at' => $order->created_at->format('d/m/Y H:i')
                    ];
                });
            }
        }

        // Ambil statistik umum
        $context['statistics'] = [
            'total_services' => Service::where('is_active', true)->count(),
            'total_customers' => Customer::where('is_active', true)->count(),
            'total_orders' => Order::count(),
        ];

        return $context;
    }

    private function createSystemPrompt($contextData, $user)
    {
        $role = $user->role;
        $userName = $user->name;

        $prompt = "Anda adalah asisten AI untuk platform WA BLAST AI. Anda membantu pengguna dengan informasi tentang layanan dan fitur platform.\n\n";

        // Informasi berdasarkan role user
        switch ($role) {
            case 'master':
                $prompt .= "Anda berbicara dengan Master Admin ($userName). Anda memiliki akses penuh ke semua data sistem.\n\n";
                break;
            case 'admin':
                $prompt .= "Anda berbicara dengan Admin Kota ($userName). Anda dapat membantu dengan manajemen layanan dan customer.\n\n";
                break;
            case 'cs':
                $prompt .= "Anda berbicara dengan Customer Service ($userName). Anda dapat membantu customer dengan layanan dan support.\n\n";
                break;
            case 'customer':
                $prompt .= "Anda berbicara dengan Customer ($userName). Anda dapat membantu dengan pemesanan layanan dan informasi akun.\n\n";
                break;
        }

        // Informasi layanan yang tersedia
        $prompt .= "Layanan yang tersedia:\n";
        foreach ($contextData['services'] as $service) {
            $prompt .= "- {$service['name']} ({$service['code']}): {$service['description']}\n";
            $prompt .= "  Harga dasar: Rp " . number_format($service['base_price'], 0, ',', '.') . "\n";
            $prompt .= "  Harga per km: Rp " . number_format($service['price_per_km'], 0, ',', '.') . "\n";
            $prompt .= "  Harga minimum: Rp " . number_format($service['minimum_price'], 0, ',', '.') . "\n\n";
        }

        // Informasi customer jika applicable
        if (isset($contextData['customer'])) {
            $customer = $contextData['customer'];
            $prompt .= "Informasi Customer:\n";
            $prompt .= "- Nama: {$customer['name']}\n";
            $prompt .= "- WhatsApp: {$customer['whatsapp_number']}\n";
            $prompt .= "- Alamat: {$customer['address']}\n";
            $prompt .= "- Saldo: Rp " . number_format($customer['balance'], 0, ',', '.') . "\n";
            $prompt .= "- Status: " . ($customer['is_active'] ? 'Aktif' : 'Tidak Aktif') . "\n\n";

            if (!empty($contextData['recent_orders'])) {
                $prompt .= "Riwayat Order Terbaru:\n";
                foreach ($contextData['recent_orders'] as $order) {
                    $prompt .= "- Order #{$order['id']}: {$order['service_name']} - {$order['status']} - Rp " . number_format($order['total_amount'], 0, ',', '.') . " ({$order['created_at']})\n";
                }
                $prompt .= "\n";
            }
        }

        // Statistik umum
        $stats = $contextData['statistics'];
        $prompt .= "Statistik Sistem:\n";
        $prompt .= "- Total Layanan Aktif: {$stats['total_services']}\n";
        $prompt .= "- Total Customer Aktif: {$stats['total_customers']}\n";
        $prompt .= "- Total Order: {$stats['total_orders']}\n\n";

        $prompt .= "Instruksi:\n";
        $prompt .= "1. Berikan jawaban yang informatif dan bermanfaat berdasarkan data yang tersedia\n";
        $prompt .= "2. Jika ditanya tentang layanan, berikan informasi lengkap termasuk harga\n";
        $prompt .= "3. Jika ditanya tentang akun customer, berikan informasi yang relevan\n";
        $prompt .= "4. Gunakan bahasa yang sopan dan profesional\n";
        $prompt .= "5. Jika tidak ada informasi yang cukup, katakan bahwa Anda tidak memiliki data tersebut\n";
        $prompt .= "6. Format harga selalu gunakan format Indonesia (contoh: Rp 15.000)\n";

        return $prompt;
    }

    private function extractServicesFromMessage($message)
    {
        $services = Service::where('is_active', true)->get();
        $foundServices = [];
        
        foreach ($services as $service) {
            if (stripos($message, $service->name) !== false || 
                stripos($message, $service->code) !== false) {
                $foundServices[] = $service->code;
            }
        }
        
        return $foundServices;
    }

    public function getHistory(Request $request)
    {
        $user = auth()->user();
        $limit = $request->get('limit', 10);
        
        $history = ChatHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($chat) {
                return [
                    'id' => $chat->id,
                    'message' => $chat->message,
                    'response' => $chat->response,
                    'timestamp' => $chat->created_at->format('H:i'),
                    'date' => $chat->created_at->format('d/m/Y')
                ];
            });
        
        return response()->json([
            'success' => true,
            'history' => $history
        ]);
    }
}
