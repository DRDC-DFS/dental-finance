<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {

            if (!Schema::hasColumn('treatments', 'is_ortho_related')) {
                $table->boolean('is_ortho_related')
                    ->default(false)
                    ->after('is_free');
            }

            if (!Schema::hasColumn('treatments', 'is_prosto_related')) {
                $table->boolean('is_prosto_related')
                    ->default(false)
                    ->after('is_ortho_related');
            }

        });
    }

    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {

            if (Schema::hasColumn('treatments', 'is_prosto_related')) {
                $table->dropColumn('is_prosto_related');
            }

            if (Schema::hasColumn('treatments', 'is_ortho_related')) {
                $table->dropColumn('is_ortho_related');
            }

        });
    }
};