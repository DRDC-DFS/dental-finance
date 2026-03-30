<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('income_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('income_transactions', 'ortho_case_mode')) {
                $table->string('ortho_case_mode', 20)
                    ->default('none')
                    ->after('payer_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('income_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('income_transactions', 'ortho_case_mode')) {
                $table->dropColumn('ortho_case_mode');
            }
        });
    }
};