<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_finance_cases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('income_transaction_id')
                ->unique()
                ->constrained('income_transactions')
                ->cascadeOnDelete();

            $table->string('case_type', 30); // prostodonti | ortho | retainer

            $table->boolean('lab_paid')->default(false);
            $table->boolean('installed')->default(false);

            $table->text('owner_private_notes')->nullable();

            // khusus ORTHO
            $table->decimal('ortho_allocation_amount', 15, 2)->default(0);
            $table->string('ortho_payment_mode', 20)->nullable(); // full | installments
            $table->unsignedInteger('ortho_installment_count')->nullable();
            $table->decimal('ortho_paid_amount', 15, 2)->default(0);
            $table->decimal('ortho_remaining_balance', 15, 2)->default(0);

            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index(['case_type', 'lab_paid', 'installed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_finance_cases');
    }
};