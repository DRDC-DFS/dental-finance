<?php

namespace App\Services;

use App\Models\IncomeTransaction;
use App\Models\IncomeTransactionItem;
use App\Models\OwnerFinanceCase;
use Illuminate\Support\Facades\Auth;

class OwnerFinanceCaseService
{
    public function syncForTransaction(IncomeTransaction $transaction): void
    {
        $transaction->loadMissing([
            'items.treatment.category',
            'ownerFinanceCases',
        ]);

        $detectedCaseTypes = [];

        foreach ($transaction->items as $item) {
            $caseType = $this->detectCaseTypeFromTransactionItem($item, $transaction);

            if ($caseType !== null) {
                $detectedCaseTypes[$caseType] = $caseType;
            }
        }

        $detectedCaseTypes = array_values($detectedCaseTypes);

        $existingCases = $transaction->ownerFinanceCases()
            ->get()
            ->keyBy(function ($case) {
                return (string) $case->case_type;
            });

        foreach ($detectedCaseTypes as $detectedCaseType) {
            $existingCase = $existingCases->get($detectedCaseType);

            $case = $existingCase ?: new OwnerFinanceCase();
            $isNew = !$case->exists;

            if ($isNew) {
                $case->income_transaction_id = (int) $transaction->id;
                $case->created_by = Auth::id();
                $case->is_active = true;
            }

            $oldCaseType = (string) ($case->case_type ?? '');
            $case->case_type = $detectedCaseType;

            $this->normalizeCaseFields($case, $oldCaseType, $detectedCaseType);

            if ($isNew) {
                $this->fillDefaultOwnerState($case);
            } else {
                $this->ensureOwnerStateDefaults($case);
            }

            /**
             * PENTING:
             * Setiap sync dari transaksi harus menyesuaikan data turunan
             * agar data lama otomatis mengikuti logika terbaru.
             */
            $this->refreshDerivedFields($case, true);

            if ($this->isCaseDone($case) && empty($case->owner_last_action_note)) {
                $case->owner_last_action_note = 'Kasus selesai';
            }

            $case->updated_by = Auth::id();
            $case->save();
        }

        foreach ($existingCases as $existingType => $existingCase) {
            if (in_array($existingType, $detectedCaseTypes, true)) {
                continue;
            }

            if (!$this->canSafelyDeleteCase($existingCase)) {
                $this->refreshDerivedFields($existingCase, true);
                $existingCase->updated_by = Auth::id();
                $existingCase->save();
                continue;
            }

            $existingCase->delete();
        }
    }

    public function detectCaseTypeFromTransactionItem(IncomeTransactionItem $item, ?IncomeTransaction $transaction = null): ?string
    {
        $item->loadMissing('treatment.category');

        $categoryName = $this->normalizeText((string) data_get($item, 'treatment.category.name', ''));
        $treatmentName = $this->normalizeText((string) data_get($item, 'treatment.name', ''));

        if (
            $this->containsAny($categoryName, ['lab', 'laboratory', 'dental laboratory']) ||
            $this->containsAny($treatmentName, ['lab', 'laboratory', 'dental laboratory'])
        ) {
            return 'lab';
        }

        if (
            $this->containsAny($categoryName, ['retainer']) ||
            $this->containsAny($treatmentName, ['retainer'])
        ) {
            return 'retainer';
        }

        $orthoCaseMode = $this->normalizeOrthoCaseMode((string) ($transaction?->ortho_case_mode ?? 'none'));

        if ($orthoCaseMode === 'lanjutan' && $this->isOrthoRelatedItem($categoryName, $treatmentName)) {
            return 'ortho';
        }

        if ($orthoCaseMode === 'biasa') {
            return null;
        }

        if (
            $orthoCaseMode === 'none' &&
            $this->containsAny($treatmentName, [
                'dp ortho',
                'dp behel',
                'dp aligner',
                'uang muka ortho',
                'uang muka behel',
                'uang muka aligner',
            ])
        ) {
            return 'ortho';
        }

        $prostoCaseMode = $this->normalizeProstoCaseMode((string) ($transaction?->prosto_case_mode ?? 'none'));

        if ($prostoCaseMode === 'lanjutan' && $this->isProstoRelatedItem($categoryName, $treatmentName)) {
            return 'prostodonti';
        }

        if ($prostoCaseMode === 'biasa') {
            return null;
        }

        if (
            $this->containsAny($categoryName, ['prostodonti', 'prostho', 'prostodonsi']) ||
            $this->containsAny($treatmentName, [
                'gigi tiruan',
                'lepasan',
                'bridge',
                'implant',
                'implan',
                'valplast',
                'crown',
                'veneer',
            ])
        ) {
            return 'prostodonti';
        }

        return null;
    }

