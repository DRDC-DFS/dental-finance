<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = ['TUNAI','BCA','BNI','BRI'];

        foreach ($methods as $name) {
            DB::table('payment_methods')->updateOrInsert(
                ['name' => $name],
                [
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}