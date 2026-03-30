<?php

namespace App\Http\Controllers;

use App\Models\OwnerPrivateTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OwnerPrivateTransactionController extends Controller
{
    private function ensureOwner(): void
    {
        $user = Auth::user();
        $role = strtolower((string) ($user->role ?? ''));

        if (!$user || $role !== 'owner') {
            abort(403, 'Hanya OWNER yang boleh mengakses transaksi private owner.');
        }
    }

    private function categoryOptions(): array
    {
        return [
            'vendor' => 'Vendor',
            'cashback' => 'Cashback',
            'refund' => 'Refund',
            'bonus' => 'Bonus',
            'transfer_owner' => 'Transfer Owner',
            'investasi_owner' => 'Investasi Owner',
            'pengeluaran_pribadi' => 'Pengeluaran Pribadi Owner',
            'transfer_keluar' => 'Transfer Keluar',
            'mutasi_owner' => 'Mutasi Owner',
            'pendapatan_lain' => 'Pendapatan Lain',
            'lainnya' => 'Lainnya',
        ];
    }

    public function index(Request $request)
    {
        $this->ensureOwner();

        $request->validate([
            'date_start' => ['nullable', 'date'],
            'date_end'   => ['nullable', 'date', 'after_or_equal:date_start'],
            'type'       => ['nullable', 'in:income,expense'],
            'category'   => ['nullable', 'string', 'max:100'],
        ], [
            'date_start.date' => 'Tanggal mulai tidak valid.',
            'date_end.date' => 'Tanggal selesai tidak valid.',
            'date_end.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'type.in' => 'Tipe transaksi tidak valid.',
            'category.max' => 'Kategori maksimal 100 karakter.',
        ]);

        $today = now()->toDateString();

        $dateStart = $request->date_start ?: $today;
        $dateEnd = $request->date_end ?: $today;
        $type = $request->type;
        $category = trim((string) $request->category);

        $query = OwnerPrivateTransaction::query()
            ->with('creator')
            ->orderByDesc('trx_date')
            ->orderByDesc('id');

        if ($dateStart) {
            $query->whereDate('trx_date', '>=', $dateStart);
        }

        if ($dateEnd) {
            $query->whereDate('trx_date', '<=', $dateEnd);
        }

        if (!empty($type)) {
            $query->where('type', $type);
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        $rows = $query->paginate(20)->withQueryString();

        return view('owner_private.index', [
            'rows' => $rows,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
            'type' => $type,
            'category' => $category,
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function create()
    {
        $this->ensureOwner();

        return view('owner_private.create', [
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureOwner();

        $data = $request->validate([
            'trx_date' => ['required', 'date'],
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', 'in:TUNAI,BCA,BNI,BRI'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ], [
            'trx_date.required' => 'Tanggal wajib diisi.',
            'trx_date.date' => 'Tanggal tidak valid.',
            'type.required' => 'Tipe transaksi wajib dipilih.',
            'type.in' => 'Tipe transaksi tidak valid.',
            'category.required' => 'Kategori wajib dipilih.',
            'category.max' => 'Kategori maksimal 100 karakter.',
            'source.max' => 'Sumber maksimal 255 karakter.',
            'description.required' => 'Keterangan wajib diisi.',
            'description.max' => 'Keterangan maksimal 255 karakter.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
            'amount.required' => 'Nominal wajib diisi.',
            'amount.numeric' => 'Nominal harus berupa angka.',
            'amount.min' => 'Nominal tidak boleh negatif.',
        ]);

        OwnerPrivateTransaction::create([
            'trx_date' => $data['trx_date'],
            'type' => $data['type'],
            'category' => $data['category'],
            'source' => $data['source'] ?? null,
            'description' => $data['description'],
            'payment_method' => $data['payment_method'],
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('owner_private.index')
            ->with('success', 'Transaksi private owner berhasil disimpan.');
    }

    public function edit(OwnerPrivateTransaction $ownerPrivateTransaction)
    {
        $this->ensureOwner();

        return view('owner_private.edit', [
            'transaction' => $ownerPrivateTransaction,
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function update(Request $request, OwnerPrivateTransaction $ownerPrivateTransaction)
    {
        $this->ensureOwner();

        $data = $request->validate([
            'trx_date' => ['required', 'date'],
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', 'in:TUNAI,BCA,BNI,BRI'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ], [
            'trx_date.required' => 'Tanggal wajib diisi.',
            'trx_date.date' => 'Tanggal tidak valid.',
            'type.required' => 'Tipe transaksi wajib dipilih.',
            'type.in' => 'Tipe transaksi tidak valid.',
            'category.required' => 'Kategori wajib dipilih.',
            'category.max' => 'Kategori maksimal 100 karakter.',
            'source.max' => 'Sumber maksimal 255 karakter.',
            'description.required' => 'Keterangan wajib diisi.',
            'description.max' => 'Keterangan maksimal 255 karakter.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
            'amount.required' => 'Nominal wajib diisi.',
            'amount.numeric' => 'Nominal harus berupa angka.',
            'amount.min' => 'Nominal tidak boleh negatif.',
        ]);

        $ownerPrivateTransaction->update([
            'trx_date' => $data['trx_date'],
            'type' => $data['type'],
            'category' => $data['category'],
            'source' => $data['source'] ?? null,
            'description' => $data['description'],
            'payment_method' => $data['payment_method'],
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('owner_private.index')
            ->with('success', 'Transaksi private owner berhasil diupdate.');
    }

    public function destroy(OwnerPrivateTransaction $ownerPrivateTransaction)
    {
        $this->ensureOwner();

        $ownerPrivateTransaction->delete();

        return redirect()
            ->route('owner_private.index')
            ->with('success', 'Transaksi private owner berhasil dihapus.');
    }
}