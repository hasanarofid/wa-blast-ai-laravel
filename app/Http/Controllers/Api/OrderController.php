<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Service;
use App\Services\PricingService;
use App\Services\LocationService;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected $pricingService;
    protected $locationService;

    public function __construct(PricingService $pricingService, LocationService $locationService)
    {
        $this->pricingService = $pricingService;
        $this->locationService = $locationService;
    }

    public function index(Request $request)
    {
        $orders = Order::with(['customer', 'partner', 'service'])
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->customer_id, function($query, $customerId) {
                return $query->where('customer_id', $customerId);
            })
            ->when($request->partner_id, function($query, $partnerId) {
                return $query->where('partner_id', $partnerId);
            })
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_whatsapp' => 'required|string',
            'service_code' => 'required|string|exists:services,code',
            'pickup_address' => 'required|string',
            'pickup_latitude' => 'required|numeric',
            'pickup_longitude' => 'required|numeric',
            'destination_address' => 'required|string',
            'destination_latitude' => 'required|numeric',
            'destination_longitude' => 'required|numeric',
            'notes' => 'nullable|string',
            'items' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Dapatkan atau buat customer
            $customer = Customer::firstOrCreate(
                ['whatsapp_number' => $request->customer_whatsapp],
                [
                    'name' => 'Customer ' . substr($request->customer_whatsapp, -4),
                    'address' => $request->pickup_address,
                    'latitude' => $request->pickup_latitude,
                    'longitude' => $request->pickup_longitude,
                ]
            );

            // Dapatkan service
            $service = Service::where('code', $request->service_code)->first();

            // Hitung jarak dan harga
            $distance = $this->locationService->calculateDistance(
                $request->pickup_latitude,
                $request->pickup_longitude,
                $request->destination_latitude,
                $request->destination_longitude
            );

            $totalPrice = $this->pricingService->calculatePrice($request->service_code, $distance);

            // Buat order
            $order = Order::create([
                'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid()),
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'status' => 'pending',
                'pickup_address' => $request->pickup_address,
                'pickup_latitude' => $request->pickup_latitude,
                'pickup_longitude' => $request->pickup_longitude,
                'destination_address' => $request->destination_address,
                'destination_latitude' => $request->destination_latitude,
                'destination_longitude' => $request->destination_longitude,
                'distance_km' => $distance,
                'base_price' => $service->base_price,
                'distance_price' => $distance * $service->price_per_km,
                'total_price' => $totalPrice,
                'notes' => $request->notes,
                'items' => $request->items,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat',
                'data' => $order->load(['customer', 'service'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Order $order)
    {
        return response()->json([
            'success' => true,
            'data' => $order->load(['customer', 'partner', 'service', 'transactions'])
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,confirmed,assigned,picked_up,in_progress,completed,cancelled,failed',
            'partner_id' => 'nullable|exists:partners,id',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order->update($request->only(['status', 'partner_id', 'notes']));

            // Update timestamp berdasarkan status
            if ($request->status === 'picked_up') {
                $order->update(['picked_up_at' => now()]);
            } elseif ($request->status === 'completed') {
                $order->update(['completed_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil diupdate',
                'data' => $order->load(['customer', 'partner', 'service'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupdate order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function calculatePrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_code' => 'required|string|exists:services,code',
            'pickup_latitude' => 'required|numeric',
            'pickup_longitude' => 'required|numeric',
            'destination_latitude' => 'required|numeric',
            'destination_longitude' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $distance = $this->locationService->calculateDistance(
                $request->pickup_latitude,
                $request->pickup_longitude,
                $request->destination_latitude,
                $request->destination_longitude
            );

            $totalPrice = $this->pricingService->calculatePrice($request->service_code, $distance);

            return response()->json([
                'success' => true,
                'data' => [
                    'distance_km' => round($distance, 2),
                    'total_price' => $totalPrice,
                    'formatted_price' => 'Rp ' . number_format($totalPrice, 0, ',', '.')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghitung harga',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function statistics(Request $request)
    {
        $period = $request->get('period', 'today');
        
        $query = Order::query();
        
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month);
                break;
        }

        $statistics = [
            'total_orders' => $query->count(),
            'total_revenue' => $query->sum('total_price'),
            'completed_orders' => $query->where('status', 'completed')->count(),
            'pending_orders' => $query->where('status', 'pending')->count(),
            'average_order_value' => $query->avg('total_price'),
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
} 