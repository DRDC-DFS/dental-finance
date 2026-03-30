<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // jika tabel sudah ada, jangan buat lagi
        if (!Schema::hasTable('user_temp_passwords')) {

            Schema::create('user_temp_passwords', function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger('user_id')->unique();

                // password sementara (encrypted)
                $table->text('temp_password_enc')->nullable();

                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });

        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_temp_passwords');
    }
};