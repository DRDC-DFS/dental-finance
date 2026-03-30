<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_incomes', function (Blueprint $table) {
            $table->id();

            $table->date('trx_date');

            $table->string('title', 150);

            $table->decimal('amount', 15, 2)->default(0);

            $table->text('notes')->nullable();

            $table->enum('visibility', ['public', 'private'])
                ->default('public');

            $table->foreignId('created_by')
                ->constrained('users');

            $table->timestamps();

            $table->index('trx_date');
            $table->index('visibility');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_incomes');
    }
};