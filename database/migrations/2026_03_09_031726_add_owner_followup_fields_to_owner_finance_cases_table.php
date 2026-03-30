<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_finance_cases', function (Blueprint $table) {
            if (!Schema::hasColumn('owner_finance_cases', 'needs_setup')) {
                $table->boolean('needs_setup')
                    ->default(true)
                    ->after('ortho_remaining_balance');
            }

            if (!Schema::hasColumn('owner_finance_cases', 'owner_followup_status')) {
                $table->string('owner_followup_status', 30)
                    ->nullable()
                    ->after('needs_setup');
            }

            if (!Schema::hasColumn('owner_finance_cases', 'case_progress_status')) {
                $table->string('case_progress_status', 50)
                    ->nullable()
                    ->after('owner_followup_status');
            }

            if (!Schema::hasColumn('owner_finance_cases', 'owner_last_action_note')) {
                $table->string('owner_last_action_note', 255)
                    ->nullable()
                    ->after('case_progress_status');
            }

            if (!Schema::hasColumn('owner_finance_cases', 'owner_last_action_at')) {
                $table->dateTime('owner_last_action_at')
                    ->nullable()
                    ->after('owner_last_action_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('owner_finance_cases', function (Blueprint $table) {
            $dropColumns = [];

            foreach ([
                'owner_last_action_at',
                'owner_last_action_note',
                'case_progress_status',
                'owner_followup_status',
                'needs_setup',
            ] as $column) {
                if (Schema::hasColumn('owner_finance_cases', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};