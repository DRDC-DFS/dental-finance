<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('income_transactions')) {
            return;
        }

        if (!Schema::hasColumn('income_transactions', 'prosto_case_mode')) {
            Schema::table('income_transactions', function (Blueprint $table) {
                $table->string('prosto_case_mode', 20)
                    ->default('none')
                    ->after('ortho_case_mode');
            });
        }

        DB::table('income_transactions')
            ->whereNull('prosto_case_mode')
            ->update([
                'prosto_case_mode' => 'none',
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('income_transactions')) {
            return;
        }

        if (Schema::hasColumn('income_transactions', 'prosto_case_mode')) {
            Schema::table('income_transactions', function (Blueprint $table) {
                $table->dropColumn('prosto_case_mode');
            });
        }
    }
};