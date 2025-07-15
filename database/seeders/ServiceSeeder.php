<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run()
    {
        $services = [
            [
                'name' => 'Ojek',
                'code' => 'ojek',
                'description' => 'Layanan antar jemput dengan motor',
                'base_price' => 10000,
                'price_per_km' => 2000,
                'minimum_price' => 10000,
                'is_active' => true,
                'settings' => [
                    'vehicle_type' => 'motor',
                    'max_passengers' => 1
                ]
            ],
            [
                'name' => 'Pengantaran',
                'code' => 'pengantaran',
                'description' => 'Layanan pengiriman makanan dan barang',
                'base_price' => 15000,
                'price_per_km' => 2500,
                'minimum_price' => 15000,
                'is_active' => true,
                'settings' => [
                    'vehicle_type' => 'motor',
                    'max_weight' => 10
                ]
            ],
            [
                'name' => 'Jasa Belanja',
                'code' => 'belanja',
                'description' => 'Layanan belanja dan antar',
                'base_price' => 20000,
                'price_per_km' => 3000,
                'minimum_price' => 20000,
                'is_active' => true,
                'settings' => [
                    'vehicle_type' => 'motor',
                    'shopping_fee' => 5000
                ]
            ],
            [
                'name' => 'Jasa Panggilan',
                'code' => 'panggilan',
                'description' => 'Layanan tukang, pijat, dan service panggilan',
                'base_price' => 50000,
                'price_per_km' => 5000,
                'minimum_price' => 50000,
                'is_active' => true,
                'settings' => [
                    'service_types' => ['tukang', 'pijat', 'service'],
                    'duration' => 60
                ]
            ]
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['code' => $service['code']],
                $service
            );
        }
    }
} 