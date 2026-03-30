<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_incomes', function (Blueprint $table) {

            // sumber / jenis pemasukan
            $table->string('source_type', 100)->nullable()->after('title');

            // metode pembayaran
            $table->enum('payment_method', ['cash', 'bank'])
                ->default('cash')
                ->after('amount');

            // channel bank (transfer, qris, edc)
            $table->string('payment_channel', 50)
                ->nullable()
                ->after('payment_method');

            // masuk laporan admin harian?
            $table->boolean('include_in_report')
                ->default(true)
                ->after('visibility');

            // masuk perhitungan net setoran?
            $table->boolean('include_in_cashflow')
                ->default(true)
                ->after('include_in_report');
        });
    }

    public function down(): void
    {
        Schema::table('other_incomes', function (Blueprint $table) {

            $table->dropColumn([
                'source_type',
                'payment_method',
                'payment_channel',
                'include_in_report',
                'include_in_cashflow',
            ]);
        });
    }
};