    public function fillDefaultOwnerState(OwnerFinanceCase $case): void
    {
        $case->needs_setup = true;
        $case->owner_followup_status = 'needs_setup';
        $case->owner_last_action_note = 'Menunggu data owner';
        $case->owner_last_action_at = null;
        $case->case_progress_status = $case->case_type === 'ortho'
            ? 'waiting_setup'
            : 'waiting_owner_setup';
    }

    public function determineProgressStatus(OwnerFinanceCase $case): string
    {
        if ((bool) $case->needs_setup) {
            return $case->case_type === 'ortho'
                ? 'waiting_setup'
                : 'waiting_owner_setup';
        }

        if ($case->case_type === 'ortho') {
            if ((bool) $case->lab_paid && (bool) $case->installed) {
                return 'done';
            }

            if ((bool) $case->lab_paid && !(bool) $case->installed) {
                return 'lab_paid_not_installed';
            }

            if (!(bool) $case->lab_paid && (bool) $case->installed) {
                return 'installed_lab_not_paid';
            }

            $remaining = (float) $case->ortho_remaining_balance;
            $allocation = (float) $case->ortho_allocation_amount;
            $paid = (float) $case->ortho_paid_amount;

            if ($paid > 0 && $remaining > 0) {
                return 'installment_running';
            }

            if ($allocation > 0) {
                return 'remaining_balance';
            }

            return 'waiting_setup';
        }

        if ((bool) $case->lab_paid && (bool) $case->installed) {
            return 'done';
        }

        if ((bool) $case->lab_paid && !(bool) $case->installed) {
            return 'lab_paid_not_installed';
        }

        if (!(bool) $case->lab_paid && (bool) $case->installed) {
            return 'installed_lab_not_paid';
        }

        return 'waiting_lab_payment';
    }

    public function determineFollowupStatus(OwnerFinanceCase $case): string
    {
        if ($this->isCaseDone($case)) {
            return 'done';
        }

        if ((bool) $case->needs_setup) {
            return 'needs_setup';
        }

        if ($case->case_type === 'ortho') {
            $hasSetup = (float) $case->ortho_allocation_amount > 0
                || !empty($case->ortho_payment_mode)
                || !empty($case->ortho_installment_count)
                || (float) $case->ortho_paid_amount > 0
                || (bool) $case->lab_paid
                || (bool) $case->installed;

            return $hasSetup ? 'in_progress' : 'followed_up';
        }

        $hasProgress = (bool) $case->lab_paid
            || (bool) $case->installed
            || !empty($case->prostho_case_type)
            || !empty($case->prostho_case_detail)
            || (float) ($case->lab_bill_amount ?? 0) > 0;

        return $hasProgress ? 'in_progress' : 'followed_up';
    }

    public function isCaseDone(OwnerFinanceCase $case): bool
    {
        return (bool) $case->lab_paid && (bool) $case->installed;
    }

    private function ensureOwnerStateDefaults(OwnerFinanceCase $case): void
    {
        if ($case->needs_setup === null) {
            $case->needs_setup = true;
        }

        if (empty($case->owner_followup_status)) {
            $case->owner_followup_status = 'needs_setup';
        }

        if (empty($case->owner_last_action_note)) {
            $case->owner_last_action_note = 'Menunggu data owner';
        }

        if (empty($case->case_progress_status)) {
            $case->case_progress_status = $case->case_type === 'ortho'
                ? 'waiting_setup'
                : 'waiting_owner_setup';
        }
    }

    private function normalizeCaseFields(OwnerFinanceCase $case, string $oldCaseType, string $newCaseType): void
    {
        if ($newCaseType !== 'ortho' && $oldCaseType === 'ortho') {
            $case->ortho_allocation_amount = 0;
            $case->ortho_payment_mode = null;
            $case->ortho_installment_count = null;
            $case->ortho_paid_amount = 0;
            $case->ortho_remaining_balance = 0;
        }

        if (
            !in_array($newCaseType, ['prostodonti', 'retainer', 'lab'], true) &&
            in_array($oldCaseType, ['prostodonti', 'retainer', 'lab'], true)
        ) {
            $case->prostho_case_type = null;
            $case->prostho_case_detail = null;
            $case->lab_bill_amount = 0;
            $case->clinic_income_amount = 0;
            $case->revenue_recognized_at = null;
        }
    }

