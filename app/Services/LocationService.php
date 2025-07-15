<?php

namespace App\Services;

class LocationService
{
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Formula Haversine untuk menghitung jarak antara dua titik koordinat
        $earthRadius = 6371; // Radius bumi dalam kilometer

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function getAddressFromCoordinates(float $latitude, float $longitude): ?string
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'json',
                'addressdetails' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['display_name'] ?? null;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting address from coordinates', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    public function getCoordinatesFromAddress(string $address): ?array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data)) {
                    return [
                        'latitude' => (float) $data[0]['lat'],
                        'longitude' => (float) $data[0]['lon'],
                        'display_name' => $data[0]['display_name']
                    ];
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting coordinates from address', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    public function isWithinServiceArea(float $latitude, float $longitude): bool
    {
        // Definisikan area layanan (contoh: Jakarta)
        $serviceArea = [
            'min_lat' => -6.5,
            'max_lat' => -5.5,
            'min_lon' => 106.5,
            'max_lon' => 107.5
        ];

        return $latitude >= $serviceArea['min_lat'] &&
               $latitude <= $serviceArea['max_lat'] &&
               $longitude >= $serviceArea['min_lon'] &&
               $longitude <= $serviceArea['max_lon'];
    }

    public function findNearestPartners(float $latitude, float $longitude, string $serviceType, int $limit = 5): array
    {
        // Query untuk mencari partner terdekat
        $partners = \App\Models\Partner::where('status', 'active')
            ->where('is_online', true)
            ->whereJsonContains('service_types', $serviceType)
            ->get();

        $partnersWithDistance = [];
        foreach ($partners as $partner) {
            if ($partner->latitude && $partner->longitude) {
                $distance = $this->calculateDistance(
                    $latitude, $longitude,
                    $partner->latitude, $partner->longitude
                );

                $partnersWithDistance[] = [
                    'partner' => $partner,
                    'distance' => $distance
                ];
            }
        }

        // Urutkan berdasarkan jarak
        usort($partnersWithDistance, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        return array_slice($partnersWithDistance, 0, $limit);
    }
} 