<?php

namespace App\Services;

use App\Models\OwnerAccountMutation;
use App\Models\OwnerFinanceCase;
use App\Models\OwnerFinanceMonthlyLedger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OwnerFinanceLedgerService
{
    public function rebuildForCase(OwnerFinanceCase $case): void
    {
        $case->loadMissing([
            'incomeTransaction.patient',
            'installments',
        ]);

        DB::transaction(function () use ($case) {
            OwnerAccountMutation::query()
                ->where('owner_finance_case_id', $case->id)
                ->delete();

            OwnerFinanceMonthlyLedger::query()
                ->where('owner_finance_case_id', $case->id)
                ->delete();

            if ($case->case_type === 'ortho') {
                $this->rebuildOrthoCase($case);
                return;
            }

            if (in_array((string) $case->case_type, ['prostodonti', 'retainer', 'lab'], true)) {
                $this->rebuildProsthoCase($case);
            }
        });
    }

    private function rebuildOrthoCase(OwnerFinanceCase $case): void
    {
        $allocation = max(0, (float) ($case->ortho_allocation_amount ?? 0));
        if ($allocation <= 0) {
            return;
        }

        $trxDate = $case->incomeTransaction?->trx_date
            ?? $case->created_at
            ?? now();

        $startMonth = Carbon::parse($trxDate)->startOfMonth();

        $installments = $case->installments
            ->sortBy(function ($item) {
                return sprintf(
                    '%s-%010d',
                    optional($item->installment_date)->format('Y-m-d') ?? '0000-00-00',
                    (int) $item->id
                );
            })
            ->values();

        $monthlyInstallments = [];
        foreach ($installments as $installment) {
            $monthKey = Carbon::parse($installment->installment_date)
                ->startOfMonth()
                ->format('Y-m-01');

            if (!isset($monthlyInstallments[$monthKey])) {
                $monthlyInstallments[$monthKey] = 0.0;
            }

            $monthlyInstallments[$monthKey] += (float) $installment->amount;
        }

        $endMonth = $startMonth->copy();

        foreach (array_keys($monthlyInstallments) as $monthKey) {
            $candidate = Carbon::parse($monthKey)->startOfMonth();
            if ($candidate->gt($endMonth)) {
                $endMonth = $candidate;
            }
        }

        $currentMonth = now()->startOfMonth();
        if ((float) $case->ortho_remaining_balance > 0 && $currentMonth->gt($endMonth)) {
            $endMonth = $currentMonth;
        }

        if ($case->lab_paid && $case->installed) {
            $doneMonth = $installments->count() > 0
                ? Carbon::parse($installments->last()->installment_date)->startOfMonth()
                : $startMonth->copy();

            if ($case->owner_last_action_at) {
                $actionMonth = Carbon::parse($case->owner_last_action_at)->startOfMonth();
                if ($actionMonth->gt($doneMonth)) {
                    $doneMonth = $actionMonth;
                }
            }

            if ($doneMonth->gt($endMonth)) {
                $endMonth = $doneMonth;
            }
        }

        $current = $startMonth->copy();
        $openingBalance = round($allocation, 2);
        $createdLedgers = collect();

        while ($current->lte($endMonth)) {
            $monthKey = $current->format('Y-m-01');

            $installmentPaid = round((float) ($monthlyInstallments[$monthKey] ?? 0), 2);
            if ($installmentPaid > $openingBalance) {
                $installmentPaid = $openingBalance;
            }

            $closingBalance = max(0, round($openingBalance - $installmentPaid, 2));

            $isClosed = $closingBalance <= 0
                && (bool) $case->lab_paid
                && (bool) $case->installed;

            $expenseEndMonth = $isClosed ? 0 : $closingBalance;

            $ledger = OwnerFinanceMonthlyLedger::create([
                'owner_finance_case_id' => $case->id,
                'ledger_month' => $current->toDateString(),
                'opening_balance' => $openingBalance,
                'income_amount' => $openingBalance,
                'installment_paid' => $installmentPaid,
                'expense_end_month' => $expenseEndMonth,
                'closing_balance' => $closingBalance,
                'is_closed' => $isClosed,
                'notes' => $this->buildLedgerNotes(
                    $current,
                    $startMonth,
                    $installmentPaid,
                    $closingBalance,
                    $isClosed
                ),
            ]);

            $createdLedgers->push($ledger);

            if ($closingBalance <= 0) {
                break;
            }

            $openingBalance = $closingBalance;
            $current->addMonth()->startOfMonth();
        }

        $this->syncOwnerAccountMutationsForOrtho($case, $createdLedgers, Carbon::parse($trxDate));
    }

    private function rebuildProsthoCase(OwnerFinanceCase $case): void
    {
        $transactionAmount = round((float) ($case->incomeTransaction?->pay_total ?? 0), 2);
        if ($transactionAmount <= 0) {
            return;
        }

        $trxDate = $case->incomeTransaction?->trx_date
            ?? $case->created_at
            ?? now();

        $startMonth = Carbon::parse($trxDate)->startOfMonth();

        $recognizedAt = $case->revenue_recognized_at
            ? Carbon::parse($case->revenue_recognized_at)
            : null;

        $recognizedMonth = $recognizedAt?->copy()?->startOfMonth();

        $currentMonth = now()->startOfMonth();
        $endMonth = $recognizedMonth
            ? $recognizedMonth->copy()
            : max($startMonth->copy(), $currentMonth);

        if (!$recognizedMonth && $currentMonth->gt($endMonth)) {
            $endMonth = $currentMonth;
        }

        $labBillAmount = round((float) ($case->lab_bill_amount ?? 0), 2);
        $clinicIncomeAmount = round((float) ($case->clinic_income_amount ?? max(0, $transactionAmount - $labBillAmount)), 2);

        if ($labBillAmount > $transactionAmount) {
            $labBillAmount = $transactionAmount;
        }

        if (($labBillAmount + $clinicIncomeAmount) > $transactionAmount) {
            $clinicIncomeAmount = max(0, round($transactionAmount - $labBillAmount, 2));
        }

        $current = $startMonth->copy();
        $openingBalance = $transactionAmount;
        $createdLedgers = collect();

        while ($current->lte($endMonth)) {
            $isRecognitionMonth = $recognizedMonth && $current->equalTo($recognizedMonth);

            $installmentPaid = 0.0;
            $expenseEndMonth = $openingBalance;
            $closingBalance = $isRecognitionMonth ? 0.0 : $openingBalance;
            $isClosed = $isRecognitionMonth;

            $notes = $isRecognitionMonth
                ? $this->buildProsthoRecognitionNotes($labBillAmount, $clinicIncomeAmount)
                : $this->buildProsthoCarryForwardNotes($case);

            $ledger = OwnerFinanceMonthlyLedger::create([
                'owner_finance_case_id' => $case->id,
                'ledger_month' => $current->toDateString(),
                'opening_balance' => $openingBalance,
                'income_amount' => $openingBalance,
                'installment_paid' => $installmentPaid,
                'expense_end_month' => $expenseEndMonth,
                'closing_balance' => $closingBalance,
                'is_closed' => $isClosed,
                'notes' => $notes,
            ]);

            $createdLedgers->push($ledger);

            if ($isRecognitionMonth) {
                break;
            }

            $current->addMonth()->startOfMonth();
        }

        $this->syncOwnerAccountMutationsForProstho(
            $case,
            $createdLedgers,
            Carbon::parse($trxDate),
            $recognizedAt,
            $labBillAmount,
            $clinicIncomeAmount
        );
    }

    private function syncOwnerAccountMutationsForOrtho(
        OwnerFinanceCase $case,
        Collection $ledgers,
        Carbon $realTransactionDate
    ): void {
        $invoice = trim((string) ($case->incomeTransaction?->invoice_number ?? ''));
        $patientName = trim((string) ($case->incomeTransaction?->patient?->name ?? ''));
        $today = now()->toDateString();

        foreach ($ledgers->values() as $index => $ledger) {
            $ledgerMonth = Carbon::parse($ledger->ledger_month)->startOfMonth();

            $incomeDate = $index === 0
                ? $realTransactionDate->toDateString()
                : $ledgerMonth->copy()->startOfMonth()->toDateString();

            $incomeDescription = $index === 0
                ? $this->buildDescription('Pemasukan Owner Finance Ortho', $invoice, $patientName)
                : $this->buildDescription('Pemasukan Carry Forward Owner Finance Ortho', $invoice, $patientName);

            if ((float) $ledger->income_amount > 0) {
                OwnerAccountMutation::create([
                    'owner_finance_case_id' => $case->id,
                    'owner_finance_monthly_ledger_id' => $ledger->id,
                    'mutation_date' => $incomeDate,
                    'mutation_type' => 'pemasukan',
                    'source_type' => 'owner_finance_ortho',
                    'description' => $incomeDescription,
                    'amount' => (float) $ledger->income_amount,
                    'reference_month' => $ledgerMonth->toDateString(),
                    'is_system_generated' => true,
                ]);
            }

            $expenseDate = $ledgerMonth->copy()->endOfMonth()->toDateString();

            if ((float) $ledger->expense_end_month > 0 && $expenseDate <= $today) {
                OwnerAccountMutation::create([
                    'owner_finance_case_id' => $case->id,
                    'owner_finance_monthly_ledger_id' => $ledger->id,
                    'mutation_date' => $expenseDate,
                    'mutation_type' => 'pengeluaran',
                    'source_type' => 'owner_finance_ortho',
                    'description' => $this->buildDescription(
                        'Pengeluaran Carry Forward Owner Finance Ortho',
                        $invoice,
                        $patientName
                    ),
                    'amount' => (float) $ledger->expense_end_month,
                    'reference_month' => $ledgerMonth->toDateString(),
                    'is_system_generated' => true,
                ]);
            }
        }
    }

    private function syncOwnerAccountMutationsForProstho(
        OwnerFinanceCase $case,
        Collection $ledgers,
        Carbon $realTransactionDate,
        ?Carbon $recognizedAt,
        float $labBillAmount,
        float $clinicIncomeAmount
    ): void {
        $invoice = trim((string) ($case->incomeTransaction?->invoice_number ?? ''));
        $patientName = trim((string) ($case->incomeTransaction?->patient?->name ?? ''));
        $today = now()->toDateString();

        foreach ($ledgers->values() as $index => $ledger) {
            $ledgerMonth = Carbon::parse($ledger->ledger_month)->startOfMonth();
            $isRecognitionMonth = (bool) $ledger->is_closed;

            $incomeDate = $index === 0
                ? $realTransactionDate->toDateString()
                : $ledgerMonth->copy()->startOfMonth()->toDateString();

            $incomeDescription = $index === 0
                ? $this->buildDescription('Pemasukan Owner Finance Kasus Khusus', $invoice, $patientName)
                : $this->buildDescription('Pemasukan Carry Forward Owner Finance Kasus Khusus', $invoice, $patientName);

            if ((float) $ledger->income_amount > 0) {
                OwnerAccountMutation::create([
                    'owner_finance_case_id' => $case->id,
                    'owner_finance_monthly_ledger_id' => $ledger->id,
                    'mutation_date' => $incomeDate,
                    'mutation_type' => 'pemasukan',
                    'source_type' => 'owner_finance_special_case',
                    'description' => $incomeDescription,
                    'amount' => (float) $ledger->income_amount,
                    'reference_month' => $ledgerMonth->toDateString(),
                    'is_system_generated' => true,
                ]);
            }

            if ($isRecognitionMonth) {
                $recognitionDate = ($recognizedAt ?? $ledgerMonth->copy()->endOfMonth())->toDateString();

                if ($labBillAmount > 0) {
                    OwnerAccountMutation::create([
                        'owner_finance_case_id' => $case->id,
                        'owner_finance_monthly_ledger_id' => $ledger->id,
                        'mutation_date' => $recognitionDate,
                        'mutation_type' => 'pengeluaran',
                        'source_type' => 'owner_finance_lab_payment',
                        'description' => $this->buildDescription(
                            'Pembayaran Dental Laboratory Kasus Khusus',
                            $invoice,
                            $patientName
                        ),
                        'amount' => $labBillAmount,
                        'reference_month' => $ledgerMonth->toDateString(),
                        'is_system_generated' => true,
                    ]);
                }

                if ($clinicIncomeAmount > 0) {
                    OwnerAccountMutation::create([
                        'owner_finance_case_id' => $case->id,
                        'owner_finance_monthly_ledger_id' => $ledger->id,
                        'mutation_date' => $recognitionDate,
                        'mutation_type' => 'pengeluaran',
                        'source_type' => 'owner_finance_clinic_income_release',
                        'description' => $this->buildDescription(
                            'Pengakuan Pendapatan Klinik Kasus Khusus',
                            $invoice,
                            $patientName
                        ),
                        'amount' => $clinicIncomeAmount,
                        'reference_month' => $ledgerMonth->toDateString(),
                        'is_system_generated' => true,
                    ]);
                }

                continue;
            }

            $expenseDate = $ledgerMonth->copy()->endOfMonth()->toDateString();

            if ((float) $ledger->expense_end_month > 0 && $expenseDate <= $today) {
                OwnerAccountMutation::create([
                    'owner_finance_case_id' => $case->id,
                    'owner_finance_monthly_ledger_id' => $ledger->id,
                    'mutation_date' => $expenseDate,
                    'mutation_type' => 'pengeluaran',
                    'source_type' => 'owner_finance_special_case_carry_forward',
                    'description' => $this->buildDescription(
                        'Pengeluaran Carry Forward Owner Finance Kasus Khusus',
                        $invoice,
                        $patientName
                    ),
                    'amount' => (float) $ledger->expense_end_month,
                    'reference_month' => $ledgerMonth->toDateString(),
                    'is_system_generated' => true,
                ]);
            }
        }
    }

    private function buildDescription(string $prefix, string $invoice, string $patientName): string
    {
        $parts = [$prefix];

        if ($invoice !== '') {
            $parts[] = $invoice;
        }

        if ($patientName !== '') {
            $parts[] = $patientName;
        }

        return implode(' - ', $parts);
    }

    private function buildLedgerNotes(
        Carbon $currentMonth,
        Carbon $startMonth,
        float $installmentPaid,
        float $closingBalance,
        bool $isClosed
    ): string {
        if ($isClosed) {
            return 'Kasus selesai pada bulan ini. Tidak ada carry forward ke bulan berikutnya.';
        }

        if ($currentMonth->equalTo($startMonth) && $installmentPaid <= 0) {
            return 'Dana ortho awal masuk pada bulan ini. Karena belum ada cicilan, seluruh saldo dibawa ke bulan berikutnya.';
        }

        if ($installmentPaid <= 0) {
            return 'Belum ada cicilan owner pada bulan ini. Saldo dibawa penuh ke bulan berikutnya.';
        }

        if ($closingBalance > 0) {
            return 'Saldo sisa bulan ini akan menjadi carry forward ke awal bulan berikutnya.';
        }

        return 'Saldo bulan ini sudah habis.';
    }

    private function buildProsthoCarryForwardNotes(OwnerFinanceCase $case): string
    {
        if (!(bool) $case->lab_paid && !(bool) $case->installed) {
            return 'Dana kasus khusus masih ditahan di Owner Finance karena LAB belum dibayar dan kasus belum terpasang. Seluruh saldo dibawa ke bulan berikutnya.';
        }

        if ((bool) $case->lab_paid && !(bool) $case->installed) {
            return 'Dana kasus khusus masih ditahan di Owner Finance karena kasus belum terpasang. Seluruh saldo dibawa ke bulan berikutnya.';
        }

        if (!(bool) $case->lab_paid && (bool) $case->installed) {
            return 'Dana kasus khusus masih ditahan di Owner Finance karena LAB belum dibayar. Seluruh saldo dibawa ke bulan berikutnya.';
        }

        return 'Dana kasus khusus masih ditahan di Owner Finance dan dibawa ke bulan berikutnya.';
    }

    private function buildProsthoRecognitionNotes(float $labBillAmount, float $clinicIncomeAmount): string
    {
        return 'Pendapatan kasus diakui pada bulan ini karena LAB sudah dibayar dan kasus sudah terpasang. Pengeluaran LAB: Rp '
            . number_format($labBillAmount, 0, ',', '.')
            . '. Pendapatan klinik diakui: Rp '
            . number_format($clinicIncomeAmount, 0, ',', '.')
            . '.';
    }
}