    private function calculateOrthoRemainingBalance(OwnerFinanceCase $case): float
    {
        if ($case->case_type !== 'ortho') {
            return 0;
        }

        $allocation = (float) ($case->ortho_allocation_amount ?? 0);
        $paid = (float) ($case->ortho_paid_amount ?? 0);

        return max(0, round($allocation - $paid, 2));
    }

    private function refreshDerivedFields(OwnerFinanceCase $case, bool $forceRewriteFromTransaction = false): void
    {
        $case->loadMissing([
            'incomeTransaction.items.treatment.category',
            'incomeTransaction',
        ]);

        $transaction = $case->incomeTransaction;
        $caseAmount = $this->calculateCaseAmountFromTransaction($case, $transaction);

        if ($case->case_type === 'ortho') {
            /**
             * ATURAN BARU:
             * Saat sync transaksi/update transaksi, alokasi ortho harus
             * selalu mengikuti subtotal item ortho agar data lama ikut diperbaiki.
             */
            if ($forceRewriteFromTransaction) {
                $case->ortho_allocation_amount = round($caseAmount, 2);
            } elseif ((float) ($case->ortho_allocation_amount ?? 0) <= 0) {
                $case->ortho_allocation_amount = round($caseAmount, 2);
            }

            // ortho_paid_amount tetap milik owner, jangan ditimpa otomatis
            $case->ortho_remaining_balance = $this->calculateOrthoRemainingBalance($case);
        } else {
            $case->ortho_remaining_balance = 0;
        }

        if (in_array((string) $case->case_type, ['prostodonti', 'retainer', 'lab'], true)) {
            $autoProsthoCaseType = $this->resolvePrimarySpecialCaseTypeFromTransaction($case, $transaction);

            if (!empty($autoProsthoCaseType)) {
                $case->prostho_case_type = mb_substr($autoProsthoCaseType, 0, 50);
            }

            /**
             * SINGLE SOURCE OF TRUTH:
             * clinic_income_amount dan lab_bill_amount harus disimpan final di DB
             * dari service ini. View/controller tidak boleh hitung ulang lagi.
             */
            $grossCaseAmount = max(0, round($caseAmount, 2));
            $labBillAmount = $this->normalizeLabBillAmount($case, $grossCaseAmount);

            $case->lab_bill_amount = $labBillAmount;
            $case->clinic_income_amount = max(0, round($grossCaseAmount - $labBillAmount, 2));

            /**
             * REVENUE RECOGNIZED:
             * - Diakui hanya jika lab_paid && installed
             * - Gunakan tanggal real terakhir
             * - Bukan tanggal save
             */
            if ((bool) $case->lab_paid && (bool) $case->installed) {
                $case->revenue_recognized_at = $this->resolveRevenueRecognizedAt($case, $transaction);
            } else {
                $case->revenue_recognized_at = null;
            }
        }

        $case->case_progress_status = $this->determineProgressStatus($case);
        $case->owner_followup_status = $this->determineFollowupStatus($case);
    }

    private function calculateCaseAmountFromTransaction(OwnerFinanceCase $case, ?IncomeTransaction $transaction): float
    {
        if (!$transaction) {
            return 0;
        }

        $items = $transaction->items ?? collect();
        $sum = 0.0;
        $targetCaseType = (string) ($case->case_type ?? '');

        foreach ($items as $item) {
            $itemCaseType = $this->detectCaseTypeFromTransactionItem($item, $transaction);

            if ($itemCaseType === $targetCaseType) {
                $sum += (float) ($item->subtotal ?? 0);
            }
        }

        return round($sum, 2);
    }


    private function resolvePrimarySpecialCaseTypeFromTransaction(OwnerFinanceCase $case, ?IncomeTransaction $transaction): ?string
    {
        if (!$transaction) {
            return !empty($case->prostho_case_type) ? trim((string) $case->prostho_case_type) : null;
        }

        $items = $transaction->items ?? collect();
        $targetCaseType = (string) ($case->case_type ?? '');

        foreach ($items as $item) {
            $itemCaseType = $this->detectCaseTypeFromTransactionItem($item, $transaction);
            $treatmentName = trim((string) data_get($item, 'treatment.name', ''));

            if ($itemCaseType === $targetCaseType && $treatmentName !== '') {
                return $treatmentName;
            }
        }

        return !empty($case->prostho_case_type) ? trim((string) $case->prostho_case_type) : null;
    }

