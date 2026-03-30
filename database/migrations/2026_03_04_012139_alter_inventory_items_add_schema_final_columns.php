<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {

            if (!Schema::hasColumn('inventory_items', 'name')) {
                $table->string('name', 150)->unique()->after('id');
            }

            if (!Schema::hasColumn('inventory_items', 'unit')) {
                $table->string('unit', 50)->default('pcs')->after('name');
            }

            if (!Schema::hasColumn('inventory_items', 'minimum_stock')) {
                $table->decimal('minimum_stock', 15, 2)->default(0)->after('unit');
            }

            if (!Schema::hasColumn('inventory_items', 'is_active')) {
                $table->tinyInteger('is_active')->default(1)->after('minimum_stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('inventory_items', 'minimum_stock')) {
                $table->dropColumn('minimum_stock');
            }
            if (Schema::hasColumn('inventory_items', 'unit')) {
                $table->dropColumn('unit');
            }
            if (Schema::hasColumn('inventory_items', 'name')) {
                // drop unique index otomatis dari kolom name (Laravel biasanya nama index: inventory_items_name_unique)
                try { $table->dropUnique('inventory_items_name_unique'); } catch (\Throwable $e) {}
                $table->dropColumn('name');
            }
        });
    }
};