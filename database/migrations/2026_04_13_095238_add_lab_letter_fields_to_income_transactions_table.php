<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('income_transactions')) {
            return;
        }

        Schema::table('income_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('income_transactions', 'needs_lab_letter')) {
                $table->boolean('needs_lab_letter')->default(false)->after('receipt_pdf_path');
            }

            if (!Schema::hasColumn('income_transactions', 'lab_letter_number')) {
                $table->string('lab_letter_number', 100)->nullable()->after('needs_lab_letter');
            }

            if (!Schema::hasColumn('income_transactions', 'lab_letter_date')) {
                $table->date('lab_letter_date')->nullable()->after('lab_letter_number');
            }

            if (!Schema::hasColumn('income_transactions', 'lab_name')) {
                $table->string('lab_name', 150)->nullable()->after('lab_letter_date');
            }

            if (!Schema::hasColumn('income_transactions', 'lab_treatment_name')) {
                $table->string('lab_treatment_name', 150)->nullable()->after('lab_name');
            }

            if (!Schema::hasColumn('income_transactions', 'lab_material_shade')) {
                $table->string('lab_material_shade', 150)->nullable()->after('lab_treatment_name');
            }

            if (!Schema::hasColumn('income_transactions', 'lab_tooth_detail')) {
                $table->text('lab_tooth_detail')->nullable()->after('lab_material_shade');
            }

            if (!Schema::hasColumn('income_transactions', 'lab_instruction')) {
                $table->text('lab_instruction')->nullable()->after('lab_tooth_detail');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('income_transactions')) {
            return;
        }

        Schema::table('income_transactions', function (Blueprint $table) {
            $columns = [
                'needs_lab_letter',
                'lab_letter_number',
                'lab_letter_date',
                'lab_name',
                'lab_treatment_name',
                'lab_material_shade',
                'lab_tooth_detail',
                'lab_instruction',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('income_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};