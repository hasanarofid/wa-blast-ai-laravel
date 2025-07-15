<?php

namespace App\Services;

use App\Models\Service;

class PricingService
{
    public function calculatePrice(string $serviceType, float $distanceKm): float
    {
        $service = Service::where('code', $serviceType)->first();
        
        if (!$service) {
            // Default pricing jika service tidak ditemukan
            return $this->getDefaultPrice($serviceType, $distanceKm);
        }

        $basePrice = $service->base_price;
        $pricePerKm = $service->price_per_km;
        $minimumPrice = $service->minimum_price;

        $totalPrice = $basePrice + ($distanceKm * $pricePerKm);
        
        return max($totalPrice, $minimumPrice);
    }

    protected function getDefaultPrice(string $serviceType, float $distanceKm): float
    {
        $pricing = [
            'ojek' => [
                'base' => 10000,
                'per_km' => 2000,
                'minimum' => 10000
            ],
            'pengantaran' => [
                'base' => 15000,
                'per_km' => 2500,
                'minimum' => 15000
            ],
            'belanja' => [
                'base' => 20000,
                'per_km' => 3000,
                'minimum' => 20000
            ],
            'panggilan' => [
                'base' => 50000,
                'per_km' => 5000,
                'minimum' => 50000
            ]
        ];

        if (isset($pricing[$serviceType])) {
            $price = $pricing[$serviceType];
            $totalPrice = $price['base'] + ($distanceKm * $price['per_km']);
            return max($totalPrice, $price['minimum']);
        }

        // Default pricing
        return 15000 + ($distanceKm * 2000);
    }

    public function calculateCommission(float $totalPrice, string $serviceType = 'default'): float
    {
        $commissionRates = [
            'ojek' => 0.15, // 15%
            'pengantaran' => 0.20, // 20%
            'belanja' => 0.25, // 25%
            'panggilan' => 0.30, // 30%
            'default' => 0.20 // 20%
        ];

        $rate = $commissionRates[$serviceType] ?? $commissionRates['default'];
        return $totalPrice * $rate;
    }

    public function calculatePartnerEarnings(float $totalPrice, float $commission): float
    {
        return $totalPrice - $commission;
    }
} 