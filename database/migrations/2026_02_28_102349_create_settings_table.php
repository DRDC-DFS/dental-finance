<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            $table->string('clinic_name', 200);
            $table->text('clinic_address')->nullable();
            $table->string('clinic_phone', 50)->nullable();
            $table->string('owner_doctor_name', 150)->nullable();

            $table->string('logo_path', 255)->nullable();
            $table->string('login_background_path', 255)->nullable();
            $table->string('favicon_path', 255)->nullable();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};