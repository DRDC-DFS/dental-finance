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
        Schema::table('owner_private_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('owner_private_transactions', 'category')) {
                $table->string('category', 100)->nullable()->after('type');
                $table->index('category');
            }

            if (!Schema::hasColumn('owner_private_transactions', 'source')) {
                $table->string('source', 255)->nullable()->after('category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owner_private_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('owner_private_transactions', 'category')) {
                $table->dropIndex(['category']);
                $table->dropColumn('category');
            }

            if (Schema::hasColumn('owner_private_transactions', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};