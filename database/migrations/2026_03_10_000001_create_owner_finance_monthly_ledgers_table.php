<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_finance_monthly_ledgers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_finance_case_id')
                ->constrained('owner_finance_cases')
                ->cascadeOnDelete();

            // simpan selalu tanggal awal bulan, contoh: 2026-02-01
            $table->date('ledger_month');

            // opening = saldo awal bulan / pemasukan owner finance bulan itu
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('income_amount', 15, 2)->default(0);

            // total cicilan owner yang benar-benar diinput pada bulan itu
            $table->decimal('installment_paid', 15, 2)->default(0);

            // pengeluaran owner finance di akhir bulan
            $table->decimal('expense_end_month', 15, 2)->default(0);

            // saldo akhir bulan / carry forward
            $table->decimal('closing_balance', 15, 2)->default(0);

            // penanda kasus selesai pada bulan ini
            $table->boolean('is_closed')->default(false);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['owner_finance_case_id', 'ledger_month'],
                'ofml_case_month_unique'
            );

            $table->index('ledger_month', 'ofml_ledger_month_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_finance_monthly_ledgers');
    }
};