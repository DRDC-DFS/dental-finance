<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    private function isOwner(Request $request): bool
    {
        $user = $request->user();
        return $user && strtolower((string) $user->role) === 'owner';
    }

    private function canManage(Request $request, Expense $expense): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $isOwner = strtolower((string) $user->role) === 'owner';

        // OWNER boleh manage semua
        if ($isOwner) {
            return;
        }

        // ADMIN hanya boleh manage miliknya sendiri & tidak boleh menyentuh data private
        if ((int) $expense->created_by !== (int) $user->id) {
            abort(403);
        }

        if ((bool) $expense->is_private === true) {
            abort(403);
        }
    }

    private function canDelete(Request $request, Expense $expense): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        // Hanya OWNER yang boleh hapus
        if (strtolower((string) $user->role) !== 'owner') {
            abort(403, 'Hanya OWNER yang boleh menghapus pengeluaran.');
        }

        // Tetap aman: owner boleh hapus semua
        $this->canManage($request, $expense);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isOwner = $this->isOwner($request);
        $today = now()->toDateString();

        if (!$isOwner) {
            $request->validate([
                'date' => ['nullable', 'date'],
            ], [
                'date.date' => 'Tanggal tidak valid.',
            ]);

            $date = $request->date ?: $today;

            $q = Expense::query()
                ->orderByDesc('expense_date')
                ->orderByDesc('id');

            // ADMIN melihat semua pengeluaran klinik yang NON-PRIVATE
            $q->where('is_private', false)
              ->whereDate('expense_date', $date);

            $rows = $q->paginate(20)->withQueryString();

            return view('expense.index', [
                'rows' => $rows,
                'date' => $date,
                'dateStart' => $date,
                'dateEnd' => $date,
            ]);
        }

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

        $q = Expense::query()->orderByDesc('expense_date')->orderByDesc('id');

        if ($dateStart) {
            $q->whereDate('expense_date', '>=', $dateStart);
        }

        if ($dateEnd) {
            $q->whereDate('expense_date', '<=', $dateEnd);
        }

        $rows = $q->paginate(20)->withQueryString();

        return view('expense.index', [
            'rows' => $rows,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
        ]);
    }

    public function create(Request $request)
    {
        return view('expense.create', [
            'isOwner' => $this->isOwner($request),
        ]);
    }

    public function store(Request $request)
    {
        $isOwner = $this->isOwner($request);

        $data = $request->validate([
            'expense_date' => ['required', 'date'],
            'name'         => ['required', 'string', 'max:255'],
            'pay_method'   => ['required', 'in:TUNAI,BCA,BNI,BRI'],
            'amount'       => ['required', 'numeric', 'min:0'],
            'is_private'   => ['nullable'],
        ]);

        Expense::create([
            'expense_date' => $data['expense_date'],
            'name'         => $data['name'],
            'pay_method'   => $data['pay_method'],
            'amount'       => $data['amount'],
            'is_private'   => $isOwner ? $request->boolean('is_private') : false,
            'created_by'   => $request->user()->id,
        ]);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil disimpan.');
    }

    public function edit(Request $request, Expense $expense)
    {
        $this->canManage($request, $expense);

        return view('expense.edit', [
            'expense' => $expense,
            'isOwner' => $this->isOwner($request),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->canManage($request, $expense);

        $isOwner = $this->isOwner($request);

        $data = $request->validate([
            'expense_date' => ['required', 'date'],
            'name'         => ['required', 'string', 'max:255'],
            'pay_method'   => ['required', 'in:TUNAI,BCA,BNI,BRI'],
            'amount'       => ['required', 'numeric', 'min:0'],
            'is_private'   => ['nullable'],
        ]);

        $expense->update([
            'expense_date' => $data['expense_date'],
            'name'         => $data['name'],
            'pay_method'   => $data['pay_method'],
            'amount'       => $data['amount'],
            'is_private'   => $isOwner ? $request->boolean('is_private') : false,
        ]);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil diupdate.');
    }

    public function destroy(Request $request, Expense $expense)
    {
        $this->canDelete($request, $expense);

        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil dihapus.');
    }
}