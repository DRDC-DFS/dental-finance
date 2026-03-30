<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            if (!Schema::hasColumn('treatments', 'price_mode')) {
                $table->string('price_mode', 20)
                    ->default('fixed')
                    ->after('price');
            }

            if (!Schema::hasColumn('treatments', 'notes_hint')) {
                $table->text('notes_hint')
                    ->nullable()
                    ->after('unit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            if (Schema::hasColumn('treatments', 'notes_hint')) {
                $table->dropColumn('notes_hint');
            }

            if (Schema::hasColumn('treatments', 'price_mode')) {
                $table->dropColumn('price_mode');
            }
        });
    }
};