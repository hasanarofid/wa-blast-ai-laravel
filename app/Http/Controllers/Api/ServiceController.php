<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::when($request->search, function($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->when($request->is_active, function($query, $isActive) {
                return $query->where('is_active', $isActive);
            })
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:services,code',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'price_per_km' => 'required|numeric|min:0',
            'minimum_price' => 'required|numeric|min:0',
            'settings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $service = Service::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Service berhasil dibuat',
                'data' => $service
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Service $service)
    {
        return response()->json([
            'success' => true,
            'data' => $service->load(['orders'])
        ]);
    }

    public function update(Request $request, Service $service)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
            'price_per_km' => 'sometimes|numeric|min:0',
            'minimum_price' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'settings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $service->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Service berhasil diupdate',
                'data' => $service
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupdate service',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 