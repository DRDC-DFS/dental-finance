<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('owner_private_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('trx_date');
            $table->enum('type', ['income', 'expense']);
            $table->string('description', 255);
            $table->enum('payment_method', ['TUNAI', 'BCA', 'BNI', 'BRI'])->default('TUNAI');
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index('trx_date');
            $table->index('type');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_private_transactions');
    }
};