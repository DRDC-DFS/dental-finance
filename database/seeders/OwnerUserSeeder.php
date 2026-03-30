<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'owner'],
            [
                'name' => 'Owner',
                'email' => 'owner@example.com',
                'password' => Hash::make('Owner12345'),
                'role' => 'OWNER',
                'is_active' => true,
            ]
        );
    }
}