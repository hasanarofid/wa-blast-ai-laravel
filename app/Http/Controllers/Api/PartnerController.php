<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Partner;
use Illuminate\Support\Facades\Validator;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $partners = Partner::when($request->search, function($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('whatsapp_number', 'like', "%{$search}%");
            })
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->is_online, function($query, $isOnline) {
                return $query->where('is_online', $isOnline);
            })
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $partners
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_number' => 'required|string|unique:partners,whatsapp_number',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'vehicle_type' => 'nullable|string',
            'vehicle_number' => 'nullable|string',
            'service_types' => 'required|array',
            'referral_code' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $partner = Partner::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Partner berhasil dibuat',
                'data' => $partner
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat partner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Partner $partner)
    {
        return response()->json([
            'success' => true,
            'data' => $partner->load(['orders', 'transactions'])
        ]);
    }

    public function update(Request $request, Partner $partner)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'vehicle_type' => 'nullable|string',
            'vehicle_number' => 'nullable|string',
            'service_types' => 'sometimes|array',
            'referral_code' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,suspended'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $partner->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Partner berhasil diupdate',
                'data' => $partner
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupdate partner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function orders(Partner $partner)
    {
        $orders = $partner->orders()
            ->with(['customer', 'service'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function transactions(Partner $partner)
    {
        $transactions = $partner->transactions()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function goOnline(Partner $partner)
    {
        try {
            $partner->update(['is_online' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Partner berhasil diaktifkan online',
                'data' => $partner
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengaktifkan partner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function goOffline(Partner $partner)
    {
        try {
            $partner->update(['is_online' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Partner berhasil dinonaktifkan offline',
                'data' => $partner
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menonaktifkan partner',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 