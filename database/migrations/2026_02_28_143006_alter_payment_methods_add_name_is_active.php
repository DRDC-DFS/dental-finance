<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tambah kolom yang hilang
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->enum('name', ['TUNAI','BCA','BNI','BRI'])->nullable()->unique()->after('id');
            $table->tinyInteger('is_active')->default(1)->after('name');
        });

        // 2) Seed default agar tidak ada NULL pada name
        DB::table('payment_methods')->updateOrInsert(
            ['name' => 'TUNAI'],
            ['is_active' => 1, 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('payment_methods')->updateOrInsert(
            ['name' => 'BCA'],
            ['is_active' => 1, 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('payment_methods')->updateOrInsert(
            ['name' => 'BNI'],
            ['is_active' => 1, 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('payment_methods')->updateOrInsert(
            ['name' => 'BRI'],
            ['is_active' => 1, 'created_at' => now(), 'updated_at' => now()]
        );

        // 3) Kunci NOT NULL sesuai schema final
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->enum('name', ['TUNAI','BCA','BNI','BRI'])->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn(['name', 'is_active']);
        });
    }
};