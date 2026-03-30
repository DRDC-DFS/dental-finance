<?php

namespace App\Http\Controllers;

use App\Models\IncomeTransaction;
use App\Models\OwnerFinanceCase;
use App\Models\OwnerFinanceInstallment;
use App\Services\OwnerFinanceLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OwnerFinanceController extends Controller
{
    private function ensureOwner(): void
    {
        $user = Auth::user();

        if (!$user || strtolower((string) $user->role) !== 'owner') {
            abort(403, 'Hanya OWNER yang boleh mengakses Owner Finance Control.');
        }
    }

    private function cleanToNumber(?string $value): float
    {
        $value = (string) $value;
        $digits = preg_replace('/[^\d]/', '', $value);

        if ($digits === null || $digits === '') {
            return 0;
        }

        return (float) $digits;
    }

    private function getNextInstallmentNumber(OwnerFinanceCase $case): int
    {
        $lastNo = (int) $case->installments()->max('installment_no');

        return $lastNo > 0 ? ($lastNo + 1) : 1;
    }

    private function recalculateOrthoFinancials(OwnerFinanceCase $case, ?float $manualPaidAmount = null): void
    {
        if ($case->case_type !== 'ortho') {
            $case->ortho_allocation_amount = 0;
            $case->ortho_payment_mode = null;
            $case->ortho_installment_count = null;
            $case->ortho_paid_amount = 0;
            $case->ortho_remaining_balance = 0;
            return;
        }

        $allocation = max(0, (float) ($case->ortho_allocation_amount ?? 0));

        $installmentCount = $case->installments()->count();
        if ($installmentCount > 0) {
            $paid = (float) $case->installments()->sum('amount');
        } else {
            $paid = max(0, (float) ($manualPaidAmount ?? $case->ortho_paid_amount ?? 0));
        }

        if ($paid > $allocation && $allocation > 0) {
            $paid = $allocation;
        }

        $case->ortho_paid_amount = round($paid, 2);
        $case->ortho_remaining_balance = max(0, round($allocation - $paid, 2));
    }

    private function normalizeProsthoFinancials(OwnerFinanceCase $case): void
    {
        if (!in_array((string) $case->case_type, ['prostodonti', 'retainer', 'lab'], true)) {
            $case->lab_bill_amount = 0;
            $case->clinic_income_amount = 0;
            $case->revenue_recognized_at = null;
            return;
        }

        $payTotal = (float) ($case->incomeTransaction?->pay_total ?? 0);
        $labBill = max(0, (float) ($case->lab_bill_amount ?? 0));

        if ($labBill > $payTotal && $payTotal > 0) {
            $labBill = $payTotal;
        }

        $case->lab_bill_amount = round($labBill, 2);
        $case->clinic_income_amount = max(0, round($payTotal - $labBill, 2));

        if ((bool) $case->lab_paid && (bool) $case->installed) {
            if (!$case->revenue_recognized_at) {
                $case->revenue_recognized_at = now();
            }
        } else {
            $case->revenue_recognized_at = null;
        }
    }

    private function syncOwnerWorkflowState(OwnerFinanceCase $case, ?string $preferredNote = null): void
    {
        $preferredNote = trim((string) $preferredNote);
        $privateNotes = trim((string) ($case->owner_private_notes ?? ''));
        $prosthoCaseType = trim((string) ($case->prostho_case_type ?? ''));
        $prosthoCaseDetail = trim((string) ($case->prostho_case_detail ?? ''));

        $case->needs_setup = true;
        $case->owner_followup_status = 'needs_setup';
        $case->case_progress_status = $case->case_type === 'ortho' ? 'waiting_setup' : 'waiting_owner_setup';
        $case->owner_last_action_note = $preferredNote !== '' ? $preferredNote : 'Menunggu data owner';
        $case->owner_last_action_at = null;

        if ($case->case_type === 'ortho') {
            $allocation = (float) ($case->ortho_allocation_amount ?? 0);
            $paid = (float) ($case->ortho_paid_amount ?? 0);
            $remaining = (float) ($case->ortho_remaining_balance ?? 0);
            $hasInstallments = $case->installments()->exists();

            $hasSetup = $allocation > 0
                || !empty($case->ortho_payment_mode)
                || !empty($case->ortho_installment_count)
                || $paid > 0
                || $hasInstallments
                || $privateNotes !== ''
                || (bool) $case->lab_paid
                || (bool) $case->installed;

            if ($case->lab_paid && $case->installed && $remaining <= 0) {
                $case->needs_setup = false;
                $case->owner_followup_status = 'done';
                $case->case_progress_status = 'done';
                $case->owner_last_action_note = $preferredNote !== '' ? $preferredNote : 'Kasus ortho selesai';
                $case->owner_last_action_at = now();
                return;
            }

            if ($case->lab_paid && !$case->installed) {
                $case->needs_setup = false;
                $case->owner_followup_status = 'in_progress';
                $case->case_progress_status = 'lab_paid_not_installed';
                $case->owner_last_action_note = $preferredNote !== '' ? $preferredNote : 'LAB sudah dibayar, menunggu pemasangan';
                $case->owner_last_action_at = now();
                return;
            }

            if (!$case->lab_paid && $case->installed) {
                $case->needs_setup = false;
                $case->owner_followup_status = 'in_progress';
                $case->case_progress_status = 'installed_lab_not_paid';
                $case->owner_last_action_note = $preferredNote !== '' ? $preferredNote : 'Sudah terpasang, LAB belum dibayar';
                $case->owner_last_action_at = now();
                return;
            }

            if (($paid > 0 || $hasInstallments) && $remaining > 0) {
                $case->needs_setup = false;
                $case->owner_followup_status = 'in_progress';
                $case->case_progress_status = 'installment_running';
                $case->owner_last_action_note = $preferredNote !== '' ? $preferredNote : 'Cicilan ortho berjalan';
                $case->owner_last_action_at = now();
                return;
            }

            if ($allocation > 0) {
                $case->needs_setup = false;
                $case->owner_followup_status = 'in_progress';
                $case->case_progress_status = 'remaining_balance';
                $case->owner_last_action_note = $preferredNote !== '' ? $preferredNote : 'Setup ortho sudah diisi';
                $case->owner_last_action_at = now();
                return;
            }

            if ($hasSetup) {
                $case->needs_setup = false;
                $case->owner_followup_status = 'followed_up';
                $case->case_progress_status = 'waiting_setup';
                $case->owner_last_action_note = $preferredNote !== '' ? $preferredNote : 'Data owner sudah dilengkapi';
                $case->owner_last_action_at = now();
                return;
            }

            return;
        }

        $hasAnyOwnerInput = (bool) $case->lab_paid
            || (bool) $case->installed
            || $privateNotes !== ''
            || $prosthoCaseType !== ''
            || $prosthoCaseDetail !== ''
            || (float) ($case->lab_bill_amount ?? 0) > 0;

        if ($case->lab_paid && $case->installed) {
            $case->needs_setup = false;
            $case->owner_followup_status = 'done';
            $case->case_progress_status = 'done';
            $case->owner_last_action_note = $preferredNote !== '' ? $preferredNote : 'Kasus selesai';
            $case->owner_last_action_at = now();
            return;
        }

        if ($case->lab_paid && !$case->installed) {
            $case->needs_setup = false;
            $case->owner_followup_status = 'in_progress';
            $case->case_progress_status = 'lab_paid_not_installed';
            $case->owner_last_action_note = $preferredNote !== '' ? $preferredNote : 'LAB sudah dibayar, menunggu pemasangan';
            $case->owner_last_action_at = now();
            return;
        }

        if (!$case->lab_paid && $case->installed) {
            $case->needs_setup = false;
            $case->owner_followup_status = 'in_progress';
            $case->case_progress_status = 'installed_lab_not_paid';
            $case->owner_last_action_note = $preferredNote !== '' ? $preferredNote : 'Sudah terpasang, LAB belum dibayar';
            $case->owner_last_action_at = now();
            return;
        }

        if ($hasAnyOwnerInput) {
            $case->needs_setup = false;
            $case->owner_followup_status = 'followed_up';
            $case->case_progress_status = 'waiting_lab_payment';
            $case->owner_last_action_note = $preferredNote !== '' ? $preferredNote : 'Data owner sudah dilengkapi';
            $case->owner_last_action_at = now();
        }
    }

    private function ensureInstallmentBelongsToCase(OwnerFinanceCase $ownerFinanceCase, OwnerFinanceInstallment $installment): void
    {
        if ((int) $installment->owner_finance_case_id !== (int) $ownerFinanceCase->id) {
            abort(404);
        }
    }

    private function rebuildMonthlyLedger(OwnerFinanceCase $case): void
    {
        app(OwnerFinanceLedgerService::class)->rebuildForCase(
            $case->fresh([
                'incomeTransaction',
                'installments',
                'monthlyLedgers',
            ])
        );
    }

    private function normalizeProsthoFields(OwnerFinanceCase $case, array $data): void
    {
        $caseType = strtolower((string) ($case->case_type ?? ''));

        if (in_array($caseType, ['prostodonti', 'retainer', 'lab'], true)) {
            $case->prostho_case_type = trim((string) ($data['prostho_case_type'] ?? '')) ?: null;
            $case->prostho_case_detail = trim((string) ($data['prostho_case_detail'] ?? '')) ?: null;
            $case->lab_bill_amount = $this->cleanToNumber($data['lab_bill_amount'] ?? '0');
            return;
        }

        $case->prostho_case_type = null;
        $case->prostho_case_detail = null;
        $case->lab_bill_amount = 0;
        $case->clinic_income_amount = 0;
        $case->revenue_recognized_at = null;
    }

    public function index(Request $request)
    {
        $this->ensureOwner();

        $tab = $request->query('tab', 'needs_setup');
        $caseType = $request->query('case_type', '');
        $q = trim((string) $request->query('q', ''));

        $query = OwnerFinanceCase::with([
            'incomeTransaction.patient',
            'incomeTransaction.doctor',
        ])->orderByDesc('id');

        if (in_array($caseType, ['prostodonti', 'ortho', 'retainer', 'lab'], true)) {
            $query->where('case_type', $caseType);
        }

        if ($q !== '') {
            $query->where(function ($caseQuery) use ($q) {
                $caseQuery->whereHas('incomeTransaction', function ($trxQuery) use ($q) {
                    $trxQuery->where('invoice_number', 'like', '%' . $q . '%')
                        ->orWhereHas('patient', function ($patientQuery) use ($q) {
                            $patientQuery->where('name', 'like', '%' . $q . '%');
                        })
                        ->orWhereHas('doctor', function ($doctorQuery) use ($q) {
                            $doctorQuery->where('name', 'like', '%' . $q . '%');
                        });
                });
            });
        }

        if ($tab === 'monitoring') {
            $query->where(function ($qBuilder) {
                $qBuilder->where('needs_setup', false)
                    ->orWhereIn('owner_followup_status', ['followed_up', 'in_progress', 'done']);
            });
        } else {
            $query->where(function ($qBuilder) {
                $qBuilder->where('needs_setup', true)
                    ->orWhere('owner_followup_status', 'needs_setup')
                    ->orWhereNull('owner_followup_status');
            });
        }

        $cases = $query->paginate(20)->withQueryString();

        return view('owner_finance.index', [
            'cases' => $cases,
            'tab' => $tab,
            'caseType' => $caseType,
            'q' => $q,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureOwner();

        $incomeId = (int) $request->query('income_id');
        $incomeTransaction = null;

        if ($incomeId > 0) {
            $existing = OwnerFinanceCase::where('income_transaction_id', $incomeId)->first();
            if ($existing) {
                return redirect()->route('owner_finance.edit', $existing->id);
            }

            $incomeTransaction = IncomeTransaction::with(['patient', 'doctor'])->findOrFail($incomeId);
        }

        $transactions = IncomeTransaction::with(['patient', 'doctor'])
            ->orderByDesc('trx_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('owner_finance.form', [
            'mode' => 'create',
            'ownerFinanceCase' => new OwnerFinanceCase([
                'case_type' => 'prostodonti',
                'lab_paid' => false,
                'installed' => false,
                'prostho_case_type' => null,
                'prostho_case_detail' => null,
                'lab_bill_amount' => 0,
                'clinic_income_amount' => 0,
                'revenue_recognized_at' => null,
                'ortho_payment_mode' => 'installments',
                'ortho_installment_count' => 3,
                'ortho_paid_amount' => 0,
                'ortho_remaining_balance' => 0,
                'needs_setup' => true,
                'owner_followup_status' => 'needs_setup',
                'case_progress_status' => 'waiting_owner_setup',
                'owner_last_action_note' => 'Menunggu data owner',
            ]),
            'incomeTransaction' => $incomeTransaction,
            'transactions' => $transactions,
            'installments' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureOwner();

        $data = $request->validate([
            'income_transaction_id' => [
                'required',
                'exists:income_transactions,id',
                'unique:owner_finance_cases,income_transaction_id',
            ],
            'case_type' => ['required', Rule::in(['prostodonti', 'ortho', 'retainer', 'lab'])],
            'lab_paid' => ['nullable', 'boolean'],
            'installed' => ['nullable', 'boolean'],
            'prostho_case_type' => ['nullable', 'string', 'max:50'],
            'prostho_case_detail' => ['nullable', 'string'],
            'lab_bill_amount' => ['nullable', 'string'],
            'owner_private_notes' => ['nullable', 'string'],
            'owner_last_action_note' => ['nullable', 'string', 'max:255'],
            'ortho_allocation_amount' => ['nullable', 'string'],
            'ortho_payment_mode' => ['nullable', Rule::in(['full', 'installments'])],
            'ortho_installment_count' => ['nullable', 'integer', 'min:1', 'max:36'],
            'ortho_paid_amount' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($data) {
            $case = new OwnerFinanceCase();
            $case->income_transaction_id = (int) $data['income_transaction_id'];
            $case->case_type = $data['case_type'];
            $case->lab_paid = (bool) ($data['lab_paid'] ?? false);
            $case->installed = (bool) ($data['installed'] ?? false);
            $case->owner_private_notes = $data['owner_private_notes'] ?? null;
            $case->ortho_allocation_amount = $this->cleanToNumber($data['ortho_allocation_amount'] ?? '0');
            $case->ortho_payment_mode = $data['case_type'] === 'ortho' ? ($data['ortho_payment_mode'] ?? 'installments') : null;
            $case->ortho_installment_count = $data['case_type'] === 'ortho' ? ((int) ($data['ortho_installment_count'] ?? 3)) : null;
            $case->is_active = (bool) ($data['is_active'] ?? true);
            $case->created_by = Auth::id();
            $case->updated_by = Auth::id();

            $this->normalizeProsthoFields($case, $data);

            $manualPaidAmount = $this->cleanToNumber($data['ortho_paid_amount'] ?? '0');
            $this->recalculateOrthoFinancials($case, $manualPaidAmount);
            $this->normalizeProsthoFinancials($case);
            $this->syncOwnerWorkflowState($case, $data['owner_last_action_note'] ?? null);

            $case->save();

            $this->rebuildMonthlyLedger($case);

            return redirect()
                ->route('owner_finance.edit', $case->id)
                ->with('success', 'Owner Finance Case berhasil dibuat.');
        });
    }

    public function edit(OwnerFinanceCase $ownerFinanceCase)
    {
        $this->ensureOwner();

        $ownerFinanceCase->load([
            'incomeTransaction.patient',
            'incomeTransaction.doctor',
            'installments',
            'monthlyLedgers',
        ]);

        return view('owner_finance.form', [
            'mode' => 'edit',
            'ownerFinanceCase' => $ownerFinanceCase,
            'incomeTransaction' => $ownerFinanceCase->incomeTransaction,
            'transactions' => collect(),
            'installments' => $ownerFinanceCase->installments,
        ]);
    }

    public function update(Request $request, OwnerFinanceCase $ownerFinanceCase)
    {
        $this->ensureOwner();

        $data = $request->validate([
            'case_type' => ['required', Rule::in(['prostodonti', 'ortho', 'retainer', 'lab'])],
            'lab_paid' => ['nullable', 'boolean'],
            'installed' => ['nullable', 'boolean'],
            'prostho_case_type' => ['nullable', 'string', 'max:50'],
            'prostho_case_detail' => ['nullable', 'string'],
            'lab_bill_amount' => ['nullable', 'string'],
            'owner_private_notes' => ['nullable', 'string'],
            'owner_last_action_note' => ['nullable', 'string', 'max:255'],
            'ortho_allocation_amount' => ['nullable', 'string'],
            'ortho_payment_mode' => ['nullable', Rule::in(['full', 'installments'])],
            'ortho_installment_count' => ['nullable', 'integer', 'min:1', 'max:36'],
            'ortho_paid_amount' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],

            'action_type' => ['nullable', Rule::in(['save_case', 'add_installment'])],
            'new_installment_no' => ['nullable', 'integer', 'min:1', 'max:120'],
            'new_installment_date' => ['nullable', 'date'],
            'new_installment_amount' => ['nullable', 'string'],
            'new_installment_notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data, $ownerFinanceCase) {
            $ownerFinanceCase->case_type = $data['case_type'];
            $ownerFinanceCase->lab_paid = (bool) ($data['lab_paid'] ?? false);
            $ownerFinanceCase->installed = (bool) ($data['installed'] ?? false);
            $ownerFinanceCase->owner_private_notes = $data['owner_private_notes'] ?? null;
            $ownerFinanceCase->ortho_allocation_amount = $this->cleanToNumber($data['ortho_allocation_amount'] ?? '0');
            $ownerFinanceCase->ortho_payment_mode = $data['case_type'] === 'ortho' ? ($data['ortho_payment_mode'] ?? 'installments') : null;
            $ownerFinanceCase->ortho_installment_count = $data['case_type'] === 'ortho' ? ((int) ($data['ortho_installment_count'] ?? 3)) : null;
            $ownerFinanceCase->is_active = (bool) ($data['is_active'] ?? true);
            $ownerFinanceCase->updated_by = Auth::id();

            $this->normalizeProsthoFields($ownerFinanceCase, $data);

            $ownerFinanceCase->save();

            $actionType = (string) ($data['action_type'] ?? 'save_case');
            $manualPaidAmount = $this->cleanToNumber($data['ortho_paid_amount'] ?? '0');

            if ($actionType === 'add_installment') {
                if ($ownerFinanceCase->case_type !== 'ortho') {
                    return redirect()
                        ->route('owner_finance.edit', $ownerFinanceCase->id)
                        ->withErrors(['new_installment_amount' => 'Histori cicilan hanya untuk kasus ortho.']);
                }

                $installmentNo = (int) ($data['new_installment_no'] ?? 0);
                if ($installmentNo <= 0) {
                    $installmentNo = $this->getNextInstallmentNumber($ownerFinanceCase);
                }

                $installmentDate = (string) ($data['new_installment_date'] ?? '');
                $installmentAmount = $this->cleanToNumber($data['new_installment_amount'] ?? '0');

                if ($installmentDate === '') {
                    return redirect()
                        ->route('owner_finance.edit', $ownerFinanceCase->id)
                        ->withErrors(['new_installment_date' => 'Tanggal cicilan wajib diisi.'])
                        ->withInput();
                }

                if ($installmentAmount <= 0) {
                    return redirect()
                        ->route('owner_finance.edit', $ownerFinanceCase->id)
                        ->withErrors(['new_installment_amount' => 'Nominal cicilan harus lebih dari 0.'])
                        ->withInput();
                }

                if ((float) $ownerFinanceCase->ortho_allocation_amount <= 0) {
                    return redirect()
                        ->route('owner_finance.edit', $ownerFinanceCase->id)
                        ->withErrors(['new_installment_amount' => 'Isi Dana Alokasi Ortho terlebih dahulu sebelum menambah cicilan.'])
                        ->withInput();
                }

                $alreadyPaid = (float) $ownerFinanceCase->installments()->sum('amount');
                $allocation = (float) ($ownerFinanceCase->ortho_allocation_amount ?? 0);
                $remainingBefore = max(0, round($allocation - $alreadyPaid, 2));

                if ($remainingBefore <= 0) {
                    return redirect()
                        ->route('owner_finance.edit', $ownerFinanceCase->id)
                        ->withErrors(['new_installment_amount' => 'Sisa dana ortho sudah 0. Tidak bisa menambah cicilan lagi.'])
                        ->withInput();
                }

                if ($installmentAmount > $remainingBefore) {
                    return redirect()
                        ->route('owner_finance.edit', $ownerFinanceCase->id)
                        ->withErrors([
                            'new_installment_amount' => 'Nominal cicilan melebihi sisa dana ortho. Sisa saat ini: Rp ' . number_format($remainingBefore, 0, ',', '.')
                        ])
                        ->withInput();
                }

                $duplicate = OwnerFinanceInstallment::query()
                    ->where('owner_finance_case_id', $ownerFinanceCase->id)
                    ->where('installment_no', $installmentNo)
                    ->exists();

                if ($duplicate) {
                    return redirect()
                        ->route('owner_finance.edit', $ownerFinanceCase->id)
                        ->withErrors(['new_installment_no' => 'Nomor cicilan ini sudah ada. Gunakan nomor berikutnya.'])
                        ->withInput();
                }

                OwnerFinanceInstallment::create([
                    'owner_finance_case_id' => $ownerFinanceCase->id,
                    'installment_no' => $installmentNo,
                    'installment_date' => $installmentDate,
                    'amount' => $installmentAmount,
                    'notes' => $data['new_installment_notes'] ?? null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            $ownerFinanceCase->refresh();
            $this->recalculateOrthoFinancials($ownerFinanceCase, $manualPaidAmount);
            $this->normalizeProsthoFinancials($ownerFinanceCase);
            $this->syncOwnerWorkflowState($ownerFinanceCase, $data['owner_last_action_note'] ?? null);
            $ownerFinanceCase->updated_by = Auth::id();
            $ownerFinanceCase->save();

            $this->rebuildMonthlyLedger($ownerFinanceCase);

            return redirect()
                ->route('owner_finance.edit', $ownerFinanceCase->id)
                ->with('success', $actionType === 'add_installment'
                    ? 'Histori cicilan owner berhasil ditambahkan.'
                    : 'Owner Finance Case berhasil diperbarui.');
        });
    }

    public function updateInstallment(Request $request, OwnerFinanceCase $ownerFinanceCase, OwnerFinanceInstallment $installment)
    {
        $this->ensureOwner();
        $this->ensureInstallmentBelongsToCase($ownerFinanceCase, $installment);

        $data = $request->validate([
            'installment_no' => ['required', 'integer', 'min:1', 'max:120'],
            'installment_date' => ['required', 'date'],
            'amount' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data, $ownerFinanceCase, $installment) {
            if ($ownerFinanceCase->case_type !== 'ortho') {
                return redirect()
                    ->route('owner_finance.edit', $ownerFinanceCase->id)
                    ->withErrors(['amount' => 'Histori cicilan hanya untuk kasus ortho.']);
            }

            $newAmount = $this->cleanToNumber($data['amount']);
            if ($newAmount <= 0) {
                return redirect()
                    ->route('owner_finance.edit', $ownerFinanceCase->id)
                    ->withErrors(['amount' => 'Nominal cicilan harus lebih dari 0.'])
                    ->withInput();
            }

            $duplicate = OwnerFinanceInstallment::query()
                ->where('owner_finance_case_id', $ownerFinanceCase->id)
                ->where('installment_no', (int) $data['installment_no'])
                ->where('id', '!=', $installment->id)
                ->exists();

            if ($duplicate) {
                return redirect()
                    ->route('owner_finance.edit', $ownerFinanceCase->id)
                    ->withErrors(['amount' => 'Nomor cicilan sudah dipakai oleh histori lain.'])
                    ->withInput();
            }

            $sumOtherInstallments = (float) OwnerFinanceInstallment::query()
                ->where('owner_finance_case_id', $ownerFinanceCase->id)
                ->where('id', '!=', $installment->id)
                ->sum('amount');

            $allocation = (float) ($ownerFinanceCase->ortho_allocation_amount ?? 0);
            $remainingAllowed = max(0, round($allocation - $sumOtherInstallments, 2));

            if ($newAmount > $remainingAllowed) {
                return redirect()
                    ->route('owner_finance.edit', $ownerFinanceCase->id)
                    ->withErrors([
                        'amount' => 'Nominal cicilan melebihi sisa dana ortho yang tersedia. Maksimal saat ini: Rp ' . number_format($remainingAllowed, 0, ',', '.')
                    ])
                    ->withInput();
            }

            $installment->update([
                'installment_no' => (int) $data['installment_no'],
                'installment_date' => $data['installment_date'],
                'amount' => $newAmount,
                'notes' => $data['notes'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            $ownerFinanceCase->refresh();
            $this->recalculateOrthoFinancials($ownerFinanceCase);
            $this->syncOwnerWorkflowState($ownerFinanceCase);
            $ownerFinanceCase->updated_by = Auth::id();
            $ownerFinanceCase->save();

            $this->rebuildMonthlyLedger($ownerFinanceCase);

            return redirect()
                ->route('owner_finance.edit', $ownerFinanceCase->id)
                ->with('success', 'Histori cicilan owner berhasil diperbarui.');
        });
    }

    public function destroyInstallment(OwnerFinanceCase $ownerFinanceCase, OwnerFinanceInstallment $installment)
    {
        $this->ensureOwner();
        $this->ensureInstallmentBelongsToCase($ownerFinanceCase, $installment);

        return DB::transaction(function () use ($ownerFinanceCase, $installment) {
            $installment->delete();

            $ownerFinanceCase->refresh();
            $this->recalculateOrthoFinancials($ownerFinanceCase);
            $this->syncOwnerWorkflowState($ownerFinanceCase);
            $ownerFinanceCase->updated_by = Auth::id();
            $ownerFinanceCase->save();

            $this->rebuildMonthlyLedger($ownerFinanceCase);

            return redirect()
                ->route('owner_finance.edit', $ownerFinanceCase->id)
                ->with('success', 'Histori cicilan owner berhasil dihapus.');
        });
    }
}