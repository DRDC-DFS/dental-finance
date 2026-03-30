<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('income_transactions', function (Blueprint $table) {
            $table->string('payer_type', 20)
                ->default('umum')
                ->after('patient_id');
        });

        DB::table('income_transactions')
            ->whereNull('payer_type')
            ->update(['payer_type' => 'umum']);
    }

    public function down(): void
    {
        Schema::table('income_transactions', function (Blueprint $table) {
            $table->dropColumn('payer_type');
        });
    }
};