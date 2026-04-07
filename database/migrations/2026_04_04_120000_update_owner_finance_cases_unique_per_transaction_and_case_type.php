<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('owner_finance_cases')) {
            return;
        }

        // 1) Drop unique lama pada income_transaction_id jika ada
        try {
            Schema::table('owner_finance_cases', function (Blueprint $table) {
                $table->dropUnique('owner_finance_cases_income_transaction_id_unique');
            });
        } catch (\Throwable $e) {
            // abaikan jika index tidak ada / nama berbeda
        }

        // 2) Bersihkan duplicate case_type per transaksi jika sempat ada
        //    Simpan ID terkecil, hapus sisanya agar unique baru aman dibuat.
        $duplicates = DB::table('owner_finance_cases')
            ->select('income_transaction_id', 'case_type', DB::raw('COUNT(*) as total'))
            ->groupBy('income_transaction_id', 'case_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $rows = DB::table('owner_finance_cases')
                ->where('income_transaction_id', $dup->income_transaction_id)
                ->where('case_type', $dup->case_type)
                ->orderBy('id', 'asc')
                ->pluck('id')
                ->all();

            $keepId = array_shift($rows);

            if (!empty($rows)) {
                DB::table('owner_finance_cases')
                    ->whereIn('id', $rows)
                    ->delete();
            }
        }

        // 3) Tambah unique baru: 1 transaksi boleh banyak case, tapi 1 case_type hanya sekali
        try {
            Schema::table('owner_finance_cases', function (Blueprint $table) {
                $table->unique(
                    ['income_transaction_id', 'case_type'],
                    'ofc_income_transaction_id_case_type_unique'
                );
            });
        } catch (\Throwable $e) {
            // abaikan jika sudah ada
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('owner_finance_cases')) {
            return;
        }

        try {
            Schema::table('owner_finance_cases', function (Blueprint $table) {
                $table->dropUnique('ofc_income_transaction_id_case_type_unique');
            });
        } catch (\Throwable $e) {
            // abaikan jika tidak ada
        }

        try {
            Schema::table('owner_finance_cases', function (Blueprint $table) {
                $table->unique(
                    ['income_transaction_id'],
                    'owner_finance_cases_income_transaction_id_unique'
                );
            });
        } catch (\Throwable $e) {
            // abaikan jika gagal restore
        }
    }
};