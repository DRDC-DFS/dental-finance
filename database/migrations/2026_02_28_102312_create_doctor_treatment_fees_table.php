<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_treatment_fees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_id')
                ->constrained('doctors')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('treatment_id')
                ->constrained('treatments')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('fee_type', ['percent','fixed','manual'])->default('percent');
            $table->decimal('fee_value', 15, 2)->nullable();

            $table->unique(['doctor_id', 'treatment_id']);
            $table->index('doctor_id');
            $table->index('treatment_id');

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_treatment_fees');
    }
};