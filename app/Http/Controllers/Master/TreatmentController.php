<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Treatment;
use App\Models\TreatmentCategory;

class TreatmentController extends Controller
{
    public function index()
    {
        $treatments = Treatment::with('category')
            ->orderBy('name')
            ->get();

        $categories = TreatmentCategory::orderBy('name')->get();
        $doctors = $this->getDoctors();
        $doctorGroups = $this->groupDoctorsByType($doctors);

        $feeRows = DB::table('doctor_treatment_fees as dtf')
            ->join('doctors as d', 'd.id', '=', 'dtf.doctor_id')
            ->select([
                'dtf.treatment_id',
                'dtf.doctor_id',
                'dtf.fee_type',
                'dtf.fee_value',
                'd.name as doctor_name',
                DB::raw('LOWER(COALESCE(d.type, "")) as doctor_type'),
            ])
            ->whereIn(DB::raw('LOWER(COALESCE(d.type, ""))'), ['owner', 'mitra', 'tamu'])
            ->orderBy('dtf.treatment_id')
            ->orderByRaw("
                CASE
                    WHEN LOWER(COALESCE(d.type, '')) = 'owner' THEN 1
                    WHEN LOWER(COALESCE(d.type, '')) = 'mitra' THEN 2
                    WHEN LOWER(COALESCE(d.type, '')) = 'tamu' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('d.name')
            ->get();

        $feesByTreatmentDoctor = [];
        $feesByTreatmentType = [];

        foreach ($feeRows as $row) {
            $treatmentId = (int) $row->treatment_id;
            $doctorId = (int) $row->doctor_id;
            $doctorType = strtolower((string) ($row->doctor_type ?? ''));
            $feeType = (string) ($row->fee_type ?? 'manual');
            $feeValue = (float) ($row->fee_value ?? 0);

            if (!isset($feesByTreatmentDoctor[$treatmentId])) {
                $feesByTreatmentDoctor[$treatmentId] = [];
            }

            $feesByTreatmentDoctor[$treatmentId][$doctorId] = [
                'doctor_id' => $doctorId,
                'doctor_name' => (string) ($row->doctor_name ?? '-'),
                'doctor_type' => $doctorType,
                'doctor_type_label' => $this->doctorTypeLabel($doctorType),
                'fee_type' => $feeType,
                'fee_value' => $feeValue,
                'fee_label' => $this->formatFeeLabel($feeType, $feeValue),
            ];

            if ($doctorType !== '' && !isset($feesByTreatmentType[$treatmentId][$doctorType])) {
                $feesByTreatmentType[$treatmentId][$doctorType] = [
                    'fee_type' => $feeType,
                    'fee_value' => $feeValue,
                ];
            }
        }

        return view('master.treatments.index', compact(
            'treatments',
            'categories',
            'doctors',
            'doctorGroups',
            'feesByTreatmentDoctor',
            'feesByTreatmentType'
        ));
    }

    public function create()
    {
        $categories = TreatmentCategory::orderBy('name')->get();
        $doctors = $this->getDoctors();
        $doctorGroups = $this->groupDoctorsByType($doctors);
        $feeDefaultsByDoctor = $this->buildDefaultFeeFormByDoctor($doctors);

        return view('master.treatments.create', compact(
            'categories',
            'doctors',
            'doctorGroups',
            'feeDefaultsByDoctor'
        ));
    }

    public function store(Request $request)
    {
        $doctors = $this->getDoctors();

        $validated = $request->validate(
            $this->buildValidationRules($doctors),
            $this->validationMessages()
        );

        $priceMode = strtolower((string) ($validated['price_mode'] ?? 'fixed'));
        $allowZeroPrice = (bool) ($validated['allow_zero_price'] ?? false);
        $isFree = (bool) ($validated['is_free'] ?? false);
        $isOrthoRelated = (bool) ($validated['is_ortho_related'] ?? false);
        $isProstoRelated = (bool) ($validated['is_prosto_related'] ?? false);
        $price = $this->normalizeTreatmentPrice($request->price, $priceMode);

        if ($isFree) {
            $price = 0;
            $allowZeroPrice = true;
        }

        if ($priceMode === 'fixed' && !$allowZeroPrice && $price <= 0) {
            return redirect()
                ->back()
                ->withErrors(['price' => 'Harga wajib diisi dan harus lebih dari 0 untuk mode Harga Tetap, kecuali treatment ini memang diizinkan harga 0.'])
                ->withInput();
        }

        $doctorFeePayload = $this->extractDoctorFeePayload($request, $doctors);

        $treatment = null;

        DB::transaction(function () use ($validated, $price, $priceMode, $allowZeroPrice, $isFree, $isOrthoRelated, $isProstoRelated, $doctorFeePayload, &$treatment) {
            $treatment = Treatment::create([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'unit' => $validated['unit'] ?? '1x',
                'price' => $price,
                'price_mode' => $priceMode,
                'allow_zero_price' => $allowZeroPrice,
                'is_free' => $isFree,
                'is_ortho_related' => $isOrthoRelated,
                'is_prosto_related' => $isProstoRelated,
                'notes_hint' => $this->normalizeNotesHint($validated['notes_hint'] ?? null),
                'is_active' => $validated['is_active'],
            ]);

            $this->syncDoctorTreatmentFeesByDoctor($treatment->id, $doctorFeePayload);
        });

        return redirect()
            ->route('master.treatments.index')
            ->with('success', 'Treatment berhasil ditambahkan');
    }

    public function edit(Treatment $treatment)
    {
        $categories = TreatmentCategory::orderBy('name')->get();
        $doctors = $this->getDoctors();
        $doctorGroups = $this->groupDoctorsByType($doctors);

        $price_display = format_rupiah($treatment->price);
        $price_mode = strtolower((string) ($treatment->price_mode ?? 'fixed'));
        $notes_hint = (string) ($treatment->notes_hint ?? '');

        $feeFormByDoctor = $this->buildDefaultFeeFormByDoctor($doctors);

        $feeRows = DB::table('doctor_treatment_fees as dtf')
            ->join('doctors as d', 'd.id', '=', 'dtf.doctor_id')
            ->select([
                'dtf.doctor_id',
                'dtf.fee_type',
                'dtf.fee_value',
                DB::raw('LOWER(COALESCE(d.type, "")) as doctor_type'),
            ])
            ->where('dtf.treatment_id', $treatment->id)
            ->whereIn(DB::raw('LOWER(COALESCE(d.type, ""))'), ['owner', 'mitra', 'tamu'])
            ->orderBy('dtf.id')
            ->get();

        foreach ($feeRows as $row) {
            $doctorId = (int) ($row->doctor_id ?? 0);

            if ($doctorId > 0) {
                $feeFormByDoctor[$doctorId] = [
                    'fee_type' => (string) ($row->fee_type ?? 'manual'),
                    'fee_value' => (string) ((float) ($row->fee_value ?? 0)),
                ];
            }
        }

        $feeForm = [
            'owner' => ['fee_type' => 'manual', 'fee_value' => '0'],
            'mitra' => ['fee_type' => 'manual', 'fee_value' => '0'],
            'tamu' => ['fee_type' => 'manual', 'fee_value' => '0'],
        ];

        foreach ($feeRows as $row) {
            $type = strtolower((string) ($row->doctor_type ?? ''));

            if (
                isset($feeForm[$type]) &&
                $feeForm[$type]['fee_type'] === 'manual' &&
                (float) $feeForm[$type]['fee_value'] === 0.0
            ) {
                $feeForm[$type] = [
                    'fee_type' => (string) ($row->fee_type ?? 'manual'),
                    'fee_value' => (string) ((float) ($row->fee_value ?? 0)),
                ];
            }
        }

        return view('master.treatments.edit', compact(
            'treatment',
            'categories',
            'price_display',
            'price_mode',
            'notes_hint',
            'doctors',
            'doctorGroups',
            'feeFormByDoctor',
            'feeForm'
        ));
    }

    public function update(Request $request, Treatment $treatment)
    {
        $doctors = $this->getDoctors();

        $validated = $request->validate(
            $this->buildValidationRules($doctors),
            $this->validationMessages()
        );

        $priceMode = strtolower((string) ($validated['price_mode'] ?? 'fixed'));
        $allowZeroPrice = (bool) ($validated['allow_zero_price'] ?? false);
        $isFree = (bool) ($validated['is_free'] ?? false);
        $isOrthoRelated = (bool) ($validated['is_ortho_related'] ?? false);
        $isProstoRelated = (bool) ($validated['is_prosto_related'] ?? false);
        $price = $this->normalizeTreatmentPrice($request->price, $priceMode);

        if ($isFree) {
            $price = 0;
            $allowZeroPrice = true;
        }

        if ($priceMode === 'fixed' && !$allowZeroPrice && $price <= 0) {
            return redirect()
                ->back()
                ->withErrors(['price' => 'Harga wajib diisi dan harus lebih dari 0 untuk mode Harga Tetap, kecuali treatment ini memang diizinkan harga 0.'])
                ->withInput();
        }

        $doctorFeePayload = $this->extractDoctorFeePayload($request, $doctors);

        DB::transaction(function () use ($validated, $price, $priceMode, $allowZeroPrice, $isFree, $isOrthoRelated, $isProstoRelated, $treatment, $doctorFeePayload) {
            $treatment->update([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'unit' => $validated['unit'] ?? '1x',
                'price' => $price,
                'price_mode' => $priceMode,
                'allow_zero_price' => $allowZeroPrice,
                'is_free' => $isFree,
                'is_ortho_related' => $isOrthoRelated,
                'is_prosto_related' => $isProstoRelated,
                'notes_hint' => $this->normalizeNotesHint($validated['notes_hint'] ?? null),
                'is_active' => $validated['is_active'],
            ]);

            $this->syncDoctorTreatmentFeesByDoctor($treatment->id, $doctorFeePayload);

            $this->syncHistoricalTransactionFeesForTreatment($treatment->id, '2026-01-01');
        });

        return redirect()
            ->route('master.treatments.index')
            ->with('success', 'Treatment berhasil diperbarui dan fee transaksi mulai 01-01-2026 berhasil disinkronkan.');
    }

    public function destroy(Treatment $treatment)
    {
        DB::transaction(function () use ($treatment) {
            DB::table('doctor_treatment_fees')
                ->where('treatment_id', $treatment->id)
                ->delete();

            $treatment->delete();
        });

        return redirect()
            ->route('master.treatments.index')
            ->with('success', 'Treatment berhasil dihapus');
    }

    private function getDoctors(): Collection
    {
        return DB::table('doctors')
            ->select('id', 'name', 'type')
            ->whereIn(DB::raw('LOWER(COALESCE(type, ""))'), ['owner', 'mitra', 'tamu'])
            ->orderByRaw("
                CASE
                    WHEN LOWER(COALESCE(type, '')) = 'owner' THEN 1
                    WHEN LOWER(COALESCE(type, '')) = 'mitra' THEN 2
                    WHEN LOWER(COALESCE(type, '')) = 'tamu' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('name')
            ->get()
            ->map(function ($doctor) {
                $doctor->type = strtolower((string) ($doctor->type ?? ''));
                return $doctor;
            })
            ->values();
    }

    private function groupDoctorsByType(Collection $doctors): array
    {
        return [
            'owner' => $doctors->where('type', 'owner')->values(),
            'mitra' => $doctors->where('type', 'mitra')->values(),
            'tamu' => $doctors->where('type', 'tamu')->values(),
        ];
    }

    private function buildDefaultFeeFormByDoctor(Collection $doctors): array
    {
        $defaults = [];

        foreach ($doctors as $doctor) {
            $defaults[(int) $doctor->id] = [
                'fee_type' => 'manual',
                'fee_value' => '0',
            ];
        }

        return $defaults;
    }

    private function buildValidationRules(Collection $doctors): array
    {
        $rules = [
            'category_id' => 'required|exists:treatment_categories,id',
            'name' => 'required|max:150',
            'unit' => 'nullable|max:30',
            'price' => 'nullable|string',
            'price_mode' => 'required|in:fixed,manual',
            'allow_zero_price' => 'nullable|boolean',
            'is_free' => 'nullable|boolean',
            'is_ortho_related' => 'nullable|boolean',
            'is_prosto_related' => 'nullable|boolean',
            'notes_hint' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
        ];

        foreach ($doctors as $doctor) {
            $doctorId = (int) $doctor->id;
            $rules["doctor_fees.{$doctorId}.fee_type"] = 'required|in:percent,fixed,manual';
            $rules["doctor_fees.{$doctorId}.fee_value"] = 'nullable|string';
        }

        return $rules;
    }

    private function validationMessages(): array
    {
        return [
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori tidak ditemukan.',
            'name.required' => 'Nama tindakan wajib diisi.',
            'name.max' => 'Nama tindakan maksimal 150 karakter.',
            'unit.max' => 'Satuan maksimal 30 karakter.',
            'price_mode.required' => 'Mode harga wajib dipilih.',
            'price_mode.in' => 'Mode harga tidak valid.',
            'allow_zero_price.boolean' => 'Pengaturan harga 0 tidak valid.',
            'is_free.boolean' => 'Pengaturan treatment gratis tidak valid.',
            'is_ortho_related.boolean' => 'Pengaturan related Ortho tidak valid.',
            'is_prosto_related.boolean' => 'Pengaturan related Prosto tidak valid.',
            'notes_hint.max' => 'Catatan petunjuk maksimal 1000 karakter.',
            'is_active.required' => 'Status aktif wajib dipilih.',
            'is_active.boolean' => 'Status aktif tidak valid.',
        ];
    }

    private function extractDoctorFeePayload(Request $request, Collection $doctors): array
    {
        $payload = [];

        foreach ($doctors as $doctor) {
            $doctorId = (int) $doctor->id;
            $feeType = (string) $request->input("doctor_fees.{$doctorId}.fee_type", 'manual');
            $feeValue = $request->input("doctor_fees.{$doctorId}.fee_value", '0');

            $payload[$doctorId] = [
                'fee_type' => $feeType,
                'fee_value' => $feeValue,
            ];
        }

        return $payload;
    }

    private function syncDoctorTreatmentFeesByDoctor(int $treatmentId, array $payloadByDoctor): void
    {
        foreach ($payloadByDoctor as $doctorId => $payload) {
            $doctorId = (int) $doctorId;
            $feeType = (string) ($payload['fee_type'] ?? 'manual');
            $feeValue = $this->normalizeFeeValue($feeType, $payload['fee_value'] ?? null);

            $exists = DB::table('doctor_treatment_fees')
                ->where('doctor_id', $doctorId)
                ->where('treatment_id', $treatmentId)
                ->exists();

            $data = [
                'fee_type' => $feeType,
                'fee_value' => $feeValue,
                'updated_at' => now(),
            ];

            if ($exists) {
                DB::table('doctor_treatment_fees')
                    ->where('doctor_id', $doctorId)
                    ->where('treatment_id', $treatmentId)
                    ->update($data);
            } else {
                DB::table('doctor_treatment_fees')
                    ->insert([
                        'doctor_id' => $doctorId,
                        'treatment_id' => $treatmentId,
                        'fee_type' => $feeType,
                        'fee_value' => $feeValue,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    private function syncHistoricalTransactionFeesForTreatment(int $treatmentId, string $startDate): void
    {
        $items = DB::table('income_transaction_items as iti')
            ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
            ->leftJoin('doctor_treatment_fees as dtf', function ($join) {
                $join->on('dtf.doctor_id', '=', 'it.doctor_id')
                    ->on('dtf.treatment_id', '=', 'iti.treatment_id');
            })
            ->leftJoin('doctors as d', 'd.id', '=', 'it.doctor_id')
            ->where('iti.treatment_id', $treatmentId)
            ->whereDate('it.trx_date', '>=', $startDate)
            ->whereRaw("LOWER(COALESCE(it.payer_type, 'umum')) <> 'bpjs'")
            ->select([
                'iti.id as item_id',
                'iti.transaction_id',
                'iti.subtotal',
                'it.doctor_id',
                'd.type as doctor_type',
                'd.default_fee_percent',
                'dtf.fee_type',
                'dtf.fee_value',
            ])
            ->orderBy('iti.id')
            ->get();

        $affectedTransactionIds = [];

        foreach ($items as $item) {
            $feeType = strtolower(trim((string) ($item->fee_type ?? 'percent')));
            $feeValue = (float) ($item->fee_value ?? 0);
            $subtotal = (float) ($item->subtotal ?? 0);
            $doctorType = strtolower(trim((string) ($item->doctor_type ?? '')));
            $defaultFeePercent = (float) ($item->default_fee_percent ?? 0);

            if ($feeType === 'manual') {
                continue;
            }

            $newFee = 0.0;

            if ($doctorType === 'owner') {
                $newFee = 0.0;
            } elseif ($feeType === 'fixed') {
                $newFee = round(max(0, $feeValue), 2);
            } elseif ($feeType === 'percent') {
                $newFee = $feeValue > 0
                    ? round($subtotal * ($feeValue / 100), 2)
                    : 0.0;
            } else {
                $newFee = $defaultFeePercent > 0
                    ? round($subtotal * ($defaultFeePercent / 100), 2)
                    : 0.0;
            }

            DB::table('income_transaction_items')
                ->where('id', (int) $item->item_id)
                ->update([
                    'fee_amount' => $newFee,
                ]);

            $affectedTransactionIds[(int) $item->transaction_id] = (int) $item->transaction_id;
        }

        if (count($affectedTransactionIds) < 1) {
            return;
        }

        foreach (array_values($affectedTransactionIds) as $transactionId) {
            $doctorFeeTotal = (float) DB::table('income_transaction_items')
                ->where('transaction_id', $transactionId)
                ->sum('fee_amount');

            DB::table('income_transactions')
                ->where('id', $transactionId)
                ->update([
                    'doctor_fee_total' => $doctorFeeTotal,
                ]);
        }
    }

    private function normalizeFeeValue(string $feeType, $rawValue): float
    {
        if ($feeType === 'manual') {
            return 0;
        }

        $value = (float) clean_rupiah((string) ($rawValue ?? '0'));

        if ($value < 0) {
            $value = 0;
        }

        return $value;
    }

    private function normalizeTreatmentPrice($rawValue, string $priceMode): float
    {
        $value = (float) clean_rupiah((string) ($rawValue ?? '0'));

        if ($value < 0) {
            $value = 0;
        }

        if ($priceMode === 'manual') {
            return max(0, $value);
        }

        return $value;
    }

    private function normalizeNotesHint(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function doctorTypeLabel(?string $type): string
    {
        return match (strtolower((string) $type)) {
            'owner' => 'Owner',
            'mitra' => 'Mitra',
            'tamu' => 'Tamu',
            default => '-',
        };
    }

    private function formatFeeLabel(string $feeType, float $feeValue): string
    {
        $feeType = strtolower(trim($feeType));

        if ($feeType === 'manual') {
            return 'Manual';
        }

        if ($feeType === 'percent') {
            $formatted = rtrim(rtrim(number_format($feeValue, 2, ',', '.'), '0'), ',');
            return $formatted . '%';
        }

        if ($feeType === 'fixed') {
            return 'Rp ' . number_format($feeValue, 0, ',', '.');
        }

        return 'Manual';
    }
}