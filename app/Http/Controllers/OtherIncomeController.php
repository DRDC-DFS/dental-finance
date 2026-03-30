<?php

namespace App\Http\Controllers;

use App\Models\OtherIncome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtherIncomeController extends Controller
{
    private function ensureOwnerOrAdmin(): void
    {
        $user = Auth::user();
        $role = strtolower((string) ($user->role ?? ''));

        if (!$user || !in_array($role, ['owner', 'admin'], true)) {
            abort(403, 'Hanya OWNER atau ADMIN yang boleh mengakses modul pemasukan lain-lain.');
        }
    }

    private function currentRole(): string
    {
        return strtolower((string) (Auth::user()->role ?? ''));
    }

    public function index(Request $request)
    {
        $this->ensureOwnerOrAdmin();

        $today = now()->toDateString();
        $role = $this->currentRole();
        $isOwner = $role === 'owner';

        if ($isOwner) {
            $request->validate([
                'date_start' => ['nullable', 'date'],
                'date_end'   => ['nullable', 'date', 'after_or_equal:date_start'],
            ], [
                'date_start.date' => 'Tanggal mulai tidak valid.',
                'date_end.date' => 'Tanggal selesai tidak valid.',
                'date_end.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            ]);

            $dateStart = $request->date_start;
            $dateEnd = $request->date_end;

            if (!$dateStart && !$dateEnd) {
                $dateStart = $today;
                $dateEnd = $today;
            } else {
                if (!$dateStart && $dateEnd) {
                    $dateStart = $dateEnd;
                }

                if ($dateStart && !$dateEnd) {
                    $dateEnd = $dateStart;
                }
            }
        } else {
            $request->validate([
                'date' => ['nullable', 'date'],
            ], [
                'date.date' => 'Tanggal tidak valid.',
            ]);

            $date = $request->date ?: $today;
            $dateStart = $date;
            $dateEnd = $date;
        }

        $query = OtherIncome::query()->with('creator');

        if ($dateStart) {
            $query->whereDate('trx_date', '>=', $dateStart);
        }

        if ($dateEnd) {
            $query->whereDate('trx_date', '<=', $dateEnd);
        }

        $rows = $query
            ->orderByDesc('trx_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('other-income.index', [
            'rows'      => $rows,
            'dateStart' => $dateStart,
            'dateEnd'   => $dateEnd,
            'isOwner'   => $isOwner,
        ]);
    }

    public function create()
    {
        $this->ensureOwnerOrAdmin();

        return view('other-income.create');
    }

    public function store(Request $request)
    {
        $this->ensureOwnerOrAdmin();

        $role = $this->currentRole();

        $visibilityRules = $role === 'owner'
            ? ['required', 'in:public,private']
            : ['nullable', 'in:public,private'];

        $visibilityMessages = $role === 'owner'
            ? ['visibility.required' => 'Visibility wajib dipilih.']
            : [];

        $data = $request->validate([
            'trx_date'             => ['required', 'date'],
            'title'                => ['required', 'string', 'max:150'],
            'source_type'          => ['required', 'string', 'max:100'],
            'amount'               => ['required', 'string'],
            'payment_method'       => ['required', 'in:cash,bank'],
            'bank_name'            => ['nullable', 'in:BCA,BNI,BRI'],
            'payment_channel'      => ['nullable', 'in:transfer,qris,edc'],
            'notes'                => ['nullable', 'string'],
            'visibility'           => $visibilityRules,
            'include_in_report'    => ['nullable', 'in:0,1'],
            'include_in_cashflow'  => ['nullable', 'in:0,1'],
        ], array_merge([
            'trx_date.required' => 'Tanggal wajib diisi.',
            'trx_date.date' => 'Tanggal tidak valid.',
            'title.required' => 'Nama pemasukan wajib diisi.',
            'title.max' => 'Nama pemasukan maksimal 150 karakter.',
            'source_type.required' => 'Jenis / sumber pemasukan wajib diisi.',
            'source_type.max' => 'Jenis / sumber pemasukan maksimal 100 karakter.',
            'amount.required' => 'Nominal wajib diisi.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
            'bank_name.in' => 'Bank harus BCA, BNI, atau BRI.',
            'payment_channel.in' => 'Channel pembayaran tidak valid.',
            'visibility.in' => 'Visibility tidak valid.',
            'include_in_report.in' => 'Nilai input laporan harian tidak valid.',
            'include_in_cashflow.in' => 'Nilai input net setoran tidak valid.',
        ], $visibilityMessages));

        $amount = (float) clean_rupiah((string) ($data['amount'] ?? '0'));

        if ($amount <= 0) {
            return back()
                ->withErrors(['amount' => 'Nominal pemasukan harus lebih dari 0.'])
                ->withInput();
        }

        $paymentMethod = strtolower((string) ($data['payment_method'] ?? 'cash'));
        $bankName = null;
        $paymentChannel = null;

        if ($paymentMethod === 'bank') {
            $bankName = strtoupper((string) ($data['bank_name'] ?? ''));
            $paymentChannel = strtolower((string) ($data['payment_channel'] ?? ''));

            if (!in_array($bankName, ['BCA', 'BNI', 'BRI'], true)) {
                return back()
                    ->withErrors(['bank_name' => 'Untuk metode BANK, pilih bank: BCA / BNI / BRI.'])
                    ->withInput();
            }

            if (!in_array($paymentChannel, ['transfer', 'qris', 'edc'], true)) {
                return back()
                    ->withErrors(['payment_channel' => 'Untuk metode BANK, channel wajib dipilih: TRANSFER / QRIS / EDC.'])
                    ->withInput();
            }
        }

        $visibility = $role === 'admin'
            ? 'public'
            : (string) ($data['visibility'] ?? 'public');

        OtherIncome::create([
            'trx_date'             => $data['trx_date'],
            'title'                => $data['title'],
            'source_type'          => $data['source_type'],
            'amount'               => $amount,
            'payment_method'       => $paymentMethod,
            'bank_name'            => $bankName,
            'payment_channel'      => $paymentChannel,
            'notes'                => $data['notes'] ?? null,
            'visibility'           => $visibility,
            'include_in_report'    => (int) ($data['include_in_report'] ?? 1) === 1,
            'include_in_cashflow'  => (int) ($data['include_in_cashflow'] ?? 1) === 1,
            'created_by'           => Auth::id(),
        ]);

        return redirect()
            ->route('other_income.index')
            ->with('success', 'Pemasukan lain-lain berhasil ditambahkan.');
    }

    public function edit(OtherIncome $otherIncome)
    {
        $this->ensureOwnerOrAdmin();

        return view('other-income.edit', [
            'otherIncome' => $otherIncome,
        ]);
    }

    public function update(Request $request, OtherIncome $otherIncome)
    {
        $this->ensureOwnerOrAdmin();

        $role = $this->currentRole();

        $visibilityRules = $role === 'owner'
            ? ['required', 'in:public,private']
            : ['nullable', 'in:public,private'];

        $visibilityMessages = $role === 'owner'
            ? ['visibility.required' => 'Visibility wajib dipilih.']
            : [];

        $data = $request->validate([
            'trx_date'             => ['required', 'date'],
            'title'                => ['required', 'string', 'max:150'],
            'source_type'          => ['required', 'string', 'max:100'],
            'amount'               => ['required', 'string'],
            'payment_method'       => ['required', 'in:cash,bank'],
            'bank_name'            => ['nullable', 'in:BCA,BNI,BRI'],
            'payment_channel'      => ['nullable', 'in:transfer,qris,edc'],
            'notes'                => ['nullable', 'string'],
            'visibility'           => $visibilityRules,
            'include_in_report'    => ['nullable', 'in:0,1'],
            'include_in_cashflow'  => ['nullable', 'in:0,1'],
        ], array_merge([
            'trx_date.required' => 'Tanggal wajib diisi.',
            'trx_date.date' => 'Tanggal tidak valid.',
            'title.required' => 'Nama pemasukan wajib diisi.',
            'title.max' => 'Nama pemasukan maksimal 150 karakter.',
            'source_type.required' => 'Jenis / sumber pemasukan wajib diisi.',
            'source_type.max' => 'Jenis / sumber pemasukan maksimal 100 karakter.',
            'amount.required' => 'Nominal wajib diisi.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
            'bank_name.in' => 'Bank harus BCA, BNI, atau BRI.',
            'payment_channel.in' => 'Channel pembayaran tidak valid.',
            'visibility.in' => 'Visibility tidak valid.',
            'include_in_report.in' => 'Nilai input laporan harian tidak valid.',
            'include_in_cashflow.in' => 'Nilai input net setoran tidak valid.',
        ], $visibilityMessages));

        $amount = (float) clean_rupiah((string) ($data['amount'] ?? '0'));

        if ($amount <= 0) {
            return back()
                ->withErrors(['amount' => 'Nominal pemasukan harus lebih dari 0.'])
                ->withInput();
        }

        $paymentMethod = strtolower((string) ($data['payment_method'] ?? 'cash'));
        $bankName = null;
        $paymentChannel = null;

        if ($paymentMethod === 'bank') {
            $bankName = strtoupper((string) ($data['bank_name'] ?? ''));
            $paymentChannel = strtolower((string) ($data['payment_channel'] ?? ''));

            if (!in_array($bankName, ['BCA', 'BNI', 'BRI'], true)) {
                return back()
                    ->withErrors(['bank_name' => 'Untuk metode BANK, pilih bank: BCA / BNI / BRI.'])
                    ->withInput();
            }

            if (!in_array($paymentChannel, ['transfer', 'qris', 'edc'], true)) {
                return back()
                    ->withErrors(['payment_channel' => 'Untuk metode BANK, channel wajib dipilih: TRANSFER / QRIS / EDC.'])
                    ->withInput();
            }
        }

        $visibility = $role === 'admin'
            ? 'public'
            : (string) ($data['visibility'] ?? 'public');

        $otherIncome->update([
            'trx_date'             => $data['trx_date'],
            'title'                => $data['title'],
            'source_type'          => $data['source_type'],
            'amount'               => $amount,
            'payment_method'       => $paymentMethod,
            'bank_name'            => $bankName,
            'payment_channel'      => $paymentChannel,
            'notes'                => $data['notes'] ?? null,
            'visibility'           => $visibility,
            'include_in_report'    => (int) ($data['include_in_report'] ?? 1) === 1,
            'include_in_cashflow'  => (int) ($data['include_in_cashflow'] ?? 1) === 1,
        ]);

        return redirect()
            ->route('other_income.index')
            ->with('success', 'Pemasukan lain-lain berhasil diperbarui.');
    }

    public function destroy(OtherIncome $otherIncome)
    {
        $this->ensureOwnerOrAdmin();

        $otherIncome->delete();

        return redirect()
            ->route('other_income.index')
            ->with('success', 'Pemasukan lain-lain berhasil dihapus.');
    }
}