<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'owner'],
            [
                'name' => 'Owner',
                'username' => 'owner',
                'email' => 'owner@gmail.com',
                'password' => Hash::make('12345678'),
                'role' => 'owner',
            ]
        );
    }
}