<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                  ->constrained('treatment_categories')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            $table->string('name',150);
            $table->decimal('price',15,2)->default(0);
            $table->string('unit',50)->default('1x');
            $table->tinyInteger('is_active')->default(1);

            $table->unique(['category_id','name']);
            $table->index('category_id');

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};