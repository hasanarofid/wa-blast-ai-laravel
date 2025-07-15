<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\User;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'pelanggan1@gmail.com')->first();

        Customer::create([
            'user_id' => $user->id,
            'whatsapp_number' => '6281234567890',
            'name' => 'Pelanggan Satu',
            'address' => 'Jl. Mawar No. 1, Jakarta',
            'latitude' => -6.20000000,
            'longitude' => 106.81666600,
            'referral_code' => 'REF123',
            'balance' => 100000,
            'is_active' => true,
        ]);


    }
}
