<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_finance_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('owner_finance_cases', 'lab_bill_amount')) {
                $table->decimal('lab_bill_amount', 15, 2)
                    ->default(0)
                    ->after('prostho_case_detail');
            }

            if (!Schema::hasColumn('owner_finance_cases', 'clinic_income_amount')) {
                $table->decimal('clinic_income_amount', 15, 2)
                    ->default(0)
                    ->after('lab_bill_amount');
            }

            if (!Schema::hasColumn('owner_finance_cases', 'revenue_recognized_at')) {
                $table->dateTime('revenue_recognized_at')
                    ->nullable()
                    ->after('clinic_income_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('owner_finance_cases', function (Blueprint $table) {
            if (Schema::hasColumn('owner_finance_cases', 'revenue_recognized_at')) {
                $table->dropColumn('revenue_recognized_at');
            }

            if (Schema::hasColumn('owner_finance_cases', 'clinic_income_amount')) {
                $table->dropColumn('clinic_income_amount');
            }

            if (Schema::hasColumn('owner_finance_cases', 'lab_bill_amount')) {
                $table->dropColumn('lab_bill_amount');
            }
        });
    }
};