    private function normalizeLabBillAmount(OwnerFinanceCase $case, float $grossCaseAmount): float
    {
        $labBillAmount = max(0, (float) ($case->lab_bill_amount ?? 0));

        if ($grossCaseAmount <= 0) {
            return 0;
        }

        if ($labBillAmount > $grossCaseAmount) {
            $labBillAmount = $grossCaseAmount;
        }

        return round($labBillAmount, 2);
    }

    private function resolveRevenueRecognizedAt(OwnerFinanceCase $case, ?IncomeTransaction $transaction = null)
    {
        $labPaidAt = $this->resolveRealDateValue($case, [
            'lab_paid_at',
            'lab_payment_date',
            'lab_paid_date',
            'tanggal_bayar_lab',
            'lab_payment_at',
        ]);

        $installedAt = $this->resolveRealDateValue($case, [
            'installed_at',
            'installation_date',
            'installed_date',
            'tanggal_pemasangan',
            'placement_date',
        ]);

        $timestamps = array_values(array_filter([
            $labPaidAt ? strtotime($labPaidAt) : null,
            $installedAt ? strtotime($installedAt) : null,
        ]));

        if (!empty($timestamps)) {
            return date('Y-m-d H:i:s', max($timestamps));
        }

        /**
         * Fallback aman:
         * Bila field tanggal real belum terisi/berbeda nama di data lama,
         * gunakan trx_date agar tetap pakai tanggal bisnis, bukan tanggal save/update.
         */
        $trxDate = $transaction?->trx_date ?: null;

        if (!empty($trxDate)) {
            return $trxDate;
        }

        return null;
    }

    private function resolveRealDateValue(OwnerFinanceCase $case, array $candidateFields): ?string
    {
        foreach ($candidateFields as $field) {
            $value = $case->getAttribute($field);

            if (empty($value)) {
                continue;
            }

            $timestamp = strtotime((string) $value);

            if ($timestamp === false) {
                continue;
            }

            return date('Y-m-d H:i:s', $timestamp);
        }

        return null;
    }

    private function canSafelyDeleteCase(OwnerFinanceCase $case): bool
    {
        if ((bool) $case->lab_paid || (bool) $case->installed) {
            return false;
        }

        if ((float) ($case->ortho_allocation_amount ?? 0) > 0) {
            return false;
        }

        if ((float) ($case->ortho_paid_amount ?? 0) > 0) {
            return false;
        }

        if ((float) ($case->lab_bill_amount ?? 0) > 0) {
            return false;
        }

        if ((float) ($case->clinic_income_amount ?? 0) > 0) {
            return false;
        }

        if (!empty($case->prostho_case_type) || !empty($case->prostho_case_detail)) {
            return false;
        }

        if (!empty($case->owner_private_notes)) {
            return false;
        }

        return true;
    }

    private function isOrthoRelatedItem(string $categoryName, string $treatmentName): bool
    {
        if ($this->containsAny($categoryName, ['retainer', 'lab', 'laboratory', 'dental laboratory'])) {
            return false;
        }

        if ($this->containsAny($treatmentName, ['retainer', 'lab', 'laboratory', 'dental laboratory'])) {
            return false;
        }

        return $this->containsAny($categoryName, ['ortho', 'ortho', 'behel', 'aligner', 'orthodonti', 'ortodonti']) ||
            $this->containsAny($treatmentName, ['ortho', 'ortho', 'behel', 'aligner', 'bracket', 'orthodonti', 'ortodonti']);
    }

    private function isProstoRelatedItem(string $categoryName, string $treatmentName): bool
    {
        if ($this->containsAny($categoryName, ['retainer', 'lab', 'laboratory', 'dental laboratory'])) {
            return false;
        }

        if ($this->containsAny($treatmentName, ['retainer', 'lab', 'laboratory', 'dental laboratory'])) {
            return false;
        }

        return $this->containsAny($categoryName, ['prostodonti', 'prostho', 'prostodonsi']) ||
            $this->containsAny($treatmentName, [
                'gigi tiruan',
                'lepasan',
                'bridge',
                'implant',
                'implan',
                'valplast',
                'crown',
                'veneer',
            ]);
    }

    private function normalizeOrthoCaseMode(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['none', 'biasa', 'lanjutan'], true) ? $value : 'none';
    }

    private function normalizeProstoCaseMode(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['none', 'biasa', 'lanjutan'], true) ? $value : 'none';
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $this->normalizeText($needle))) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text);

        return (string) $text;
    }
}