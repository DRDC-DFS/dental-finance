<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaction_id')
                ->constrained('income_transactions')
                ->onDelete('cascade');

            $table->foreignId('payment_method_id')
                ->constrained('payment_methods');

            $table->decimal('amount', 15, 2)->default(0);

            $table->date('pay_date');

            $table->timestamps();

            $table->index('transaction_id');
            $table->index('payment_method_id');
            $table->index('pay_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};