<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            if (!Schema::hasColumn('treatments', 'allow_zero_price')) {
                $table->boolean('allow_zero_price')
                      ->default(false)
                      ->after('price_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            if (Schema::hasColumn('treatments', 'allow_zero_price')) {
                $table->dropColumn('allow_zero_price');
            }
        });
    }
};