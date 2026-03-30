<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_finance_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('owner_finance_cases', 'prostho_case_type')) {
                $table->string('prostho_case_type', 50)
                    ->nullable()
                    ->after('installed');
            }

            if (!Schema::hasColumn('owner_finance_cases', 'prostho_case_detail')) {
                $table->text('prostho_case_detail')
                    ->nullable()
                    ->after('prostho_case_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('owner_finance_cases', function (Blueprint $table) {
            if (Schema::hasColumn('owner_finance_cases', 'prostho_case_detail')) {
                $table->dropColumn('prostho_case_detail');
            }

            if (Schema::hasColumn('owner_finance_cases', 'prostho_case_type')) {
                $table->dropColumn('prostho_case_type');
            }
        });
    }
};