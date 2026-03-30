<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * USERS — sesuai DATABASE_SCHEMA_FINAL.txt
         */
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED PK AI
            $table->string('name', 150);
            $table->string('email', 191)->unique();
            $table->string('password', 255);

            $table->enum('role', ['owner','admin','staff'])->default('admin');
            $table->tinyInteger('is_active')->default(1);

            // timestamp NULL sesuai schema final
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        /**
         * Laravel default tables (dipakai untuk reset password & session)
         * Boleh tetap ada (tidak mengganggu schema final sistem keuangan)
         */
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};