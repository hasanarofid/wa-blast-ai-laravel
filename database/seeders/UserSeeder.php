<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Master Admin',
            'email' => 'master@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'master'
        ]);

        User::create([
            'name' => 'Admin Kota',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        User::create([
            'name' => 'Customer Service',
            'email' => 'cs@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'cs'
        ]);
    }
}
