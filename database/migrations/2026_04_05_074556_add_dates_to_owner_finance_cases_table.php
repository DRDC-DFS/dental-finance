<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_finance_cases', function (Blueprint $table) {
            $table->date('lab_paid_at')->nullable()->after('lab_paid');
            $table->date('installed_at')->nullable()->after('installed');
        });
    }

    public function down(): void
    {
        Schema::table('owner_finance_cases', function (Blueprint $table) {
            $table->dropColumn(['lab_paid_at', 'installed_at']);
        });
    }
};