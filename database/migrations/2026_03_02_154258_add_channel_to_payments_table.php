<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            $table->enum('channel', ['CASH','TRANSFER','EDC','QRIS'])
                ->default('CASH')
                ->after('payment_method_id');

            $table->index(['payment_method_id','channel']);

        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            $table->dropIndex(['payment_method_id','channel']);
            $table->dropColumn('channel');

        });
    }
};