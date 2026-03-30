<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('item_id');
                $table->enum('type', ['IN','OUT','ADJUSTMENT']);
                $table->decimal('qty', 15, 2)->default(0);
                $table->date('date');
                $table->string('reference', 120)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->timestamps();

                $table->index('item_id');
                $table->index('date');
                $table->index('type');

                $table->foreign('item_id')->references('id')->on('inventory_items');
                $table->foreign('created_by')->references('id')->on('users');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};