<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_number', 40)->unique();

            $table->date('trx_date');

            $table->foreignId('doctor_id')
                ->constrained('doctors');

            $table->foreignId('patient_id')
                ->nullable()
                ->constrained('patients');

            $table->enum('status', ['draft','paid','cancelled','void'])
                ->default('paid');

            $table->decimal('bill_total', 15, 2)->default(0);

            $table->decimal('doctor_fee_total', 15, 2)->default(0);

            $table->decimal('pay_total', 15, 2)->default(0);

            $table->enum('visibility', ['public','private'])
                ->default('public');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->constrained('users');

            $table->string('receipt_verify_code', 80)
                ->nullable()
                ->unique();

            $table->string('receipt_pdf_path')
                ->nullable();

            $table->timestamps();

            $table->index('trx_date');
            $table->index('doctor_id');
            $table->index('patient_id');
            $table->index('status');
            $table->index('visibility');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_transactions');
    }
};