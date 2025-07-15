<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::when($request->search, function($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('whatsapp_number', 'like', "%{$search}%");
            })
            ->when($request->is_active, function($query, $isActive) {
                return $query->where('is_active', $isActive);
            })
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_number' => 'required|string|unique:customers,whatsapp_number',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
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
            $customer = Customer::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil dibuat',
                'data' => $customer
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Customer $customer)
    {
        return response()->json([
            'success' => true,
            'data' => $customer->load(['orders', 'transactions'])
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'referral_code' => 'nullable|string',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil diupdate',
                'data' => $customer
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupdate customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function orders(Customer $customer)
    {
        $orders = $customer->orders()
            ->with(['service', 'partner'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function transactions(Customer $customer)
    {
        $transactions = $customer->transactions()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
} 