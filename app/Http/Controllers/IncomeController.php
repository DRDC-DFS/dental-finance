<?php

namespace App\Http\Controllers;

use App\Models\IncomeTransaction;
use App\Models\Doctor;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IncomeController extends Controller
{
    public function index()
    {
        $rows = IncomeTransaction::with(['doctor', 'items.treatment'])
            ->orderByDesc('trx_date')
            ->orderByDesc('id')
            ->get();

        return view('income.index', compact('rows'));
    }

    public function create()
    {
        $doctors = Doctor::where('is_active', true)->orderBy('name')->get();
        $treatments = Treatment::where('is_active', true)->orderBy('name')->get();

        return view('income.create', compact('doctors', 'treatments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'trx_date' => ['required', 'date'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'patient_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:LUNAS,BELUM_BAYAR'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.treatment_id' => ['required', 'exists:treatments,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],

            // unit_price TIDAK dipakai lagi (harga dipaksa dari master), tapi biarkan nullable agar form aman
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],

            'pay_tunai' => ['nullable', 'numeric', 'min:0'],
            'pay_bca' => ['nullable', 'numeric', 'min:0'],
            'pay_bni' => ['nullable', 'numeric', 'min:0'],
            'pay_bri' => ['nullable', 'numeric', 'min:0'],
        ]);

        $doctor = Doctor::findOrFail($data['doctor_id']);
        $isMitra = ($doctor->type === 'MITRA');

        // IMPORTANT: bill_total + fee dihitung server-side, harga ambil dari master
        [$itemsToInsert, $billTotal] = $this->buildItemsAndTotalWithFee($data['items'], $isMitra);

        $status = $data['status'];

        $payTunai = (float) ($data['pay_tunai'] ?? 0);
        $payBca   = (float) ($data['pay_bca'] ?? 0);
        $payBni   = (float) ($data['pay_bni'] ?? 0);
        $payBri   = (float) ($data['pay_bri'] ?? 0);

        if ($status === 'BELUM_BAYAR') {
            $payTunai = $payBca = $payBni = $payBri = 0;
        }

        $payTotal = $payTunai + $payBca + $payBni + $payBri;

        if ($status === 'LUNAS') {
            if (abs($payTotal - $billTotal) > 0.01) {
                throw ValidationException::withMessages([
                    'pay_tunai' => 'Total pembayaran harus sama dengan total tagihan.',
                ]);
            }
        } else {
            $payTotal = 0;
        }

        DB::transaction(function () use ($request, $data, $billTotal, $payTunai, $payBca, $payBni, $payBri, $payTotal, $itemsToInsert) {
            $trx = IncomeTransaction::create([
                'trx_date' => $data['trx_date'],
                'patient_name' => $data['patient_name'],
                'doctor_id' => $data['doctor_id'],
                'status' => $data['status'],
                'is_private' => false,

                'bill_total' => $billTotal,

                // fee tamu manual default 0 (diisi owner kalau dokter TAMU)
                'doctor_fee_manual' => 0,

                'pay_tunai' => $payTunai,
                'pay_bca' => $payBca,
                'pay_bni' => $payBni,
                'pay_bri' => $payBri,
                'pay_total' => $payTotal,

                'created_by' => $request->user()->id,
            ]);

            foreach ($itemsToInsert as $row) {
                $trx->items()->create($row);
            }
        });

        return redirect()->route('income.index')->with('success', 'Pemasukan berhasil disimpan.');
    }

    /** Owner-only: Edit hanya untuk BELUM_BAYAR */
    public function edit(Request $request, IncomeTransaction $incomeTransaction)
    {
        if ($incomeTransaction->status !== 'BELUM_BAYAR') abort(403);

        $incomeTransaction->load(['items.treatment', 'doctor']);

        $doctors = Doctor::where('is_active', true)->orderBy('name')->get();
        $treatments = Treatment::where('is_active', true)->orderBy('name')->get();

        return view('income.edit', [
            'trx' => $incomeTransaction,
            'doctors' => $doctors,
            'treatments' => $treatments,
        ]);
    }

    /** Owner-only: Update hanya untuk BELUM_BAYAR */
    public function update(Request $request, IncomeTransaction $incomeTransaction)
    {
        if ($incomeTransaction->status !== 'BELUM_BAYAR') abort(403);

        $data = $request->validate([
            'trx_date' => ['required', 'date'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'patient_name' => ['required', 'string', 'max:255'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.treatment_id' => ['required', 'exists:treatments,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],

            // unit_price TIDAK dipakai lagi (harga dipaksa dari master)
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $doctor = Doctor::findOrFail($data['doctor_id']);
        $isMitra = ($doctor->type === 'MITRA');

        // IMPORTANT: bill_total + fee dihitung server-side, harga ambil dari master
        [$itemsToInsert, $billTotal] = $this->buildItemsAndTotalWithFee($data['items'], $isMitra);

        DB::transaction(function () use ($incomeTransaction, $data, $itemsToInsert, $billTotal) {
            $incomeTransaction->items()->delete();

            $incomeTransaction->update([
                'trx_date' => $data['trx_date'],
                'patient_name' => $data['patient_name'],
                'doctor_id' => $data['doctor_id'],

                'status' => 'BELUM_BAYAR',

                'bill_total' => $billTotal,

                'pay_tunai' => 0,
                'pay_bca' => 0,
                'pay_bni' => 0,
                'pay_bri' => 0,
                'pay_total' => 0,

                'doctor_fee_manual' => 0,
            ]);

            foreach ($itemsToInsert as $row) {
                $incomeTransaction->items()->create($row);
            }
        });

        return redirect()->route('income.index')->with('success', 'Pemasukan (Belum Bayar) berhasil diupdate.');
    }

    public function destroy(Request $request, IncomeTransaction $incomeTransaction)
    {
        $incomeTransaction->delete();
        return redirect()->route('income.index')->with('success', 'Pemasukan berhasil dihapus.');
    }

    public function lunasiForm(IncomeTransaction $incomeTransaction)
    {
        if ($incomeTransaction->status !== 'BELUM_BAYAR') abort(403);

        $incomeTransaction->load(['doctor', 'items.treatment']);

        return view('income.lunasi', [
            'trx' => $incomeTransaction,
        ]);
    }

    public function lunasiStore(Request $request, IncomeTransaction $incomeTransaction)
    {
        if ($incomeTransaction->status !== 'BELUM_BAYAR') abort(403);

        $data = $request->validate([
            'pay_tunai' => ['nullable', 'numeric', 'min:0'],
            'pay_bca' => ['nullable', 'numeric', 'min:0'],
            'pay_bni' => ['nullable', 'numeric', 'min:0'],
            'pay_bri' => ['nullable', 'numeric', 'min:0'],
        ]);

        $payTunai = (float) ($data['pay_tunai'] ?? 0);
        $payBca   = (float) ($data['pay_bca'] ?? 0);
        $payBni   = (float) ($data['pay_bni'] ?? 0);
        $payBri   = (float) ($data['pay_bri'] ?? 0);

        $payTotal = $payTunai + $payBca + $payBni + $payBri;

        if (abs($payTotal - (float) $incomeTransaction->bill_total) > 0.01) {
            throw ValidationException::withMessages([
                'pay_tunai' => 'Total pembayaran harus sama dengan total tagihan.',
            ]);
        }

        $incomeTransaction->update([
            'status' => 'LUNAS',
            'pay_tunai' => $payTunai,
            'pay_bca' => $payBca,
            'pay_bni' => $payBni,
            'pay_bri' => $payBri,
            'pay_total' => $payTotal,
        ]);

        return redirect()->route('income.index')->with('success', 'Transaksi berhasil dilunasi.');
    }

    /** OWNER ONLY: Fee tamu manual */
    public function feeTamuForm(IncomeTransaction $incomeTransaction)
    {
        $incomeTransaction->load('doctor');

        if ($incomeTransaction->status !== 'LUNAS') abort(403);
        if (($incomeTransaction->doctor?->type ?? '') !== 'TAMU') abort(403);

        return view('income.fee_tamu', [
            'trx' => $incomeTransaction,
        ]);
    }

    public function feeTamuStore(Request $request, IncomeTransaction $incomeTransaction)
    {
        $incomeTransaction->load('doctor');

        if ($incomeTransaction->status !== 'LUNAS') abort(403);
        if (($incomeTransaction->doctor?->type ?? '') !== 'TAMU') abort(403);

        $data = $request->validate([
            'doctor_fee_manual' => ['required', 'numeric', 'min:0'],
        ]);

        $incomeTransaction->update([
            'doctor_fee_manual' => (float) $data['doctor_fee_manual'],
        ]);

        return redirect()->route('income.index')->with('success', 'Fee Dokter Tamu berhasil disimpan.');
    }

    /**
     * Build detail items + bill_total + fee snapshot (untuk MITRA).
     * Kalau bukan MITRA -> fee snapshot NONE.
     *
     * IMPORTANT:
     * - Harga unit_price DIAMBIL DARI MASTER tindakan (treatments.price), bukan dari input.
     * - qty FLAT dipaksa 1
     */
    private function buildItemsAndTotalWithFee(array $itemsInput, bool $isMitra): array
    {
        $treatments = Treatment::whereIn('id', collect($itemsInput)->pluck('treatment_id')->unique())
            ->get()
            ->keyBy('id');

        $itemsToInsert = [];
        $billTotal = 0;

        foreach ($itemsInput as $it) {
            $t = $treatments->get($it['treatment_id']);
            if (! $t) {
                throw ValidationException::withMessages(['items' => 'Tindakan tidak valid.']);
            }

            $calcType = $t->calc_type;
            $qty = (int) ($it['qty'] ?? 1);

            // Harga diambil dari master tindakan agar tidak salah input / tidak bisa dimanipulasi
            $unitPrice = (float) ($t->price ?? 0);

            // kalau FLAT, qty dipaksa 1
            if ($calcType === 'FLAT') {
                $qty = 1;
            }

            $subtotal = $qty * $unitPrice;
            $billTotal += $subtotal;

            // fee snapshot
            $feeType = 'NONE';
            $feeValue = 0;
            $feeAmount = 0;

            if ($isMitra) {
                $feeType = $t->mitra_fee_type ?? 'PERCENT';
                $feeValue = (float) ($t->mitra_fee_value ?? 0);

                if ($feeType === 'PERCENT') {
                    $feeAmount = $subtotal * ($feeValue / 100);
                } elseif ($feeType === 'PER_UNIT') {
                    $feeAmount = $qty * $feeValue;
                } else {
                    $feeType = 'NONE';
                    $feeValue = 0;
                    $feeAmount = 0;
                }
            }

            $itemsToInsert[] = [
                'treatment_id' => $t->id,
                'calc_type_snapshot' => $calcType,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,

                'fee_type_snapshot' => $feeType,
                'fee_value_snapshot' => $feeValue,
                'fee_amount' => $feeAmount,
            ];
        }

        return [$itemsToInsert, $billTotal];
    }
}