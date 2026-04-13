<?php

namespace App\Http\Controllers\Income;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\IncomeTransaction;
use App\Models\IncomeTransactionItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Treatment;
use App\Services\OwnerFinanceCaseService;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IncomeTransactionController extends Controller
{
    private function currentRole(): string
    {
        return strtolower((string) (Auth::user()->role ?? ''));
    }

    private function ensureOwnerOrAdmin(): void
    {
        $user = Auth::user();
        $role = strtolower((string) ($user->role ?? ''));

        if (!$user || !in_array($role, ['owner', 'admin'], true)) {
            abort(403, 'Hanya OWNER atau ADMIN yang boleh mengakses modul pemasukan.');
        }
    }

    private function ensureOwnerOnly(): void
    {
        $user = Auth::user();
        $role = strtolower((string) ($user->role ?? ''));

        if (!$user || $role !== 'owner') {
            abort(403, 'Hanya OWNER yang boleh melakukan aksi ini.');
        }
    }

    private function isBpjs(?string $payerType): bool
    {
        return strtolower(trim((string) $payerType)) === 'bpjs';
    }

    private function isKhusus(?string $payerType): bool
    {
        return strtolower(trim((string) $payerType)) === 'khusus';
    }

    private function normalizeOrthoCaseMode(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['none', 'biasa', 'lanjutan'], true) ? $value : 'none';
    }

    private function normalizeProstoCaseMode(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['none', 'biasa', 'lanjutan'], true) ? $value : 'none';
    }


    private function normalizeNeedsLabLetter($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'ya', 'yes', 'on'], true);
    }

    private function normalizeLabKeyword(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = str_replace(['(', ')', '-', '_', '/', '\\', ','], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value ?? '');

        return trim((string) $value);
    }

    private function treatmentLooksLikeProstoLab(?Treatment $treatment): bool
    {
        if (!$treatment) {
            return false;
        }

        if ((bool) ($treatment->is_prosto_related ?? false)) {
            return true;
        }

        $name = $this->normalizeLabKeyword($treatment->name ?? '');

        $keywords = [
            'gigi palsu',
            'gtsl',
            'gtjl',
            'protesa',
            'prostodonti',
            'prosto',
            'denture',
            'akrilik',
            'frame',
            'impression',
            'cetak',
            'sendok cetak',
            'wax bite',
            'bite rim',
            'try in',
            'reparasi gigi palsu',
            'perbaikan gigi palsu',
            'pemasangan gigi palsu',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($name, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function transactionNeedsLabDetection(IncomeTransaction $incomeTransaction): bool
    {
        $items = $incomeTransaction->relationLoaded('items')
            ? $incomeTransaction->items
            : $incomeTransaction->items()->with('treatment')->get();

        foreach ($items as $item) {
            if ($this->treatmentLooksLikeProstoLab($item->treatment ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function ensureLabEligibleTransaction(IncomeTransaction $incomeTransaction): void
    {
        $incomeTransaction->loadMissing(['items.treatment']);

        if (!$this->transactionNeedsLabDetection($incomeTransaction)) {
            abort(403, 'Surat LAB hanya tersedia untuk transaksi Prosto / gigi palsu yang relevan.');
        }
    }

    private function nextLabLetterNumber(IncomeTransaction $incomeTransaction): string
    {
        $date = $incomeTransaction->trx_date ? $incomeTransaction->trx_date->format('Ymd') : now()->format('Ymd');

        $todayCount = (int) DB::table('income_transactions')
            ->whereDate('trx_date', $incomeTransaction->trx_date ? $incomeTransaction->trx_date->format('Y-m-d') : now()->toDateString())
            ->whereNotNull('lab_letter_number')
            ->count();

        $sequence = str_pad((string) ($todayCount + 1), 3, '0', STR_PAD_LEFT);

        return 'LAB-' . $date . '-' . $sequence;
    }

    private function inferLabActionType(IncomeTransaction $incomeTransaction): string
    {
        $incomeTransaction->loadMissing(['items.treatment']);

        foreach ($incomeTransaction->items as $item) {
            $treatment = $item->treatment;
            if ($this->treatmentLooksLikeProstoLab($treatment)) {
                return (string) ($treatment->name ?? '');
            }
        }

        return '-';
    }

    private function buildLabPayload(IncomeTransaction $incomeTransaction): array
    {
        $incomeTransaction->loadMissing(['patient', 'doctor', 'items.treatment']);

        $setting = Setting::query()->first();
        $labLetterNumber = trim((string) ($incomeTransaction->lab_letter_number ?? ''));
        if ($labLetterNumber === '') {
            $labLetterNumber = $this->nextLabLetterNumber($incomeTransaction);
        }

        $labLetterDate = $incomeTransaction->lab_letter_date
            ? \Illuminate\Support\Carbon::parse($incomeTransaction->lab_letter_date)
            : ($incomeTransaction->trx_date ?: now());

        $doctorName = trim((string) ($incomeTransaction->doctor?->name ?? ''));
        if ($doctorName === '') {
            $doctorName = trim((string) ($setting?->owner_doctor_name ?? 'drg. Desly A.C. Luhulima, M.K.M'));
        }

        return [
            'incomeTransaction' => $incomeTransaction,
            'setting' => $setting,
            'labLetterNumber' => $labLetterNumber,
            'labLetterDate' => $labLetterDate,
            'labDoctorName' => $doctorName,
            'labActionType' => trim((string) ($incomeTransaction->lab_action_type ?? '')) !== ''
                ? (string) $incomeTransaction->lab_action_type
                : $this->inferLabActionType($incomeTransaction),
        ];
    }

    private function syncOwnerFinanceCase(IncomeTransaction $incomeTransaction): void
    {
        app(OwnerFinanceCaseService::class)->syncForTransaction($incomeTransaction);
    }

    private function resolveVerifyBaseUrl(): string
    {
        $appUrl = rtrim(trim((string) config('app.url', '')), '/');

        if ($appUrl !== '') {
            return $appUrl;
        }

        return rtrim((string) url('/'), '/');
    }

    private function buildVerifyUrl(IncomeTransaction $incomeTransaction): string
    {
        $path = route('income.invoice.verify', [
            'income' => $incomeTransaction->id,
            'code'   => (string) $incomeTransaction->receipt_verify_code,
        ], false);

        return $this->resolveVerifyBaseUrl() . $path;
    }

    private function redirectToNewTransactionForm(string $message)
    {
        return redirect()
            ->route('income.create')
            ->with('success', $message);
    }

    private function sanitizeDiscount(float $grossSubtotal, $discountRaw): float
    {
        $discount = (float) clean_rupiah((string) ($discountRaw ?? '0'));

        if ($discount < 0) {
            $discount = 0;
        }

        if ($discount > $grossSubtotal) {
            $discount = $grossSubtotal;
        }

        return round($discount, 2);
    }

    /**
     * Rule edit:
     * - OWNER: boleh edit DRAFT + PAID
     * - ADMIN: hanya boleh edit DRAFT
     */
    private function ensureEditableByUser(IncomeTransaction $incomeTransaction): void
    {
        $role = $this->currentRole();

        $status = strtolower(trim((string) ($incomeTransaction->status ?? '')));
        if ($status === '') {
            $status = 'draft';
        }

        if ($role === 'owner') {
            if (!in_array($status, ['draft', 'paid'], true)) {
                abort(403, 'Transaksi tidak bisa diubah untuk status ini.');
            }
            return;
        }

        if ($role === 'admin') {
            if ($status !== 'draft') {
                abort(403, 'ADMIN hanya boleh mengubah transaksi dengan status DRAFT.');
            }
            return;
        }

        abort(403, 'Anda tidak memiliki akses untuk mengubah transaksi ini.');
    }

    private function normalizeTreatmentName(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/\s+/', ' ', $value ?? '');

        return trim((string) $value);
    }

    private function isIncludedProstoPackageTreatment(?Treatment $treatment): bool
    {
        $name = $this->normalizeTreatmentName($treatment?->name);

        $allowed = [
            'pemasangan gigi palsu',
            'perbaikan gigi palsu',
            'pemasangan gigi palsu (gtsl)',
            'perbaikan gigi palsu (gtsl)',
            'pemasangan gigi palsu (gtsl) (1x)',
            'perbaikan gigi palsu (gtsl) (1x)',
            'pemasangan gigi palsu (gtjl)',
            'perbaikan gigi palsu (gtjl)',
            'pemasangan gigi palsu (gtjl) (1x)',
            'perbaikan gigi palsu (gtjl) (1x)',
        ];

        if (in_array($name, $allowed, true)) {
            return true;
        }

        return str_contains($name, 'pemasangan gigi palsu')
            || str_contains($name, 'perbaikan gigi palsu');
    }

    private function treatmentAllowsZeroPrice(?Treatment $treatment): bool
    {
        if (!$treatment) {
            return false;
        }

        if ((bool) ($treatment->is_free ?? false)) {
            return true;
        }

        if ((bool) ($treatment->allow_zero_price ?? false)) {
            return true;
        }

        return $this->isIncludedProstoPackageTreatment($treatment);
    }

    private function determineZeroReason(
        ?Treatment $treatment,
        bool $isBpjs,
        float $unitPrice,
        float $grossSubtotal,
        float $discountAmount,
        float $subtotal
    ): ?string {
        if ($isBpjs) {
            return null;
        }

        if ($subtotal > 0) {
            return null;
        }

        if ((bool) ($treatment?->is_free ?? false)) {
            return 'free_medical';
        }

        if ($grossSubtotal > 0 && $discountAmount >= $grossSubtotal) {
            return 'discount';
        }

        if ($unitPrice <= 0) {
            return 'manual_zero';
        }

        return null;
    }

    private function transactionAllowsZeroBillCompletion(IncomeTransaction $incomeTransaction): bool
    {
        if ($this->isBpjs($incomeTransaction->payer_type ?? 'umum')) {
            return false;
        }

        if ($this->isKhusus($incomeTransaction->payer_type ?? 'umum')) {
            return false;
        }

        $rows = DB::table('income_transaction_items as iti')
            ->leftJoin('treatments as t', 't.id', '=', 'iti.treatment_id')
            ->where('iti.transaction_id', (int) $incomeTransaction->id)
            ->get([
                'iti.subtotal',
                't.name as treatment_name',
                't.allow_zero_price',
                't.is_free',
            ]);

        if ($rows->count() < 1) {
            return false;
        }

        foreach ($rows as $row) {
            $subtotal = (float) ($row->subtotal ?? 0);
            $allowZeroPrice = (bool) ($row->allow_zero_price ?? false);
            $isFree = (bool) ($row->is_free ?? false);
            $treatmentName = $this->normalizeTreatmentName((string) ($row->treatment_name ?? ''));

            if ($subtotal != 0.0) {
                return false;
            }

            if (
                !$isFree &&
                !$allowZeroPrice &&
                !str_contains($treatmentName, 'pemasangan gigi palsu') &&
                !str_contains($treatmentName, 'perbaikan gigi palsu')
            ) {
                return false;
            }
        }

        return true;
    }

    public function index(Request $request)
    {
        $this->ensureOwnerOrAdmin();

        $role = $this->currentRole();
        $today = now()->toDateString();

        if ($role === 'admin') {
            $request->validate([
                'date' => ['nullable', 'date'],
            ], [
                'date.date' => 'Tanggal tidak valid.',
            ]);

            $date = $request->date ?: $today;

            $query = IncomeTransaction::with(['patient', 'doctor']);
            $query->whereDate('trx_date', $date);

            $transactions = $query
                ->orderByDesc('trx_date')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();

            return view('income.index', [
                'transactions' => $transactions,
                'date'         => $date,
                'dateStart'    => $date,
                'dateEnd'      => $date,
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

        $query = IncomeTransaction::with(['patient', 'doctor']);

        if ($dateStart) {
            $query->whereDate('trx_date', '>=', $dateStart);
        }

        if ($dateEnd) {
            $query->whereDate('trx_date', '<=', $dateEnd);
        }

        $transactions = $query
            ->orderByDesc('trx_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('income.index', [
            'transactions' => $transactions,
            'dateStart'    => $dateStart,
            'dateEnd'      => $dateEnd,
        ]);
    }

    public function create()
    {
        $this->ensureOwnerOrAdmin();

        $doctors = Doctor::where('is_active', 1)->orderBy('name')->get();

        return view('income.create', compact('doctors'));
    }

    public function store(Request $request)
    {
        $this->ensureOwnerOrAdmin();

        $data = $request->validate([
            'doctor_id'        => ['required', 'exists:doctors,id'],
            'patient_name'     => ['required', 'string', 'max:150'],
            'patient_phone'    => ['nullable', 'string', 'max:50'],
            'payer_type'       => ['required', 'in:umum,bpjs,khusus'],
            'ortho_case_mode'  => ['nullable', 'in:none,biasa,lanjutan'],
            'prosto_case_mode' => ['nullable', 'in:none,biasa,lanjutan'],
            'trx_date'         => ['nullable', 'date'],
            'notes'            => ['nullable', 'string'],
            'visibility'       => ['nullable', 'in:public,private'],
            'needs_lab_letter' => ['nullable', 'in:0,1'],
        ]);

        return DB::transaction(function () use ($data) {
            $patient = Patient::firstOrCreate([
                'name'  => $data['patient_name'],
                'phone' => $data['patient_phone'] ?? null,
            ]);

            $trxDate = $data['trx_date'] ?? now()->toDateString();
            $invoice = 'INV-' . now()->format('Ymd-His') . '-' . random_int(100, 999);
            $role = $this->currentRole();

            $visibility = $role === 'admin'
                ? 'public'
                : ($data['visibility'] ?? 'public');

            $trx = IncomeTransaction::create([
                'invoice_number'      => $invoice,
                'trx_date'            => $trxDate,
                'doctor_id'           => (int) $data['doctor_id'],
                'patient_id'          => $patient->id,
                'payer_type'          => $data['payer_type'],
                'ortho_case_mode'     => $this->normalizeOrthoCaseMode($data['ortho_case_mode'] ?? 'none'),
                'prosto_case_mode'    => $this->normalizeProstoCaseMode($data['prosto_case_mode'] ?? 'none'),
                'status'              => 'draft',
                'bill_total'          => 0,
                'doctor_fee_total'    => 0,
                'pay_total'           => 0,
                'visibility'          => $visibility,
                'notes'               => $data['notes'] ?? null,
                'needs_lab_letter'    => $this->normalizeNeedsLabLetter($data['needs_lab_letter'] ?? false),
                'created_by'          => Auth::id(),
                'receipt_verify_code' => null,
                'receipt_pdf_path'    => null,
            ]);

            return redirect()
                ->route('income.edit', ['income' => $trx->id])
                ->with('success', 'Transaksi berhasil dibuat (DRAFT).');
        });
    }

    public function edit(IncomeTransaction $income)
    {
        $this->ensureOwnerOrAdmin();

        $incomeTransaction = $income;

        $incomeTransaction->load(['patient', 'doctor', 'items.treatment']);

        $treatments = Treatment::where('is_active', 1)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'price',
                'price_mode',
                'allow_zero_price',
                'is_free',
                'is_ortho_related',
                'is_prosto_related',
                'unit',
                'notes_hint',
            ]);

        $doctors = Doctor::where('is_active', 1)->orderBy('name')->get();

        $paymentMethods = DB::table('payment_methods')
            ->select('id', 'name')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        $payments = DB::table('payments as p')
            ->join('payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->where('p.transaction_id', (int) $incomeTransaction->id)
            ->orderBy('p.id', 'asc')
            ->get([
                'p.id',
                'p.pay_date',
                'pm.name as method_name',
                'p.channel',
                'p.amount',
                'p.created_at',
            ]);

        return view('income.edit', [
            'incomeTransaction' => $incomeTransaction,
            'treatments'        => $treatments,
            'doctors'           => $doctors,
            'paymentMethods'    => $paymentMethods,
            'payments'          => $payments,
        ]);
    }

    public function update(Request $request, IncomeTransaction $income)
    {
        $this->ensureOwnerOrAdmin();

        $incomeTransaction = $income;
        $this->ensureEditableByUser($incomeTransaction);

        $data = $request->validate([
            'trx_date'         => ['required', 'date'],
            'doctor_id'        => ['required', 'exists:doctors,id'],
            'patient_name'     => ['required', 'string', 'max:150'],
            'patient_phone'    => ['nullable', 'string', 'max:50'],
            'payer_type'       => ['required', 'in:umum,bpjs,khusus'],
            'ortho_case_mode'  => ['nullable', 'in:none,biasa,lanjutan'],
            'prosto_case_mode' => ['nullable', 'in:none,biasa,lanjutan'],
            'notes'            => ['nullable', 'string'],
            'visibility'       => ['required', 'in:public,private'],
            'needs_lab_letter' => ['nullable', 'in:0,1'],
        ]);

        return DB::transaction(function () use ($incomeTransaction, $data) {
            $patient = Patient::firstOrCreate([
                'name'  => $data['patient_name'],
                'phone' => $data['patient_phone'] ?? null,
            ]);

            $payerTypeBefore = (string) ($incomeTransaction->payer_type ?? 'umum');
            $payerTypeAfter = (string) $data['payer_type'];

            $incomeTransaction->update([
                'trx_date'         => $data['trx_date'],
                'doctor_id'        => (int) $data['doctor_id'],
                'patient_id'       => $patient->id,
                'payer_type'       => $payerTypeAfter,
                'ortho_case_mode'  => $this->normalizeOrthoCaseMode($data['ortho_case_mode'] ?? 'none'),
                'prosto_case_mode' => $this->normalizeProstoCaseMode($data['prosto_case_mode'] ?? 'none'),
                'notes'            => $data['notes'] ?? null,
                'visibility'       => $data['visibility'],
                'needs_lab_letter' => $this->normalizeNeedsLabLetter($data['needs_lab_letter'] ?? false),
            ]);

            if ($this->isBpjs($payerTypeAfter)) {
                DB::table('payments')
                    ->where('transaction_id', (int) $incomeTransaction->id)
                    ->delete();

                DB::table('income_transaction_items')
                    ->where('transaction_id', (int) $incomeTransaction->id)
                    ->update([
                        'unit_price' => 0,
                        'discount_amount' => 0,
                        'subtotal' => 0,
                        'fee_amount' => 0,
                        'zero_reason' => null,
                    ]);
            } elseif ($this->isBpjs($payerTypeBefore) && !$this->isBpjs($payerTypeAfter)) {
                DB::table('income_transaction_items')
                    ->where('transaction_id', (int) $incomeTransaction->id)
                    ->update([
                        'unit_price' => 0,
                        'discount_amount' => 0,
                        'subtotal' => 0,
                        'fee_amount' => 0,
                        'zero_reason' => null,
                    ]);
            }

            $this->recalcAllItemFees($incomeTransaction);
            $this->recalcTotalsAndSyncStatus($incomeTransaction);
            $this->syncOwnerFinanceCase($incomeTransaction);

            return redirect()
                ->route('income.edit', ['income' => $incomeTransaction->id])
                ->with('success', 'Header transaksi berhasil diupdate.');
        });
    }

    public function destroy(IncomeTransaction $income)
    {
        $this->ensureOwnerOnly();

        $incomeTransaction = $income;
        $this->ensureEditableByUser($incomeTransaction);

        return DB::transaction(function () use ($incomeTransaction) {
            DB::table('payments')->where('transaction_id', (int) $incomeTransaction->id)->delete();
            DB::table('income_transaction_items')->where('transaction_id', (int) $incomeTransaction->id)->delete();

            $incomeTransaction->delete();

            return redirect()
                ->route('income.index')
                ->with('success', 'Transaksi berhasil dihapus (termasuk items & payments).');
        });
    }

    public function storeItem(Request $request, IncomeTransaction $incomeTransaction)
    {
        $this->ensureOwnerOrAdmin();
        $this->ensureEditableByUser($incomeTransaction);

        $data = $request->validate([
            'treatment_id'     => ['required', 'exists:treatments,id'],
            'qty'              => ['required', 'numeric', 'min:0.01'],
            'unit_price'       => ['nullable', 'string'],
            'discount_amount'  => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($incomeTransaction, $data) {
            $qty = (float) $data['qty'];
            $isBpjs = $this->isBpjs($incomeTransaction->payer_type ?? 'umum');

            $treatment = Treatment::query()->findOrFail((int) $data['treatment_id']);
            $priceMode = strtolower((string) ($treatment->price_mode ?? 'fixed'));
            $allowZeroPrice = $this->treatmentAllowsZeroPrice($treatment);
            $isFree = (bool) ($treatment->is_free ?? false);

            if ($isBpjs) {
                $unitPrice = 0.0;
            } elseif ($isFree) {
                $unitPrice = 0.0;
            } elseif ($priceMode === 'manual') {
                $unitPrice = (float) clean_rupiah((string) ($data['unit_price'] ?? '0'));

                if ($unitPrice <= 0 && !$allowZeroPrice) {
                    return redirect()
                        ->to(route('income.edit', ['income' => $incomeTransaction->id]) . '#add-item-form')
                        ->withErrors(['unit_price' => 'Harga manual wajib diisi dan harus lebih dari 0, kecuali treatment ini memang diizinkan harga 0.'])
                        ->withInput();
                }

                if ($unitPrice <= 0 && $allowZeroPrice) {
                    $unitPrice = 0.0;
                }
            } else {
                $masterPrice = (float) ($treatment->price ?? 0);
                $inputPrice = (float) clean_rupiah((string) ($data['unit_price'] ?? '0'));
                $unitPrice = $inputPrice > 0 ? $inputPrice : $masterPrice;

                if ($unitPrice <= 0 && $allowZeroPrice) {
                    $unitPrice = 0.0;
                }

                if ($unitPrice <= 0 && !$allowZeroPrice) {
                    return redirect()
                        ->to(route('income.edit', ['income' => $incomeTransaction->id]) . '#add-item-form')
                        ->withErrors(['unit_price' => 'Harga treatment fixed di master masih 0. Silakan perbaiki Master Tindakan terlebih dahulu atau aktifkan izin harga 0 untuk treatment ini.'])
                        ->withInput();
                }
            }

            $grossSubtotal = $isBpjs ? 0 : round($qty * $unitPrice, 2);
            $discountAmount = $isBpjs ? 0 : $this->sanitizeDiscount($grossSubtotal, $data['discount_amount'] ?? '0');
            $subtotal = $isBpjs ? 0 : round(max(0, $grossSubtotal - $discountAmount), 2);
            $zeroReason = $this->determineZeroReason(
                $treatment,
                $isBpjs,
                (float) $unitPrice,
                (float) $grossSubtotal,
                (float) $discountAmount,
                (float) $subtotal
            );

            $feeAmount = $isBpjs
                ? 0
                : $this->calcFeeAmount(
                    (int) $incomeTransaction->doctor_id,
                    (int) $data['treatment_id'],
                    $subtotal
                );

            IncomeTransactionItem::create([
                'transaction_id'  => (int) $incomeTransaction->id,
                'treatment_id'    => (int) $data['treatment_id'],
                'qty'             => $qty,
                'unit_price'      => $unitPrice,
                'discount_amount' => $discountAmount,
                'subtotal'        => $subtotal,
                'fee_amount'      => $feeAmount,
                'zero_reason'     => $zeroReason,
            ]);

            $this->syncOwnerFinanceCase($incomeTransaction);
            $this->recalcTotalsAndSyncStatus($incomeTransaction);

            return redirect()
                ->to(route('income.edit', ['income' => $incomeTransaction->id]) . '#add-item-form')
                ->with('success', 'Item tindakan berhasil ditambahkan.');
        });
    }

    public function updateItem(Request $request, IncomeTransaction $incomeTransaction, IncomeTransactionItem $item)
    {
        $this->ensureOwnerOrAdmin();
        $this->ensureEditableByUser($incomeTransaction);

        if ((int) $item->transaction_id !== (int) $incomeTransaction->id) {
            abort(404);
        }

        $data = $request->validate([
            'qty'             => ['required', 'numeric', 'min:0.01'],
            'unit_price'      => ['nullable', 'string'],
            'discount_amount' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($incomeTransaction, $item, $data) {
            $qty = (float) $data['qty'];
            $isBpjs = $this->isBpjs($incomeTransaction->payer_type ?? 'umum');

            $treatment = Treatment::query()->find((int) $item->treatment_id);
            $priceMode = strtolower((string) ($treatment->price_mode ?? 'fixed'));
            $allowZeroPrice = $this->treatmentAllowsZeroPrice($treatment);
            $isFree = (bool) ($treatment->is_free ?? false);

            if ($isBpjs) {
                $unitPrice = 0.0;
            } elseif ($isFree) {
                $unitPrice = 0.0;
            } elseif ($priceMode === 'manual') {
                $unitPrice = (float) clean_rupiah((string) ($data['unit_price'] ?? '0'));

                if ($unitPrice <= 0 && !$allowZeroPrice) {
                    return redirect()
                        ->route('income.edit', ['income' => $incomeTransaction->id])
                        ->withErrors(['unit_price' => 'Harga manual wajib diisi dan harus lebih dari 0, kecuali treatment ini memang diizinkan harga 0.'])
                        ->withInput();
                }

                if ($unitPrice <= 0 && $allowZeroPrice) {
                    $unitPrice = 0.0;
                }
            } else {
                $inputPrice = (float) clean_rupiah((string) ($data['unit_price'] ?? '0'));
                $masterPrice = (float) ($treatment->price ?? 0);
                $unitPrice = $inputPrice > 0 ? $inputPrice : ($masterPrice > 0 ? $masterPrice : (float) $item->unit_price);

                if ($unitPrice <= 0 && $allowZeroPrice) {
                    $unitPrice = 0.0;
                }

                if ($unitPrice <= 0 && !$allowZeroPrice) {
                    return redirect()
                        ->route('income.edit', ['income' => $incomeTransaction->id])
                        ->withErrors(['unit_price' => 'Harga treatment fixed di master masih 0. Silakan perbaiki Master Tindakan terlebih dahulu atau aktifkan izin harga 0 untuk treatment ini.'])
                        ->withInput();
                }
            }

            $grossSubtotal = $isBpjs ? 0 : round($qty * $unitPrice, 2);
            $discountAmount = $isBpjs ? 0 : $this->sanitizeDiscount($grossSubtotal, $data['discount_amount'] ?? '0');
            $subtotal = $isBpjs ? 0 : round(max(0, $grossSubtotal - $discountAmount), 2);
            $zeroReason = $this->determineZeroReason(
                $treatment,
                $isBpjs,
                (float) $unitPrice,
                (float) $grossSubtotal,
                (float) $discountAmount,
                (float) $subtotal
            );

            $feeAmount = 0;
            if (!$isBpjs) {
                $feeRow = DB::table('doctor_treatment_fees')
                    ->where('doctor_id', (int) $incomeTransaction->doctor_id)
                    ->where('treatment_id', (int) $item->treatment_id)
                    ->first(['fee_type']);

                $feeAmount = (float) $item->fee_amount;
                if (!$feeRow || strtolower((string) ($feeRow->fee_type ?? '')) !== 'manual') {
                    $feeAmount = $this->calcFeeAmount(
                        (int) $incomeTransaction->doctor_id,
                        (int) $item->treatment_id,
                        $subtotal
                    );
                }
            }

            $item->update([
                'qty'             => $qty,
                'unit_price'      => $unitPrice,
                'discount_amount' => $discountAmount,
                'subtotal'        => $subtotal,
                'fee_amount'      => $feeAmount,
                'zero_reason'     => $zeroReason,
            ]);

            $this->syncOwnerFinanceCase($incomeTransaction);
            $this->recalcTotalsAndSyncStatus($incomeTransaction);

            return redirect()
                ->route('income.edit', ['income' => $incomeTransaction->id])
                ->with('success', 'Item tindakan berhasil diupdate.');
        });
    }

    public function destroyItem(IncomeTransaction $incomeTransaction, IncomeTransactionItem $item)
    {
        $this->ensureOwnerOrAdmin();
        $this->ensureEditableByUser($incomeTransaction);

        if ((int) $item->transaction_id !== (int) $incomeTransaction->id) {
            abort(404);
        }

        return DB::transaction(function () use ($incomeTransaction, $item) {
            $item->delete();

            $this->syncOwnerFinanceCase($incomeTransaction);
            $this->recalcTotalsAndSyncStatus($incomeTransaction);

            return redirect()
                ->route('income.edit', ['income' => $incomeTransaction->id])
                ->with('success', 'Item tindakan berhasil dihapus.');
        });
    }

    public function pay(Request $request, IncomeTransaction $incomeTransaction)
    {
        $this->ensureOwnerOrAdmin();
        $this->ensureEditableByUser($incomeTransaction);

        if ($this->isBpjs($incomeTransaction->payer_type ?? 'umum')) {
            return DB::transaction(function () use ($incomeTransaction) {
                $itemsCount = IncomeTransactionItem::where('transaction_id', $incomeTransaction->id)->count();

                if ($itemsCount < 1) {
                    return redirect()
                        ->route('income.edit', ['income' => $incomeTransaction->id])
                        ->withErrors(['pay' => 'Minimal 1 tindakan sebelum transaksi BPJS bisa disimpan.']);
                }

                DB::table('payments')
                    ->where('transaction_id', (int) $incomeTransaction->id)
                    ->delete();

                DB::table('income_transaction_items')
                    ->where('transaction_id', (int) $incomeTransaction->id)
                    ->update([
                        'unit_price' => 0,
                        'discount_amount' => 0,
                        'subtotal' => 0,
                        'fee_amount' => 0,
                        'zero_reason' => null,
                    ]);

                $incomeTransaction->update([
                    'pay_total' => 0,
                    'status'    => 'paid',
                ]);

                $this->recalcTotalsAndSyncStatus($incomeTransaction);
                $this->syncOwnerFinanceCase($incomeTransaction);

                return $this->redirectToNewTransactionForm('Transaksi BPJS berhasil disimpan. Silakan lanjut input transaksi baru.');
            });
        }

        if ($this->isKhusus($incomeTransaction->payer_type ?? 'umum')) {
            $this->recalcAllItemFees($incomeTransaction);
            $this->recalcTotalsAndSyncStatus($incomeTransaction);
            $incomeTransaction->refresh();

            $billTotal = (float) $incomeTransaction->bill_total;

            if ($billTotal <= 0) {
                return DB::transaction(function () use ($incomeTransaction) {
                    $status = strtolower(trim((string) ($incomeTransaction->status ?? '')));
                    if ($status !== 'draft') {
                        return redirect()
                            ->route('income.edit', ['income' => $incomeTransaction->id])
                            ->withErrors(['pay' => 'Transaksi tidak bisa dibayar karena status sudah bukan DRAFT.']);
                    }

                    $itemsCount = IncomeTransactionItem::where('transaction_id', (int) $incomeTransaction->id)->count();
                    if ($itemsCount < 1) {
                        return redirect()
                            ->route('income.edit', ['income' => $incomeTransaction->id])
                            ->withErrors(['pay' => 'Minimal 1 tindakan sebelum transaksi KHUSUS bisa disimpan.']);
                    }

                    DB::table('payments')
                        ->where('transaction_id', (int) $incomeTransaction->id)
                        ->delete();

                    $incomeTransaction->update([
                        'pay_total' => 0,
                    ]);

                    $this->recalcTotalsAndSyncStatus($incomeTransaction);
                    $this->syncOwnerFinanceCase($incomeTransaction);

                    return $this->redirectToNewTransactionForm('Transaksi KHUSUS (GRATIS) berhasil disimpan. Silakan lanjut input transaksi baru.');
                });
            }
        }

        $this->recalcAllItemFees($incomeTransaction);
        $this->recalcTotalsAndSyncStatus($incomeTransaction);
        $incomeTransaction->refresh();

        if ($this->transactionAllowsZeroBillCompletion($incomeTransaction) && (float) $incomeTransaction->bill_total <= 0) {
            return DB::transaction(function () use ($incomeTransaction) {
                $status = strtolower(trim((string) ($incomeTransaction->status ?? '')));
                if ($status !== 'draft') {
                    return redirect()
                        ->route('income.edit', ['income' => $incomeTransaction->id])
                        ->withErrors(['pay' => 'Transaksi tidak bisa disimpan karena status sudah bukan DRAFT.']);
                }

                $itemsCount = IncomeTransactionItem::where('transaction_id', (int) $incomeTransaction->id)->count();
                if ($itemsCount < 1) {
                    return redirect()
                        ->route('income.edit', ['income' => $incomeTransaction->id])
                        ->withErrors(['pay' => 'Minimal 1 tindakan sebelum transaksi bisa disimpan.']);
                }

                DB::table('payments')
                    ->where('transaction_id', (int) $incomeTransaction->id)
                    ->delete();

                $incomeTransaction->update([
                    'pay_total' => 0,
                ]);

                $this->recalcTotalsAndSyncStatus($incomeTransaction);
                $this->syncOwnerFinanceCase($incomeTransaction);

                return $this->redirectToNewTransactionForm('Transaksi treatment gratis berhasil disimpan tanpa pembayaran. Silakan lanjut input transaksi baru.');
            });
        }

        $data = $request->validate([
            'pay_date' => ['nullable', 'date'],
            'payment_method_id' => ['required', 'array', 'min:1'],
            'payment_method_id.*' => ['nullable', 'exists:payment_methods,id'],
            'channel' => ['nullable', 'array'],
            'channel.*' => ['nullable', 'string'],
            'amount' => ['required', 'array', 'min:1'],
            'amount.*' => ['nullable', 'string'],
        ], [
            'payment_method_id.required' => 'Minimal 1 metode pembayaran wajib diisi.',
            'payment_method_id.array' => 'Format metode pembayaran tidak valid.',
            'amount.required' => 'Minimal 1 nominal pembayaran wajib diisi.',
            'amount.array' => 'Format nominal pembayaran tidak valid.',
        ]);

        return DB::transaction(function () use ($incomeTransaction, $data) {
            $this->recalcAllItemFees($incomeTransaction);
            $this->recalcTotalsAndSyncStatus($incomeTransaction);
            $incomeTransaction->refresh();

            $status = strtolower(trim((string) ($incomeTransaction->status ?? '')));

            if ($status !== 'draft') {
                return redirect()
                    ->route('income.edit', ['income' => $incomeTransaction->id])
                    ->withErrors(['pay' => 'Transaksi tidak bisa dibayar karena status sudah bukan DRAFT.']);
            }

            $itemsCount = IncomeTransactionItem::where('transaction_id', (int) $incomeTransaction->id)->count();
            if ($itemsCount < 1) {
                return redirect()
                    ->route('income.edit', ['income' => $incomeTransaction->id])
                    ->withErrors(['pay' => 'Minimal 1 tindakan sebelum transaksi bisa dibayar.']);
            }

            $billTotal = (float) $incomeTransaction->bill_total;
            if ($billTotal <= 0) {
                return redirect()
                    ->route('income.edit', ['income' => $incomeTransaction->id])
                    ->withErrors(['pay' => 'Total tagihan masih 0. Tambahkan tindakan terlebih dahulu.']);
            }

            $payDate = $data['pay_date'] ?? now()->toDateString();
            $methodIds = $data['payment_method_id'] ?? [];
            $channels = $data['channel'] ?? [];
            $amounts = $data['amount'] ?? [];

            $rowsToSave = [];

            foreach ($methodIds as $index => $methodIdRaw) {
                $methodId = (int) ($methodIdRaw ?? 0);
                $amount = (float) clean_rupiah((string) ($amounts[$index] ?? '0'));

                if ($methodId <= 0 && $amount <= 0) {
                    continue;
                }

                if ($methodId <= 0) {
                    return redirect()
                        ->route('income.edit', ['income' => $incomeTransaction->id])
                        ->withErrors(['pay' => 'Ada baris pembayaran yang nominalnya diisi tetapi metode bayarnya belum dipilih.'])
                        ->withInput();
                }

                if ($amount <= 0) {
                    return redirect()
                        ->route('income.edit', ['income' => $incomeTransaction->id])
                        ->withErrors(['pay' => 'Ada baris pembayaran yang metode bayarnya dipilih tetapi nominalnya masih 0.'])
                        ->withInput();
                }

                $methodName = (string) DB::table('payment_methods')
                    ->where('id', $methodId)
                    ->value('name');

                if ($methodName === '') {
                    return redirect()
                        ->route('income.edit', ['income' => $incomeTransaction->id])
                        ->withErrors(['pay' => 'Metode pembayaran tidak ditemukan.'])
                        ->withInput();
                }

                $methodNameUpper = strtoupper(trim($methodName));
                $channel = strtoupper(trim((string) ($channels[$index] ?? '')));

                if ($methodNameUpper === 'TUNAI') {
                    $channel = 'CASH';
                } else {
                    $allowed = ['TRANSFER', 'EDC', 'QRIS'];
                    if (!in_array($channel, $allowed, true)) {
                        return redirect()
                            ->route('income.edit', ['income' => $incomeTransaction->id])
                            ->withErrors(['pay' => 'Untuk metode bank, channel wajib diisi: TRANSFER / EDC / QRIS.'])
                            ->withInput();
                    }
                }

                $rowsToSave[] = [
                    'payment_method_id' => $methodId,
                    'channel' => $channel,
                    'amount' => $amount,
                ];
            }

            if (count($rowsToSave) < 1) {
                return redirect()
                    ->route('income.edit', ['income' => $incomeTransaction->id])
                    ->withErrors(['pay' => 'Isi minimal 1 baris pembayaran.'])
                    ->withInput();
            }

            $currentPaid = $this->calcPayTotal($incomeTransaction);
            $newAmountTotal = array_sum(array_column($rowsToSave, 'amount'));
            $newPaid = $currentPaid + $newAmountTotal;

            if (round($newPaid, 2) > round($billTotal, 2)) {
                $sisa = max(0, $billTotal - $currentPaid);

                return redirect()
                    ->route('income.edit', ['income' => $incomeTransaction->id])
                    ->withErrors(['pay' => 'Total pembayaran baru melebihi total tagihan. Sisa tagihan saat ini: ' . number_format($sisa, 0, ',', '.')])
                    ->withInput();
            }

            foreach ($rowsToSave as $row) {
                $payment = new Payment();
                $payment->transaction_id = (int) $incomeTransaction->id;
                $payment->payment_method_id = (int) $row['payment_method_id'];
                $payment->channel = $row['channel'];
                $payment->amount = $row['amount'];
                $payment->pay_date = $payDate;
                $payment->save();
            }

            $payTotal = $this->calcPayTotal($incomeTransaction);

            $incomeTransaction->update([
                'pay_total' => $payTotal,
            ]);

            $this->recalcTotalsAndSyncStatus($incomeTransaction);
            $this->syncOwnerFinanceCase($incomeTransaction);

            $incomeTransaction->refresh();
            if (strtolower(trim((string) $incomeTransaction->status)) === 'paid') {
                return $this->redirectToNewTransactionForm('Pembayaran berhasil disimpan. Silakan lanjut input transaksi baru.');
            }

            $sisa = max(0, (float) $incomeTransaction->bill_total - (float) $incomeTransaction->pay_total);

            return redirect()
                ->route('income.edit', ['income' => $incomeTransaction->id])
                ->with('success', 'Pembayaran berhasil disimpan. Sisa tagihan: ' . number_format($sisa, 0, ',', '.'));
        });
    }

    public function destroyPayment(IncomeTransaction $incomeTransaction, Payment $payment)
    {
        $this->ensureOwnerOrAdmin();
        $this->ensureEditableByUser($incomeTransaction);

        if ((int) $payment->transaction_id !== (int) $incomeTransaction->id) {
            abort(404);
        }

        return DB::transaction(function () use ($incomeTransaction, $payment) {
            $payment->delete();

            $this->recalcTotalsAndSyncStatus($incomeTransaction);
            $this->syncOwnerFinanceCase($incomeTransaction);

            return redirect()
                ->route('income.edit', ['income' => $incomeTransaction->id])
                ->with('success', 'Pembayaran berhasil dihapus dan total/status sudah dihitung ulang.');
        });
    }


    public function labForm(IncomeTransaction $income)
    {
        $this->ensureOwnerOrAdmin();
        $this->ensureLabEligibleTransaction($income);

        $payload = $this->buildLabPayload($income);

        return view('income.lab_form', $payload);
    }

    public function labStore(Request $request, IncomeTransaction $income)
    {
        $this->ensureOwnerOrAdmin();
        $this->ensureLabEligibleTransaction($income);

        $data = $request->validate([
            'lab_name'           => ['required', 'string', 'max:150'],
            'lab_letter_date'    => ['required', 'date'],
            'lab_letter_number'  => ['nullable', 'string', 'max:100'],
            'lab_action_type'    => ['required', 'string', 'max:200'],
            'lab_material_shade' => ['nullable', 'string', 'max:200'],
            'lab_teeth_detail'   => ['required', 'string'],
            'lab_instruction'    => ['required', 'string'],
        ]);

        $number = trim((string) ($data['lab_letter_number'] ?? ''));
        if ($number === '') {
            $number = $this->nextLabLetterNumber($income);
        }

        DB::table('income_transactions')
            ->where('id', (int) $income->id)
            ->update([
                'needs_lab_letter' => 1,
                'lab_name' => trim((string) $data['lab_name']),
                'lab_letter_date' => $data['lab_letter_date'],
                'lab_letter_number' => $number,
                'lab_action_type' => trim((string) $data['lab_action_type']),
                'lab_material_shade' => trim((string) ($data['lab_material_shade'] ?? '')) ?: null,
                'lab_teeth_detail' => trim((string) $data['lab_teeth_detail']),
                'lab_instruction' => trim((string) $data['lab_instruction']),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('income.lab.form', ['income' => $income->id])
            ->with('success', 'Data Surat LAB berhasil disimpan.');
    }

    public function labPrint(IncomeTransaction $income)
    {
        $this->ensureOwnerOrAdmin();
        $this->ensureLabEligibleTransaction($income);

        if (!(bool) ($income->needs_lab_letter ?? false)) {
            return redirect()
                ->route('income.lab.form', ['income' => $income->id])
                ->withErrors(['lab_name' => 'Silakan isi dan simpan data Surat LAB terlebih dahulu.']);
        }

        $payload = $this->buildLabPayload($income->fresh());
        $number = preg_replace('/[^A-Za-z0-9\-]/', '-', (string) ($payload['labLetterNumber'] ?? ('LAB-' . $income->id)));
        $fileName = 'surat-lab-' . ($number ?: $income->id) . '.pdf';

        return Pdf::loadView('income.lab_print', $payload)
            ->setPaper('a4', 'portrait')
            ->download($fileName);
    }

    public function invoice(IncomeTransaction $income)
    {
        $this->ensureOwnerOrAdmin();

        $payload = $this->buildInvoicePayload($income);

        return view('income.invoice', $payload);
    }

    public function invoicePdf(IncomeTransaction $income)
    {
        $this->ensureOwnerOrAdmin();

        $payload = $this->buildInvoicePayload($income);

        $safeNumber = preg_replace('/[^A-Za-z0-9\-]/', '-', (string) $income->invoice_number);
        $fileName = 'invoice-' . ($safeNumber ?: $income->id) . '.pdf';

        return Pdf::loadView('income.invoice_pdf', $payload)
            ->setPaper('a4', 'portrait')
            ->download($fileName);
    }

    public function invoiceVerify(IncomeTransaction $income, string $code)
    {
        $income->load([
            'patient',
            'doctor',
            'items.treatment',
        ]);

        $payments = DB::table('payments as p')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->where('p.transaction_id', (int) $income->id)
            ->orderBy('p.id', 'asc')
            ->get([
                'p.id',
                'p.pay_date',
                'p.channel',
                'p.amount',
                'pm.name as method_name',
            ]);

        $storedCode = strtoupper(trim((string) ($income->receipt_verify_code ?? '')));
        $inputCode = strtoupper(trim($code));

        $isValid = $storedCode !== '' && hash_equals($storedCode, $inputCode);

        return view('income.invoice_verify', [
            'incomeTransaction' => $income,
            'payments'          => $payments,
            'isValid'           => $isValid,
            'verifyCode'        => $inputCode,
            'storedCode'        => $storedCode,
        ]);
    }

    private function buildInvoicePayload(IncomeTransaction $incomeTransaction): array
    {
        $incomeTransaction->load([
            'patient',
            'doctor',
            'items.treatment',
        ]);

        $payments = DB::table('payments as p')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->where('p.transaction_id', (int) $incomeTransaction->id)
            ->orderBy('p.id', 'asc')
            ->get([
                'p.id',
                'p.pay_date',
                'p.channel',
                'p.amount',
                'pm.name as method_name',
            ]);

        if (blank($incomeTransaction->receipt_verify_code)) {
            $incomeTransaction->receipt_verify_code = strtoupper(Str::random(10));
            $incomeTransaction->save();
        }

        $setting = Setting::query()->first();

        $verifyUrl = $this->buildVerifyUrl($incomeTransaction);

        $qrSvg = null;
        $qrSvgPath = null;

        try {
            $renderer = new ImageRenderer(
                new RendererStyle(220),
                new SvgImageBackEnd()
            );

            $writer = new Writer($renderer);
            $qrSvg = $writer->writeString($verifyUrl);

            $directory = storage_path('app/public');
            if (!is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }

            $qrSvgFile = storage_path('app/public/tmp_qr_invoice_' . $incomeTransaction->id . '.svg');
            file_put_contents($qrSvgFile, $qrSvg);

            if (file_exists($qrSvgFile)) {
                $qrSvgPath = $qrSvgFile;
            }
        } catch (\Throwable $e) {
            $qrSvg = null;
            $qrSvgPath = null;
        }

        return [
            'incomeTransaction' => $incomeTransaction,
            'payments'          => $payments,
            'setting'           => $setting,
            'verifyUrl'         => $verifyUrl,
            'verifyCode'        => (string) $incomeTransaction->receipt_verify_code,
            'qrSvg'             => $qrSvg,
            'qrSvgPath'         => $qrSvgPath,
            'signatureDoctor'   => 'drg. Desly A.C. Luhulima, M.K.M',
            'isBpjs'            => $this->isBpjs($incomeTransaction->payer_type ?? 'umum'),
        ];
    }

    private function calcFeeAmount(int $doctorId, int $treatmentId, float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        $doctor = DB::table('doctors')
            ->select('type', 'default_fee_percent')
            ->where('id', $doctorId)
            ->first();

        if (!$doctor) {
            return 0.0;
        }

        $doctorType = strtolower((string) ($doctor->type ?? ''));
        if ($doctorType === 'owner') {
            return 0.0;
        }

        $feeRow = DB::table('doctor_treatment_fees')
            ->where('doctor_id', $doctorId)
            ->where('treatment_id', $treatmentId)
            ->first(['fee_type', 'fee_value']);

        if ($feeRow) {
            $feeType = strtolower((string) ($feeRow->fee_type ?? 'percent'));
            $feeVal  = (float) ($feeRow->fee_value ?? 0);

            if ($feeType === 'manual') {
                return 0.0;
            }

            if ($feeType === 'fixed') {
                return round(max(0, $feeVal), 2);
            }

            if ($feeVal > 0) {
                return round($subtotal * ($feeVal / 100), 2);
            }

            return 0.0;
        }

        $percent = (float) ($doctor->default_fee_percent ?? 0);
        if ($percent <= 0) {
            return 0.0;
        }

        return round($subtotal * ($percent / 100), 2);
    }

    private function recalcAllItemFees(IncomeTransaction $incomeTransaction): void
    {
        if ($this->isBpjs($incomeTransaction->payer_type ?? 'umum')) {
            DB::table('income_transaction_items')
                ->where('transaction_id', (int) $incomeTransaction->id)
                ->update([
                    'fee_amount' => 0,
                    'unit_price' => 0,
                    'discount_amount' => 0,
                    'subtotal' => 0,
                    'zero_reason' => null,
                ]);

            return;
        }

        $items = DB::table('income_transaction_items')
            ->where('transaction_id', (int) $incomeTransaction->id)
            ->get(['id', 'treatment_id', 'subtotal']);

        foreach ($items as $it) {
            $feeRow = DB::table('doctor_treatment_fees')
                ->where('doctor_id', (int) $incomeTransaction->doctor_id)
                ->where('treatment_id', (int) $it->treatment_id)
                ->first(['fee_type']);

            if ($feeRow && strtolower((string) $feeRow->fee_type) === 'manual') {
                continue;
            }

            $feeAmount = $this->calcFeeAmount(
                (int) $incomeTransaction->doctor_id,
                (int) $it->treatment_id,
                (float) $it->subtotal
            );

            DB::table('income_transaction_items')
                ->where('id', (int) $it->id)
                ->update(['fee_amount' => $feeAmount]);
        }
    }

    private function recalcTotalsAndSyncStatus(IncomeTransaction $incomeTransaction): void
    {
        $billTotal = (float) DB::table('income_transaction_items')
            ->where('transaction_id', (int) $incomeTransaction->id)
            ->sum('subtotal');

        $doctorFeeTotal = (float) DB::table('income_transaction_items')
            ->where('transaction_id', (int) $incomeTransaction->id)
            ->sum('fee_amount');

        $payTotal = (float) DB::table('payments')
            ->where('transaction_id', (int) $incomeTransaction->id)
            ->sum('amount');

        $itemsCount = (int) DB::table('income_transaction_items')
            ->where('transaction_id', (int) $incomeTransaction->id)
            ->count();

        $isBpjs = $this->isBpjs($incomeTransaction->payer_type ?? 'umum');
        $isKhusus = $this->isKhusus($incomeTransaction->payer_type ?? 'umum');

        if ($isBpjs) {
            $billTotal = 0;
            $doctorFeeTotal = 0;
            $payTotal = 0;
        }

        $currentStatus = strtolower(trim((string) ($incomeTransaction->status ?? 'draft')));
        if ($currentStatus === '') {
            $currentStatus = 'draft';
        }

        $newStatus = 'draft';

        if ($isBpjs) {
            $newStatus = ($itemsCount > 0 && $currentStatus === 'paid') ? 'paid' : 'draft';
        } elseif ($isKhusus) {
            $newStatus = $itemsCount > 0 && round($payTotal, 2) === round($billTotal, 2) ? 'paid' : 'draft';
        } elseif ($this->transactionAllowsZeroBillCompletion($incomeTransaction) && $itemsCount > 0 && round($billTotal, 2) === 0.0) {
            $newStatus = 'paid';
        } elseif ($billTotal > 0 && round($payTotal, 2) === round($billTotal, 2)) {
            $newStatus = 'paid';
        }

        $incomeTransaction->update([
            'bill_total'       => $billTotal,
            'doctor_fee_total' => $doctorFeeTotal,
            'pay_total'        => $payTotal,
            'status'           => $newStatus,
        ]);
    }

    private function calcPayTotal(IncomeTransaction $incomeTransaction): float
    {
        return (float) DB::table('payments')
            ->where('transaction_id', (int) $incomeTransaction->id)
            ->sum('amount');
    }
}