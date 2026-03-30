<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_account_mutations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_finance_case_id')
                ->nullable()
                ->constrained('owner_finance_cases')
                ->nullOnDelete();

            $table->foreignId('owner_finance_monthly_ledger_id')
                ->nullable()
                ->constrained('owner_finance_monthly_ledgers')
                ->nullOnDelete();

            $table->date('mutation_date');
            $table->string('mutation_type', 20); // pemasukan | pengeluaran
            $table->string('source_type', 50)->default('owner_finance_ortho');
            $table->string('description', 255);
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('reference_month')->nullable();
            $table->boolean('is_system_generated')->default(true);

            $table->timestamps();

            $table->index('mutation_date', 'oam_mutation_date_idx');
            $table->index(['mutation_type', 'source_type'], 'oam_type_source_idx');
            $table->index(['owner_finance_case_id', 'reference_month'], 'oam_case_month_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_account_mutations');
    }
};