<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1) USERS: tambah doctor_id + role dokter_mitra
        |--------------------------------------------------------------------------
        | SAFE UPDATE:
        | - tidak ubah migration lama
        | - tambah kolom baru dengan nullable
        | - konversi ENUM role secara aman via SQL
        */

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'doctor_id')) {
                    $table->unsignedBigInteger('doctor_id')->nullable()->after('role');
                    $table->index('doctor_id', 'users_doctor_id_index');
                }
            });

            try {
                DB::statement("
                    ALTER TABLE users
                    MODIFY role ENUM('owner','admin','staff','dokter_mitra')
                    NOT NULL DEFAULT 'admin'
                ");
            } catch (\Throwable $e) {
                // Diamkan agar migration tidak langsung gagal
                // jika struktur DB tertentu sedikit berbeda.
                // Kolom doctor_id tetap bisa dibuat.
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2) DOCTOR NOTES
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('doctor_notes')) {
            Schema::create('doctor_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('income_transaction_id');
                $table->unsignedBigInteger('doctor_id');
                $table->text('note');
                $table->enum('status', ['active', 'done', 'archived'])->default('active');
                $table->timestamps();

                $table->index('income_transaction_id', 'doctor_notes_income_transaction_id_index');
                $table->index('doctor_id', 'doctor_notes_doctor_id_index');
                $table->index('status', 'doctor_notes_status_index');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 3) OWNER NOTIFICATIONS KHUSUS DOKTER MITRA
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('doctor_note_notifications')) {
            Schema::create('doctor_note_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('doctor_note_id');
                $table->unsignedBigInteger('income_transaction_id');
                $table->unsignedBigInteger('doctor_id');
                $table->unsignedBigInteger('owner_user_id')->nullable();
                $table->enum('status', ['unread', 'read'])->default('unread');
                $table->timestamps();

                $table->index('doctor_note_id', 'doctor_note_notifications_note_id_index');
                $table->index('income_transaction_id', 'doctor_note_notifications_trx_id_index');
                $table->index('doctor_id', 'doctor_note_notifications_doctor_id_index');
                $table->index('owner_user_id', 'doctor_note_notifications_owner_user_id_index');
                $table->index('status', 'doctor_note_notifications_status_index');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 4) FOREIGN KEY AMAN (dibungkus try-catch)
        |--------------------------------------------------------------------------
        */
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('doctor_id', 'users_doctor_id_foreign')
                    ->references('id')
                    ->on('doctors')
                    ->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // skip jika FK sudah ada / kondisi DB berbeda
        }

        try {
            Schema::table('doctor_notes', function (Blueprint $table) {
                $table->foreign('income_transaction_id', 'doctor_notes_income_transaction_id_foreign')
                    ->references('id')
                    ->on('income_transactions')
                    ->cascadeOnDelete();

                $table->foreign('doctor_id', 'doctor_notes_doctor_id_foreign')
                    ->references('id')
                    ->on('doctors')
                    ->cascadeOnDelete();
            });
        } catch (\Throwable $e) {
            // skip jika FK sudah ada / kondisi DB berbeda
        }

        try {
            Schema::table('doctor_note_notifications', function (Blueprint $table) {
                $table->foreign('doctor_note_id', 'doctor_note_notifications_note_id_foreign')
                    ->references('id')
                    ->on('doctor_notes')
                    ->cascadeOnDelete();

                $table->foreign('income_transaction_id', 'doctor_note_notifications_trx_id_foreign')
                    ->references('id')
                    ->on('income_transactions')
                    ->cascadeOnDelete();

                $table->foreign('doctor_id', 'doctor_note_notifications_doctor_id_foreign')
                    ->references('id')
                    ->on('doctors')
                    ->cascadeOnDelete();

                $table->foreign('owner_user_id', 'doctor_note_notifications_owner_user_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // skip jika FK sudah ada / kondisi DB berbeda
        }
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Hapus FK dulu jika ada
        |--------------------------------------------------------------------------
        */
        try {
            Schema::table('doctor_note_notifications', function (Blueprint $table) {
                $table->dropForeign('doctor_note_notifications_note_id_foreign');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('doctor_note_notifications', function (Blueprint $table) {
                $table->dropForeign('doctor_note_notifications_trx_id_foreign');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('doctor_note_notifications', function (Blueprint $table) {
                $table->dropForeign('doctor_note_notifications_doctor_id_foreign');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('doctor_note_notifications', function (Blueprint $table) {
                $table->dropForeign('doctor_note_notifications_owner_user_id_foreign');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('doctor_notes', function (Blueprint $table) {
                $table->dropForeign('doctor_notes_income_transaction_id_foreign');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('doctor_notes', function (Blueprint $table) {
                $table->dropForeign('doctor_notes_doctor_id_foreign');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign('users_doctor_id_foreign');
            });
        } catch (\Throwable $e) {}

        /*
        |--------------------------------------------------------------------------
        | Drop tables
        |--------------------------------------------------------------------------
        */
        if (Schema::hasTable('doctor_note_notifications')) {
            Schema::dropIfExists('doctor_note_notifications');
        }

        if (Schema::hasTable('doctor_notes')) {
            Schema::dropIfExists('doctor_notes');
        }

        /*
        |--------------------------------------------------------------------------
        | Kembalikan users
        |--------------------------------------------------------------------------
        */
        if (Schema::hasTable('users')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    if (Schema::hasColumn('users', 'doctor_id')) {
                        $table->dropIndex('users_doctor_id_index');
                        $table->dropColumn('doctor_id');
                    }
                });
            } catch (\Throwable $e) {
                // skip
            }

            try {
                DB::statement("
                    ALTER TABLE users
                    MODIFY role ENUM('owner','admin','staff')
                    NOT NULL DEFAULT 'admin'
                ");
            } catch (\Throwable $e) {
                // skip
            }
        }
    }
};