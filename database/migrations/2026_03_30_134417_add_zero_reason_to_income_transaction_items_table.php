<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('income_transaction_items', function (Blueprint $table) {
            if (!Schema::hasColumn('income_transaction_items', 'zero_reason')) {
                $table->string('zero_reason')->nullable()->after('discount_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('income_transaction_items', function (Blueprint $table) {
            if (Schema::hasColumn('income_transaction_items', 'zero_reason')) {
                $table->dropColumn('zero_reason');
            }
        });
    }
};