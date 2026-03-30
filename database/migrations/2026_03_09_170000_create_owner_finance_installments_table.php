<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_finance_installments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_finance_case_id')
                ->constrained('owner_finance_cases')
                ->cascadeOnDelete();

            $table->unsignedInteger('installment_no');
            $table->date('installment_date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index(['owner_finance_case_id', 'installment_no'], 'ofi_case_installment_idx');
            $table->index('installment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_finance_installments');
    }
};