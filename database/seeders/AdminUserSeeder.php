<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin1'],
            [
                'name' => 'Admin 1',
                'email' => 'admin1@example.com',
                'password' => Hash::make('Admin12345'),
                'role' => 'ADMIN',
                'is_active' => true,
            ]
        );
    }
}