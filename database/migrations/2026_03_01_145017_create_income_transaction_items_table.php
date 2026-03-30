<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_transaction_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaction_id')
                ->constrained('income_transactions')
                ->onDelete('cascade');

            $table->foreignId('treatment_id')
                ->constrained('treatments');

            $table->decimal('qty', 15, 2)->default(1);

            $table->decimal('unit_price', 15, 2)->default(0);

            $table->decimal('subtotal', 15, 2)->default(0);

            $table->decimal('fee_amount', 15, 2)->default(0);

            $table->timestamps();

            $table->index('transaction_id');
            $table->index('treatment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_transaction_items');
    }
};