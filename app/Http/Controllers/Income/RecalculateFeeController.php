<?php

namespace App\Http\Controllers\Income;

use App\Http\Controllers\Controller;
use App\Models\IncomeTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecalculateFeeController extends Controller
{
    private function ensureOwner(Request $request): void
    {
        $role = strtolower((string) ($request->user()->role ?? ''));
        if ($role !== 'owner') {
            abort(403, 'Hanya OWNER yang boleh melakukan aksi ini.');
        }
    }

    public function show(Request $request, IncomeTransaction $incomeTransaction)
    {
        $this->ensureOwner($request);

        $incomeTransaction->load(['doctor', 'patient']);

        $items = DB::table('income_transaction_items as i')
            ->join('treatments as tr', 'tr.id', '=', 'i.treatment_id')
            ->where('i.transaction_id', (int) $incomeTransaction->id)
            ->orderBy('i.id', 'asc')
            ->get([
                'i.id',
                'tr.name as treatment_name',
                'i.qty',
                'i.unit_price',
                'i.subtotal',
                'i.fee_amount',
            ]);

        return view('income.recalculate_fee', [
            'trx'   => $incomeTransaction,
            'items' => $items,
        ]);
    }

    public function run(Request $request, IncomeTransaction $incomeTransaction)
    {
        $this->ensureOwner($request);

        return DB::transaction(function () use ($incomeTransaction) {

            // Ambil dokter
            $doctor = DB::table('doctors')
                ->select('id', 'type', 'default_fee_percent')
                ->where('id', (int) $incomeTransaction->doctor_id)
                ->first();

            if (!$doctor) {
                return redirect()
                    ->route('income.edit', $incomeTransaction->id)
                    ->withErrors(['fee' => 'Dokter tidak ditemukan.']);
            }

            $doctorType = strtolower((string) ($doctor->type ?? ''));
            $defaultPercent = (float) ($doctor->default_fee_percent ?? 0);

            // Items transaksi
            $items = DB::table('income_transaction_items')
                ->where('transaction_id', (int) $incomeTransaction->id)
                ->get(['id', 'treatment_id', 'subtotal', 'fee_amount']);

            foreach ($items as $it) {

                // OWNER selalu 0
                if ($doctorType === 'owner') {
                    DB::table('income_transaction_items')
                        ->where('id', (int) $it->id)
                        ->update(['fee_amount' => 0]);
                    continue;
                }

                // cari mapping fee khusus dokter+tindakan
                $feeRow = DB::table('doctor_treatment_fees')
                    ->where('doctor_id', (int) $incomeTransaction->doctor_id)
                    ->where('treatment_id', (int) $it->treatment_id)
                    ->first(['fee_type', 'fee_value']);

                // default: pakai mapping jika ada
                if ($feeRow) {
                    $feeType = strtolower((string) ($feeRow->fee_type ?? 'percent'));
                    $feeVal  = (float) ($feeRow->fee_value ?? 0);

                    // MANUAL: jangan override (biarkan fee_amount existing)
                    if ($feeType === 'manual') {
                        continue;
                    }

                    if ($feeType === 'fixed') {
                        DB::table('income_transaction_items')
                            ->where('id', (int) $it->id)
                            ->update(['fee_amount' => round(max(0, $feeVal), 2)]);
                        continue;
                    }

                    // percent
                    $fee = 0;
                    if ($feeVal > 0) {
                        $fee = (float) $it->subtotal * ($feeVal / 100);
                    }

                    DB::table('income_transaction_items')
                        ->where('id', (int) $it->id)
                        ->update(['fee_amount' => round(max(0, $fee), 2)]);

                    continue;
                }

                // fallback: default fee percent dokter
                if ($defaultPercent > 0) {
                    $fee = (float) $it->subtotal * ($defaultPercent / 100);

                    DB::table('income_transaction_items')
                        ->where('id', (int) $it->id)
                        ->update(['fee_amount' => round(max(0, $fee), 2)]);
                } else {
                    DB::table('income_transaction_items')
                        ->where('id', (int) $it->id)
                        ->update(['fee_amount' => 0]);
                }
            }

            // Recalc totals
            $billTotal = (float) DB::table('income_transaction_items')
                ->where('transaction_id', (int) $incomeTransaction->id)
                ->sum('subtotal');

            $doctorFeeTotal = (float) DB::table('income_transaction_items')
                ->where('transaction_id', (int) $incomeTransaction->id)
                ->sum('fee_amount');

            DB::table('income_transactions')
                ->where('id', (int) $incomeTransaction->id)
                ->update([
                    'bill_total'       => $billTotal,
                    'doctor_fee_total' => $doctorFeeTotal,
                ]);

            return redirect()
                ->route('income.recalculate_fee.show', $incomeTransaction->id)
                ->with('success', 'Fee berhasil dihitung ulang untuk transaksi ini.');
        });
    }
}