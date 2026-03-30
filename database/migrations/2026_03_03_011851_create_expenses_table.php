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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->date('expense_date')->index();
            $table->string('name', 255);

            $table->enum('pay_method', ['TUNAI', 'BCA', 'BNI', 'BRI'])->index();
            $table->decimal('amount', 15, 2)->default(0);

            $table->boolean('is_private')->default(false)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();

            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};