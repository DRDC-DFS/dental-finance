<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ReportController extends Controller
{
    private function ensureOwner(): void
    {
        $user = Auth::user();

        if (!$user || strtolower((string) $user->role) !== 'owner') {
            abort(403, 'Hanya OWNER yang boleh mengakses laporan.');
        }
    }

    private function ensureOwnerOrAdmin(): void
    {
        $user = Auth::user();
        $role = strtolower((string) ($user->role ?? ''));

        if (!$user || !in_array($role, ['owner', 'admin'], true)) {
            abort(403, 'Hanya OWNER atau ADMIN yang boleh mengakses laporan ini.');
        }
    }

    private function resolvePeriod(Request $request): array
    {
        $singleDate = $request->query('date');
        $start = $request->query('start');
        $end   = $request->query('end');

        if ($singleDate) {
            return [$singleDate, $singleDate];
        }

        if ($start || $end) {
            if (!$start) {
                $start = $end ?: now()->toDateString();
            }

            if (!$end) {
                $end = $start;
            }

            if ($start > $end) {
                [$start, $end] = [$end, $start];
            }

            return [$start, $end];
        }

        $today = now()->toDateString();

        return [$today, $today];
    }

    private function isCashExpenseMethod(?string $payMethod): bool
    {
        $value = strtolower(trim((string) $payMethod));

        if ($value === '') {
            return false;
        }

        return str_contains($value, 'tunai')
            || str_contains($value, 'cash')
            || str_contains($value, 'kas');
    }

    private function resolveKasHarianView(): string
    {
        if (View::exists('reports.kas_harian')) {
            return 'reports.kas_harian';
        }

        if (View::exists('reports.kas-harian')) {
            return 'reports.kas-harian';
        }

        abort(500, 'View laporan Kas Harian tidak ditemukan. Pastikan file view tersedia.');
    }

    private function mapOwnerCaseLabel(?string $caseType): string
    {
        return match (strtolower(trim((string) $caseType))) {
            'prostodonti' => 'Prostodonti',
            'ortho' => 'Ortho',
            'retainer' => 'Retainer',
            'lab' => 'Dental Laboratory',
            default => 'Reguler',
        };
    }

    private function mapPayerTypeLabel(?string $payerType): string
    {
        return match (strtolower(trim((string) $payerType))) {
            'bpjs' => 'BPJS',
            'khusus' => 'Khusus',
            default => 'Umum',
        };
    }

    private function sanitizeFilenamePart(?string $value, string $fallback = 'hari_ini'): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $fallback;
        }

        return preg_replace('/[^A-Za-z0-9\-_]/', '_', $value) ?: $fallback;
    }

    private function buildExportFilename(string $prefix, Request $request, string $extension): string
    {
        [$start, $end] = $this->resolvePeriod($request);

        $startPart = $this->sanitizeFilenamePart($start);
        $endPart = $this->sanitizeFilenamePart($end);

        $periodPart = $startPart === $endPart
            ? $startPart
            : $startPart . '_sd_' . $endPart;

        return $prefix . '_' . $periodPart . '.' . ltrim($extension, '.');
    }

    private function extractViewData(ViewContract $view, array $extra = []): array
    {
        $data = $view->getData();

        foreach ($extra as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }

    private function exportExcelFromView(string $filename, string $viewName, array $data): BinaryFileResponse
    {
        $export = new class($viewName, $data) implements FromView, ShouldAutoSize {
            public function __construct(
                private string $viewName,
                private array $data
            ) {
            }

            public function view(): ViewContract
            {
                return view($this->viewName, $this->data);
            }
        };

        return Excel::download($export, $filename);
    }

    public function labaRugi(Request $request)
    {
        $this->ensureOwner();

        [$start, $end] = $this->resolvePeriod($request);
        $metric = $request->query('metric', 'qty');

        $grossIncomeRegular = (float) DB::table('income_transactions as it')
            ->leftJoin('owner_finance_cases as ofc', 'ofc.income_transaction_id', '=', 'it.id')
            ->whereBetween(DB::raw('DATE(it.trx_date)'), [$start, $end])
            ->where('it.status', 'paid')
            ->whereNull('ofc.id')
            ->sum('it.pay_total');

        $recognizedProsthoRetainerIncome = (float) DB::table('owner_finance_cases')
            ->whereIn('case_type', ['prostodonti', 'retainer'])
            ->whereNotNull('revenue_recognized_at')
            ->whereBetween(DB::raw('DATE(revenue_recognized_at)'), [$start, $end])
            ->sum('clinic_income_amount');

        $recognizedDentalLaboratoryIncome = (float) DB::table('owner_finance_cases')
            ->where('case_type', 'lab')
            ->whereNotNull('revenue_recognized_at')
            ->whereBetween(DB::raw('DATE(revenue_recognized_at)'), [$start, $end])
            ->sum('clinic_income_amount');

        $otherIncomeTotal = 0.0;
        if (Schema::hasTable('other_incomes')) {
            $otherIncomeTotal = (float) DB::table('other_incomes')
                ->whereBetween(DB::raw('DATE(trx_date)'), [$start, $end])
                ->where('include_in_report', true)
                ->sum('amount');
        }

        $privateOwnerIncome = 0.0;
        $privateOwnerExpense = 0.0;

        if (Schema::hasTable('owner_private_transactions')) {
            $privateOwnerIncome = (float) DB::table('owner_private_transactions')
                ->whereBetween(DB::raw('DATE(trx_date)'), [$start, $end])
                ->where('type', 'income')
                ->sum('amount');

            $privateOwnerExpense = (float) DB::table('owner_private_transactions')
                ->whereBetween(DB::raw('DATE(trx_date)'), [$start, $end])
                ->where('type', 'expense')
                ->sum('amount');
        }

        $recognizedClinicIncome = $recognizedProsthoRetainerIncome + $recognizedDentalLaboratoryIncome + $otherIncomeTotal;

        $grossIncome = $grossIncomeRegular + $recognizedClinicIncome;
        $totalClinicIncome = $grossIncome + $privateOwnerIncome;

        $doctorFee = (float) DB::table('income_transactions as it')
            ->leftJoin('owner_finance_cases as ofc', 'ofc.income_transaction_id', '=', 'it.id')
            ->whereBetween(DB::raw('DATE(it.trx_date)'), [$start, $end])
            ->where('it.status', 'paid')
            ->whereNull('ofc.id')
            ->sum('it.doctor_fee_total');

        $netClinicIncome = $grossIncome - $doctorFee;

        $operationalExpense = (float) DB::table('expenses')
            ->whereBetween(DB::raw('DATE(expense_date)'), [$start, $end])
            ->sum('amount');

        $totalExpense = $operationalExpense + $privateOwnerExpense;

        $netProfit = $netClinicIncome - $operationalExpense;
        $netClinicCashflow = $totalClinicIncome - $totalExpense;

        $ownerMutationIncome = (float) DB::table('owner_account_mutations')
            ->whereBetween(DB::raw('DATE(mutation_date)'), [$start, $end])
            ->where('mutation_type', 'pemasukan')
            ->sum('amount');

        $ownerMutationExpense = (float) DB::table('owner_account_mutations')
            ->whereBetween(DB::raw('DATE(mutation_date)'), [$start, $end])
            ->where('mutation_type', 'pengeluaran')
            ->sum('amount');

        $ownerNetCashflow = $ownerMutationIncome - $ownerMutationExpense;

        $topCategories = DB::table('income_transaction_items as iti')
            ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
            ->join('treatments as t', 't.id', '=', 'iti.treatment_id')
            ->leftJoin('treatment_categories as tc', 'tc.id', '=', 't.category_id')
            ->leftJoin('owner_finance_cases as ofc', 'ofc.income_transaction_id', '=', 'it.id')
            ->whereBetween(DB::raw('DATE(it.trx_date)'), [$start, $end])
            ->where('it.status', 'paid')
            ->whereNull('ofc.id')
            ->selectRaw("
                COALESCE(tc.name, 'Tanpa Kategori') as category_name,
                SUM(CASE WHEN ? = 'subtotal' THEN iti.subtotal ELSE iti.qty END) as metric_total
            ", [$metric])
            ->groupBy('category_name')
            ->orderByDesc('metric_total')
            ->limit(6)
            ->pluck('category_name')
            ->all();

        $rawRows = DB::table('income_transaction_items as iti')
            ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
            ->join('treatments as t', 't.id', '=', 'iti.treatment_id')
            ->leftJoin('treatment_categories as tc', 'tc.id', '=', 't.category_id')
            ->leftJoin('owner_finance_cases as ofc', 'ofc.income_transaction_id', '=', 'it.id')
            ->whereBetween(DB::raw('DATE(it.trx_date)'), [$start, $end])
            ->where('it.status', 'paid')
            ->whereNull('ofc.id')
            ->selectRaw("
                DATE(it.trx_date) as day,
                COALESCE(tc.name, 'Tanpa Kategori') as category_name,
                SUM(iti.qty) as qty_total,
                SUM(iti.subtotal) as subtotal_total
            ")
            ->groupBy(DB::raw('DATE(it.trx_date)'), 'category_name')
            ->orderBy(DB::raw('DATE(it.trx_date)'))
            ->get();

        $labels = [];
        $cursor = strtotime($start);
        $last   = strtotime($end);

        while ($cursor <= $last) {
            $labels[] = date('Y-m-d', $cursor);
            $cursor = strtotime('+1 day', $cursor);
        }

        $series = [];
        foreach ($topCategories as $cat) {
            $series[$cat] = array_fill(0, count($labels), 0);
        }
        $series['Lainnya'] = array_fill(0, count($labels), 0);

        $labelIndex = array_flip($labels);

        foreach ($rawRows as $row) {
            $day = $row->day;
            if (!isset($labelIndex[$day])) {
                continue;
            }

            $idx = $labelIndex[$day];
            $cat = in_array($row->category_name, $topCategories, true) ? $row->category_name : 'Lainnya';
            $value = $metric === 'subtotal'
                ? (float) $row->subtotal_total
                : (float) $row->qty_total;

            $series[$cat][$idx] += $value;
        }

        $chartData = [
            'labels' => $labels,
            'series' => $series,
            'metric' => $metric,
            'enabled' => count($labels) > 0,
            'note' => null,
        ];

        return view('reports.laba_rugi', [
            'start' => $start,
            'end' => $end,
            'grossIncome' => $grossIncome,
            'grossIncomeRegular' => $grossIncomeRegular,
            'recognizedClinicIncome' => $recognizedClinicIncome,
            'recognizedProsthoRetainerIncome' => $recognizedProsthoRetainerIncome,
            'recognizedDentalLaboratoryIncome' => $recognizedDentalLaboratoryIncome,
            'otherIncomeTotal' => $otherIncomeTotal,
            'privateOwnerIncome' => $privateOwnerIncome,
            'privateOwnerExpense' => $privateOwnerExpense,
            'totalClinicIncome' => $totalClinicIncome,
            'operationalExpense' => $operationalExpense,
            'doctorFee' => $doctorFee,
            'netClinicIncome' => $netClinicIncome,
            'totalExpense' => $totalExpense,
            'netProfit' => $netProfit,
            'netClinicCashflow' => $netClinicCashflow,
            'ownerMutationIncome' => $ownerMutationIncome,
            'ownerMutationExpense' => $ownerMutationExpense,
            'ownerNetCashflow' => $ownerNetCashflow,
            'chartData' => $chartData,
        ]);
    }

    public function kasHarian(Request $request)
    {
        $this->ensureOwnerOrAdmin();

        [$start, $end] = $this->resolvePeriod($request);
        $authUser = Auth::user();
        $isOwner = strtolower((string) ($authUser->role ?? '')) === 'owner';

        $debugNotes = [];
        $hasPaymentChannel = Schema::hasColumn('payments', 'channel');
        $hasPayerType = Schema::hasColumn('income_transactions', 'payer_type');
        $hasOwnerPrivateTable = Schema::hasTable('owner_private_transactions');
        $hasOtherIncomeTable = Schema::hasTable('other_incomes');

        $paymentRows = collect();
        $regularIncomeRows = collect();
        $specialCasePaymentRows = collect();

        $regularIncomeByDay = [];
        $specialCasePaymentByDay = [];
        $totalOperationalPaymentByDay = [];
        $paymentBucketsByDay = [];
        $recognizedByDay = [];
        $expenseBucketsByDay = [];
        $ownerMutationBucketsByDay = [];
        $ownerMutationDetails = [];
        $payerTypesByDay = [];
        $rows = [];
        $paymentDetails = [];
        $recognizedIncomeDetails = [];
        $dailyTraceDetails = [];

        $otherIncomeReportByDay = [];
        $otherIncomeCashflowByDay = [];
        $otherIncomeDetails = [];

        $privateOwnerIncomeByDay = [];
        $privateOwnerExpenseByDay = [];
        $privateOwnerDetails = [];
        $privateOwnerSummary = [
            'income_total' => 0,
            'expense_total' => 0,
            'net_total' => 0,
        ];

        try {
            $operationalPaymentQuery = DB::table('payments as p')
                ->join('income_transactions as it', 'it.id', '=', 'p.transaction_id')
                ->leftJoin('payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
                ->whereBetween(DB::raw('DATE(p.pay_date)'), [$start, $end])
                ->where('it.status', 'paid');

            if ($hasPaymentChannel) {
                $paymentRows = $operationalPaymentQuery
                    ->selectRaw("
                        DATE(p.pay_date) as day,
                        COALESCE(pm.name, '') as method_name,
                        COALESCE(p.channel, '') as channel,
                        SUM(p.amount) as total_amount
                    ")
                    ->groupBy(DB::raw('DATE(p.pay_date)'), 'pm.name', 'p.channel')
                    ->orderBy(DB::raw('DATE(p.pay_date)'))
                    ->get();
            } else {
                $paymentRows = $operationalPaymentQuery
                    ->selectRaw("
                        DATE(p.pay_date) as day,
                        COALESCE(pm.name, '') as method_name,
                        '' as channel,
                        SUM(p.amount) as total_amount
                    ")
                    ->groupBy(DB::raw('DATE(p.pay_date)'), 'pm.name')
                    ->orderBy(DB::raw('DATE(p.pay_date)'))
                    ->get();
            }

            foreach ($paymentRows as $row) {
                $day = (string) $row->day;
                $amount = (float) $row->total_amount;
                $methodName = strtoupper(trim((string) $row->method_name));
                $channel = strtolower(trim((string) $row->channel));

                if (!isset($paymentBucketsByDay[$day])) {
                    $paymentBucketsByDay[$day] = [
                        'tunai' => 0,
                        'bca_transfer' => 0,
                        'bca_edc' => 0,
                        'bca_qris' => 0,
                        'bni_transfer' => 0,
                        'bni_edc' => 0,
                        'bni_qris' => 0,
                        'bri_transfer' => 0,
                        'bri_edc' => 0,
                        'bri_qris' => 0,
                        'lainnya' => 0,
                    ];
                }

                if (!isset($totalOperationalPaymentByDay[$day])) {
                    $totalOperationalPaymentByDay[$day] = 0;
                }

                $bucketKey = 'lainnya';

                if ($methodName === 'TUNAI') {
                    $bucketKey = 'tunai';
                } elseif ($hasPaymentChannel && $methodName === 'BCA') {
                    if ($channel === 'transfer') {
                        $bucketKey = 'bca_transfer';
                    } elseif ($channel === 'edc') {
                        $bucketKey = 'bca_edc';
                    } elseif ($channel === 'qris') {
                        $bucketKey = 'bca_qris';
                    }
                } elseif ($hasPaymentChannel && $methodName === 'BNI') {
                    if ($channel === 'transfer') {
                        $bucketKey = 'bni_transfer';
                    } elseif ($channel === 'edc') {
                        $bucketKey = 'bni_edc';
                    } elseif ($channel === 'qris') {
                        $bucketKey = 'bni_qris';
                    }
                } elseif ($hasPaymentChannel && $methodName === 'BRI') {
                    if ($channel === 'transfer') {
                        $bucketKey = 'bri_transfer';
                    } elseif ($channel === 'edc') {
                        $bucketKey = 'bri_edc';
                    } elseif ($channel === 'qris') {
                        $bucketKey = 'bri_qris';
                    }
                }

                $paymentBucketsByDay[$day][$bucketKey] += $amount;
                $totalOperationalPaymentByDay[$day] += $amount;
            }

            $regularIncomeRows = DB::table('payments as p')
                ->join('income_transactions as it', 'it.id', '=', 'p.transaction_id')
                ->leftJoin('owner_finance_cases as ofc', 'ofc.income_transaction_id', '=', 'it.id')
                ->whereBetween(DB::raw('DATE(p.pay_date)'), [$start, $end])
                ->where('it.status', 'paid')
                ->whereNull('ofc.id')
                ->selectRaw("
                    DATE(p.pay_date) as day,
                    SUM(p.amount) as total_amount
                ")
                ->groupBy(DB::raw('DATE(p.pay_date)'))
                ->orderBy(DB::raw('DATE(p.pay_date)'))
                ->get();

            foreach ($regularIncomeRows as $row) {
                $regularIncomeByDay[(string) $row->day] = (float) $row->total_amount;
            }

            $specialCasePaymentRows = DB::table('payments as p')
                ->join('income_transactions as it', 'it.id', '=', 'p.transaction_id')
                ->join('owner_finance_cases as ofc', 'ofc.income_transaction_id', '=', 'it.id')
                ->whereBetween(DB::raw('DATE(p.pay_date)'), [$start, $end])
                ->where('it.status', 'paid')
                ->selectRaw("
                    DATE(p.pay_date) as day,
                    SUM(p.amount) as total_amount
                ")
                ->groupBy(DB::raw('DATE(p.pay_date)'))
                ->orderBy(DB::raw('DATE(p.pay_date)'))
                ->get();

            foreach ($specialCasePaymentRows as $row) {
                $specialCasePaymentByDay[(string) $row->day] = (float) $row->total_amount;
            }

            $debugNotes[] = 'OK: paymentRows operasional + regular + special case';
        } catch (Throwable $e) {
            $debugNotes[] = 'ERROR paymentRows: ' . $e->getMessage();
            Log::error('Kas Harian paymentRows error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        try {
            if ($hasOtherIncomeTable) {
                $otherIncomeRows = DB::table('other_incomes')
                    ->whereBetween(DB::raw('DATE(trx_date)'), [$start, $end])
                    ->orderBy(DB::raw('DATE(trx_date)'))
                    ->orderBy('id')
                    ->get();

                foreach ($otherIncomeRows as $row) {
                    $day = (string) $row->trx_date;
                    $amount = (float) ($row->amount ?? 0);
                    $includeInReport = (bool) ($row->include_in_report ?? false);
                    $includeInCashflow = (bool) ($row->include_in_cashflow ?? false);
                    $paymentMethod = strtolower(trim((string) ($row->payment_method ?? 'cash')));
                    $paymentChannel = strtolower(trim((string) ($row->payment_channel ?? '')));

                    if ($includeInReport) {
                        $otherIncomeReportByDay[$day] = ($otherIncomeReportByDay[$day] ?? 0) + $amount;

                        if (!isset($dailyTraceDetails[$day])) {
                            $dailyTraceDetails[$day] = [
                                'payments' => [],
                                'expenses' => [],
                                'other_incomes' => [],
                            ];
                        } elseif (!isset($dailyTraceDetails[$day]['other_incomes'])) {
                            $dailyTraceDetails[$day]['other_incomes'] = [];
                        }

                        $otherIncomeItem = [
                            'id' => (int) ($row->id ?? 0),
                            'trx_date' => (string) $row->trx_date,
                            'title' => (string) ($row->title ?? '-'),
                            'source_type' => (string) ($row->source_type ?? '-'),
                            'amount' => $amount,
                            'payment_method' => $paymentMethod,
                            'payment_channel' => $paymentChannel,
                            'notes' => (string) ($row->notes ?? ''),
                            'visibility' => (string) ($row->visibility ?? 'public'),
                            'include_in_report' => $includeInReport,
                            'include_in_cashflow' => $includeInCashflow,
                            'created_by' => (int) ($row->created_by ?? 0),
                        ];

                        $dailyTraceDetails[$day]['other_incomes'][] = $otherIncomeItem;
                        $otherIncomeDetails[] = $otherIncomeItem;
                    }

                    if ($includeInCashflow) {
                        $otherIncomeCashflowByDay[$day] = ($otherIncomeCashflowByDay[$day] ?? 0) + $amount;

                        if (!isset($paymentBucketsByDay[$day])) {
                            $paymentBucketsByDay[$day] = [
                                'tunai' => 0,
                                'bca_transfer' => 0,
                                'bca_edc' => 0,
                                'bca_qris' => 0,
                                'bni_transfer' => 0,
                                'bni_edc' => 0,
                                'bni_qris' => 0,
                                'bri_transfer' => 0,
                                'bri_edc' => 0,
                                'bri_qris' => 0,
                                'lainnya' => 0,
                            ];
                        }

                        if (!isset($totalOperationalPaymentByDay[$day])) {
                            $totalOperationalPaymentByDay[$day] = 0;
                        }

                        $bucketKey = 'lainnya';

                        if ($paymentMethod === 'cash') {
                            $bucketKey = 'tunai';
                        } elseif ($paymentMethod === 'bank') {
                            if ($paymentChannel === 'transfer') {
                                $bucketKey = 'lainnya';
                            } elseif ($paymentChannel === 'qris') {
                                $bucketKey = 'lainnya';
                            } elseif ($paymentChannel === 'edc') {
                                $bucketKey = 'lainnya';
                            }
                        }

                        $paymentBucketsByDay[$day][$bucketKey] += $amount;
                        $totalOperationalPaymentByDay[$day] += $amount;
                    }
                }

                $debugNotes[] = 'OK: other_incomes';
            } else {
                $debugNotes[] = 'INFO: tabel other_incomes belum ada';
            }
        } catch (Throwable $e) {
            $debugNotes[] = 'ERROR other_incomes: ' . $e->getMessage();
            Log::error('Kas Harian other_incomes error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        try {
            $recognizedRows = DB::table('owner_finance_cases')
                ->whereIn('case_type', ['prostodonti', 'retainer', 'lab'])
                ->whereNotNull('revenue_recognized_at')
                ->whereBetween(DB::raw('DATE(revenue_recognized_at)'), [$start, $end])
                ->selectRaw("
                    DATE(revenue_recognized_at) as day,
                    SUM(CASE WHEN case_type IN ('prostodonti', 'retainer') THEN clinic_income_amount ELSE 0 END) as recognized_prostho_retainer,
                    SUM(CASE WHEN case_type = 'lab' THEN clinic_income_amount ELSE 0 END) as recognized_dental_lab,
                    SUM(clinic_income_amount) as recognized_total
                ")
                ->groupBy(DB::raw('DATE(revenue_recognized_at)'))
                ->get();

            foreach ($recognizedRows as $row) {
                $recognizedByDay[(string) $row->day] = [
                    'recognized_prostho_retainer' => (float) $row->recognized_prostho_retainer,
                    'recognized_dental_lab' => (float) $row->recognized_dental_lab,
                    'recognized_total' => (float) $row->recognized_total,
                ];
            }

            $debugNotes[] = 'OK: recognizedRows';
        } catch (Throwable $e) {
            $debugNotes[] = 'ERROR recognizedRows: ' . $e->getMessage();
            Log::error('Kas Harian recognizedRows error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        try {
            $expenseRowsQuery = DB::table('expenses')
                ->whereBetween(DB::raw('DATE(expense_date)'), [$start, $end]);

            if (!$isOwner) {
                $expenseRowsQuery
                    ->where('is_private', false);
            }

            $expenseRows = $expenseRowsQuery
                ->selectRaw("
                    id,
                    DATE(expense_date) as day,
                    expense_date,
                    name,
                    pay_method,
                    amount,
                    created_by,
                    is_private
                ")
                ->orderBy(DB::raw('DATE(expense_date)'))
                ->orderBy('id')
                ->get();

            foreach ($expenseRows as $expense) {
                $day = (string) $expense->day;
                $amount = (float) $expense->amount;
                $payMethod = (string) ($expense->pay_method ?? '');

                if (!isset($expenseBucketsByDay[$day])) {
                    $expenseBucketsByDay[$day] = [
                        'keluar_tunai' => 0,
                        'keluar_non_tunai' => 0,
                    ];
                }

                if (!isset($dailyTraceDetails[$day])) {
                    $dailyTraceDetails[$day] = [
                        'payments' => [],
                        'expenses' => [],
                        'other_incomes' => [],
                    ];
                } elseif (!isset($dailyTraceDetails[$day]['other_incomes'])) {
                    $dailyTraceDetails[$day]['other_incomes'] = [];
                }

                if ($this->isCashExpenseMethod($payMethod)) {
                    $expenseBucketsByDay[$day]['keluar_tunai'] += $amount;
                } else {
                    $expenseBucketsByDay[$day]['keluar_non_tunai'] += $amount;
                }

                $dailyTraceDetails[$day]['expenses'][] = [
                    'expense_id' => (int) $expense->id,
                    'date' => (string) $expense->expense_date,
                    'name' => (string) ($expense->name ?? '-'),
                    'pay_method' => (string) ($expense->pay_method ?? '-'),
                    'amount' => $amount,
                    'created_by' => (int) ($expense->created_by ?? 0),
                    'is_private' => (bool) ($expense->is_private ?? false),
                ];
            }

            $debugNotes[] = $isOwner
                ? 'OK: expenseRows owner membaca semua pengeluaran termasuk private'
                : 'OK: expenseRows admin hanya membaca pengeluaran non-private';
        } catch (Throwable $e) {
            $debugNotes[] = 'ERROR expenseRows: ' . $e->getMessage();
            Log::error('Kas Harian expenseRows error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        try {
            if ($isOwner) {
                $ownerMutationRows = DB::table('owner_account_mutations')
                    ->whereBetween(DB::raw('DATE(mutation_date)'), [$start, $end])
                    ->selectRaw("
                        DATE(mutation_date) as day,
                        mutation_type,
                        SUM(amount) as total_amount
                    ")
                    ->groupBy(DB::raw('DATE(mutation_date)'), 'mutation_type')
                    ->orderBy(DB::raw('DATE(mutation_date)'))
                    ->get();

                foreach ($ownerMutationRows as $mutationRow) {
                    $day = (string) $mutationRow->day;

                    if (!isset($ownerMutationBucketsByDay[$day])) {
                        $ownerMutationBucketsByDay[$day] = [
                            'owner_mutation_income' => 0,
                            'owner_mutation_expense' => 0,
                        ];
                    }

                    if ($mutationRow->mutation_type === 'pemasukan') {
                        $ownerMutationBucketsByDay[$day]['owner_mutation_income'] += (float) $mutationRow->total_amount;
                    } else {
                        $ownerMutationBucketsByDay[$day]['owner_mutation_expense'] += (float) $mutationRow->total_amount;
                    }
                }

                $ownerMutationDetails = DB::table('owner_account_mutations')
                    ->whereBetween(DB::raw('DATE(mutation_date)'), [$start, $end])
                    ->orderBy(DB::raw('DATE(mutation_date)'))
                    ->orderBy('id')
                    ->select([
                        DB::raw('DATE(mutation_date) as mutation_date'),
                        'mutation_type',
                        'description',
                        'amount',
                        'reference_month',
                    ])
                    ->get()
                    ->map(function ($row) {
                        return [
                            'date' => (string) $row->mutation_date,
                            'mutation_type' => (string) $row->mutation_type,
                            'mutation_type_label' => $row->mutation_type === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran',
                            'description' => (string) $row->description,
                            'amount' => (float) $row->amount,
                            'reference_month' => $row->reference_month
                                ? Carbon::parse($row->reference_month)->translatedFormat('F Y')
                                : '-',
                        ];
                    })
                    ->values()
                    ->all();
            }

            $debugNotes[] = 'OK: ownerMutation';
        } catch (Throwable $e) {
            $debugNotes[] = 'ERROR ownerMutation: ' . $e->getMessage();
            Log::error('Kas Harian ownerMutation error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        try {
            if ($isOwner && $hasOwnerPrivateTable) {
                $privateRows = DB::table('owner_private_transactions')
                    ->whereBetween(DB::raw('DATE(trx_date)'), [$start, $end])
                    ->orderBy(DB::raw('DATE(trx_date)'))
                    ->orderBy('id')
                    ->get();

                foreach ($privateRows as $row) {
                    $day = (string) $row->trx_date;
                    $amount = (float) ($row->amount ?? 0);
                    $type = strtolower((string) ($row->type ?? ''));

                    if ($type === 'income') {
                        $privateOwnerIncomeByDay[$day] = ($privateOwnerIncomeByDay[$day] ?? 0) + $amount;
                        $privateOwnerSummary['income_total'] += $amount;
                    } elseif ($type === 'expense') {
                        $privateOwnerExpenseByDay[$day] = ($privateOwnerExpenseByDay[$day] ?? 0) + $amount;
                        $privateOwnerSummary['expense_total'] += $amount;
                    }

                    $privateOwnerDetails[] = [
                        'id' => (int) $row->id,
                        'trx_date' => (string) $row->trx_date,
                        'type' => (string) $row->type,
                        'type_label' => strtolower((string) $row->type) === 'income' ? 'Pemasukan Private' : 'Pengeluaran Private',
                        'description' => (string) ($row->description ?? '-'),
                        'payment_method' => (string) ($row->payment_method ?? '-'),
                        'amount' => $amount,
                        'notes' => (string) ($row->notes ?? ''),
                        'created_by' => (int) ($row->created_by ?? 0),
                    ];
                }

                $privateOwnerSummary['net_total'] = $privateOwnerSummary['income_total'] - $privateOwnerSummary['expense_total'];
                $debugNotes[] = 'OK: owner_private_transactions';
            } elseif ($isOwner && !$hasOwnerPrivateTable) {
                $debugNotes[] = 'INFO: tabel owner_private_transactions belum ada';
            }
        } catch (Throwable $e) {
            $debugNotes[] = 'ERROR owner_private_transactions: ' . $e->getMessage();
            Log::error('Kas Harian owner_private_transactions error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        try {
            $payerRowsFromPayments = collect();

            if ($hasPayerType) {
                $payerRowsFromPayments = DB::table('payments as p')
                    ->join('income_transactions as it', 'it.id', '=', 'p.transaction_id')
                    ->whereBetween(DB::raw('DATE(p.pay_date)'), [$start, $end])
                    ->where('it.status', 'paid')
                    ->selectRaw("DATE(p.pay_date) as day, COALESCE(it.payer_type, 'umum') as payer_type")
                    ->orderBy(DB::raw('DATE(p.pay_date)'))
                    ->get();

                $payerRowsBpjsNoPayment = DB::table('income_transactions as it')
                    ->leftJoin('owner_finance_cases as ofc', 'ofc.income_transaction_id', '=', 'it.id')
                    ->whereBetween(DB::raw('DATE(it.trx_date)'), [$start, $end])
                    ->where('it.status', 'paid')
                    ->whereRaw("LOWER(COALESCE(it.payer_type, 'umum')) = 'bpjs'")
                    ->whereNull('ofc.id')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('payments as p2')
                            ->whereColumn('p2.transaction_id', 'it.id');
                    })
                    ->selectRaw("DATE(it.trx_date) as day, 'bpjs' as payer_type")
                    ->orderBy(DB::raw('DATE(it.trx_date)'))
                    ->get();

                // ZERO VALUE PAID harus tetap muncul walaupun tidak ada kas nyata masuk.
                $payerRowsZeroPaid = DB::table('income_transactions as it')
                    ->whereBetween(DB::raw('DATE(it.trx_date)'), [$start, $end])
                    ->where('it.status', 'paid')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('payments as p2')
                            ->whereColumn('p2.transaction_id', 'it.id');
                    })
                    ->where(function ($query) {
                        $query->where('it.pay_total', 0)
                            ->orWhereNull('it.pay_total');
                    })
                    ->where(function ($query) {
                        $query->where('it.bill_total', 0)
                            ->orWhereNull('it.bill_total');
                    })
                    ->selectRaw("DATE(it.trx_date) as day, COALESCE(it.payer_type, 'umum') as payer_type")
                    ->orderBy(DB::raw('DATE(it.trx_date)'))
                    ->get();

                $payerRows = $payerRowsFromPayments
                    ->concat($payerRowsBpjsNoPayment)
                    ->concat($payerRowsZeroPaid);
            } else {
                $payerRows = DB::table('payments as p')
                    ->join('income_transactions as it', 'it.id', '=', 'p.transaction_id')
                    ->whereBetween(DB::raw('DATE(p.pay_date)'), [$start, $end])
                    ->where('it.status', 'paid')
                    ->selectRaw("DATE(p.pay_date) as day, 'umum' as payer_type")
                    ->orderBy(DB::raw('DATE(p.pay_date)'))
                    ->get();
            }

            foreach ($payerRows as $payerRow) {
                $day = (string) $payerRow->day;
                $payerType = strtolower(trim((string) ($payerRow->payer_type ?? 'umum')));

                if (!isset($payerTypesByDay[$day])) {
                    $payerTypesByDay[$day] = [];
                }

                if ($payerType === '') {
                    $payerType = 'umum';
                }

                $payerTypesByDay[$day][$payerType] = true;
            }

            $debugNotes[] = 'OK: payerRows';
        } catch (Throwable $e) {
            $debugNotes[] = 'ERROR payerRows: ' . $e->getMessage();
            Log::error('Kas Harian payerRows error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        try {
            $paymentTraceQuery = DB::table('payments as p')
                ->join('income_transactions as it', 'it.id', '=', 'p.transaction_id')
                ->leftJoin('patients as pt', 'pt.id', '=', 'it.patient_id')
                ->leftJoin('doctors as d', 'd.id', '=', 'it.doctor_id')
                ->leftJoin('payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
                ->leftJoin('owner_finance_cases as ofc', 'ofc.income_transaction_id', '=', 'it.id')
                ->leftJoin('income_transaction_items as iti', 'iti.transaction_id', '=', 'it.id')
                ->leftJoin('treatments as t', 't.id', '=', 'iti.treatment_id')
                ->whereBetween(DB::raw('DATE(p.pay_date)'), [$start, $end])
                ->where('it.status', 'paid')
                ->selectRaw("
                    p.id as payment_id,
                    DATE(p.pay_date) as day,
                    DATE(p.pay_date) as pay_date,
                    it.id as transaction_id,
                    it.invoice_number,
                    COALESCE(pt.name, '-') as patient_name,
                    COALESCE(d.name, '-') as doctor_name,
                    COALESCE(pm.name, '-') as payment_method_name,
                    " . ($hasPaymentChannel ? "COALESCE(p.channel, '')" : "''") . " as channel,
                    COALESCE(ofc.case_type, '') as case_type,
                    COALESCE(it.payer_type, 'umum') as payer_type,
                    p.amount as amount,
                    GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as treatment_names
                ")
                ->groupBy(
                    'p.id',
                    DB::raw('DATE(p.pay_date)'),
                    'it.id',
                    'it.invoice_number',
                    'pt.name',
                    'd.name',
                    'pm.name',
                    $hasPaymentChannel ? 'p.channel' : DB::raw("''"),
                    'ofc.case_type',
                    'it.payer_type',
                    'p.amount'
                )
                ->orderBy(DB::raw('DATE(p.pay_date)'))
                ->orderBy('it.invoice_number')
                ->orderBy('p.id')
                ->get();

            foreach ($paymentTraceQuery as $paymentTrace) {
                $day = (string) $paymentTrace->day;

                if (!isset($dailyTraceDetails[$day])) {
                    $dailyTraceDetails[$day] = [
                        'payments' => [],
                        'expenses' => [],
                        'other_incomes' => [],
                    ];
                } elseif (!isset($dailyTraceDetails[$day]['other_incomes'])) {
                    $dailyTraceDetails[$day]['other_incomes'] = [];
                }

                $caseLabel = $this->mapOwnerCaseLabel((string) ($paymentTrace->case_type ?? ''));
                $treatmentNames = trim((string) ($paymentTrace->treatment_names ?? ''));
                $caseOrTreatment = $caseLabel === 'Reguler'
                    ? ($treatmentNames !== '' ? $treatmentNames : 'Reguler')
                    : ($treatmentNames !== '' ? $caseLabel . ' • ' . $treatmentNames : $caseLabel);

                $dailyTraceDetails[$day]['payments'][] = [
                    'payment_id' => (int) $paymentTrace->payment_id,
                    'date' => (string) $paymentTrace->pay_date,
                    'transaction_id' => (int) $paymentTrace->transaction_id,
                    'invoice_number' => (string) $paymentTrace->invoice_number,
                    'patient_name' => (string) $paymentTrace->patient_name,
                    'doctor_name' => (string) $paymentTrace->doctor_name,
                    'payer_label' => $this->mapPayerTypeLabel((string) ($paymentTrace->payer_type ?? 'umum')),
                    'case_label' => $caseLabel,
                    'case_or_treatment' => $caseOrTreatment,
                    'payment_method_name' => (string) $paymentTrace->payment_method_name,
                    'channel' => (string) ($paymentTrace->channel ?? ''),
                    'amount' => (float) $paymentTrace->amount,
                ];
            }

            if ($hasPayerType) {
                // ZERO VALUE PAID tetap ditampilkan di rincian sumber data
                $zeroPaidTraceRows = DB::table('income_transactions as it')
                    ->leftJoin('patients as pt', 'pt.id', '=', 'it.patient_id')
                    ->leftJoin('doctors as d', 'd.id', '=', 'it.doctor_id')
                    ->leftJoin('owner_finance_cases as ofc', 'ofc.income_transaction_id', '=', 'it.id')
                    ->leftJoin('income_transaction_items as iti', 'iti.transaction_id', '=', 'it.id')
                    ->leftJoin('treatments as t', 't.id', '=', 'iti.treatment_id')
                    ->whereBetween(DB::raw('DATE(it.trx_date)'), [$start, $end])
                    ->where('it.status', 'paid')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('payments as p2')
                            ->whereColumn('p2.transaction_id', 'it.id');
                    })
                    ->where(function ($query) {
                        $query->where('it.pay_total', 0)
                            ->orWhereNull('it.pay_total');
                    })
                    ->where(function ($query) {
                        $query->where('it.bill_total', 0)
                            ->orWhereNull('it.bill_total');
                    })
                    ->selectRaw("
                        0 as payment_id,
                        DATE(it.trx_date) as day,
                        DATE(it.trx_date) as pay_date,
                        it.id as transaction_id,
                        it.invoice_number,
                        COALESCE(pt.name, '-') as patient_name,
                        COALESCE(d.name, '-') as doctor_name,
                        'TANPA PEMBAYARAN' as payment_method_name,
                        '-' as channel,
                        COALESCE(ofc.case_type, '') as case_type,
                        COALESCE(it.payer_type, 'umum') as payer_type,
                        0 as amount,
                        GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as treatment_names
                    ")
                    ->groupBy(
                        DB::raw('DATE(it.trx_date)'),
                        'it.id',
                        'it.invoice_number',
                        'pt.name',
                        'd.name',
                        'ofc.case_type',
                        'it.payer_type'
                    )
                    ->orderBy(DB::raw('DATE(it.trx_date)'))
                    ->orderBy('it.invoice_number')
                    ->get();

                foreach ($zeroPaidTraceRows as $paymentTrace) {
                    $day = (string) $paymentTrace->day;

                    if (!isset($dailyTraceDetails[$day])) {
                        $dailyTraceDetails[$day] = [
                            'payments' => [],
                            'expenses' => [],
                            'other_incomes' => [],
                        ];
                    } elseif (!isset($dailyTraceDetails[$day]['other_incomes'])) {
                        $dailyTraceDetails[$day]['other_incomes'] = [];
                    }

                    $caseLabel = $this->mapOwnerCaseLabel((string) ($paymentTrace->case_type ?? ''));
                    $treatmentNames = trim((string) ($paymentTrace->treatment_names ?? ''));
                    $caseOrTreatment = $caseLabel === 'Reguler'
                        ? ($treatmentNames !== '' ? $treatmentNames : 'Reguler')
                        : ($treatmentNames !== '' ? $caseLabel . ' • ' . $treatmentNames : $caseLabel);

                    $dailyTraceDetails[$day]['payments'][] = [
                        'payment_id' => 0,
                        'date' => (string) $paymentTrace->pay_date,
                        'transaction_id' => (int) $paymentTrace->transaction_id,
                        'invoice_number' => (string) $paymentTrace->invoice_number,
                        'patient_name' => (string) $paymentTrace->patient_name,
                        'doctor_name' => (string) $paymentTrace->doctor_name,
                        'payer_label' => $this->mapPayerTypeLabel((string) ($paymentTrace->payer_type ?? 'umum')),
                        'case_label' => $caseLabel,
                        'case_or_treatment' => $caseOrTreatment,
                        'payment_method_name' => 'TANPA PEMBAYARAN',
                        'channel' => '-',
                        'amount' => 0.0,
                    ];
                }
            }

            $debugNotes[] = 'OK: paymentTraceDetails';
        } catch (Throwable $e) {
            $debugNotes[] = 'ERROR paymentTraceDetails: ' . $e->getMessage();
            Log::error('Kas Harian paymentTraceDetails error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        try {
            $cursor = strtotime($start);
            $last   = strtotime($end);

            while ($cursor <= $last) {
                $day = date('Y-m-d', $cursor);

                $bucket = $paymentBucketsByDay[$day] ?? [
                    'tunai' => 0,
                    'bca_transfer' => 0,
                    'bca_edc' => 0,
                    'bca_qris' => 0,
                    'bni_transfer' => 0,
                    'bni_edc' => 0,
                    'bni_qris' => 0,
                    'bri_transfer' => 0,
                    'bri_edc' => 0,
                    'bri_qris' => 0,
                    'lainnya' => 0,
                ];

                $expenseBucket = $expenseBucketsByDay[$day] ?? [
                    'keluar_tunai' => 0,
                    'keluar_non_tunai' => 0,
                ];

                $ownerMutationBucket = $ownerMutationBucketsByDay[$day] ?? [
                    'owner_mutation_income' => 0,
                    'owner_mutation_expense' => 0,
                ];

                $recognized = $recognizedByDay[$day] ?? [
                    'recognized_prostho_retainer' => 0,
                    'recognized_dental_lab' => 0,
                    'recognized_total' => 0,
                ];

                $payerTypes = array_keys($payerTypesByDay[$day] ?? []);
                sort($payerTypes);

                $payerLabels = [];
                foreach ($payerTypes as $payerType) {
                    $label = $this->mapPayerTypeLabel($payerType);
                    if (!in_array($label, $payerLabels, true)) {
                        $payerLabels[] = $label;
                    }
                }

                $payerLabel = count($payerLabels) > 0
                    ? implode(' + ', $payerLabels)
                    : '-';

                $otherIncomeReportTotal = (float) ($otherIncomeReportByDay[$day] ?? 0);
                $otherIncomeCashflowTotal = (float) ($otherIncomeCashflowByDay[$day] ?? 0);

                if ($otherIncomeReportTotal > 0) {
                    if ($payerLabel === '-' || $payerLabel === '') {
                        $payerLabel = 'Lain-lain';
                    } else {
                        $payerLabel .= ' + Lain-lain';
                    }
                }

                $totalPaymentOperasional = (float) ($totalOperationalPaymentByDay[$day] ?? 0);
                $masukKlinikReguler = (float) ($regularIncomeByDay[$day] ?? 0);
                $masukKasusKhusus = (float) ($specialCasePaymentByDay[$day] ?? 0);

                $pendapatanDiakuiProsthoRetainer = (float) ($recognized['recognized_prostho_retainer'] ?? 0);
                $pendapatanDiakuiDentalLab = (float) ($recognized['recognized_dental_lab'] ?? 0);
                $pendapatanDiakuiTotal = (float) ($recognized['recognized_total'] ?? 0);

                $masukKlinikOwnerView = $masukKlinikReguler + $pendapatanDiakuiTotal + $otherIncomeReportTotal;

                $keluarTunai = (float) ($expenseBucket['keluar_tunai'] ?? 0);
                $keluarNonTunai = (float) ($expenseBucket['keluar_non_tunai'] ?? 0);
                $keluarKlinik = $keluarTunai + $keluarNonTunai;

                $ownerIncome = (float) ($ownerMutationBucket['owner_mutation_income'] ?? 0);
                $ownerExpense = (float) ($ownerMutationBucket['owner_mutation_expense'] ?? 0);

                $privateIncome = (float) ($privateOwnerIncomeByDay[$day] ?? 0);
                $privateExpense = (float) ($privateOwnerExpenseByDay[$day] ?? 0);
                $privateNet = $privateIncome - $privateExpense;

                $masukTotalOwner = $masukKlinikOwnerView + $ownerIncome + $privateIncome;
                $keluarTotalOwner = $keluarKlinik + $ownerExpense + $privateExpense;
                $netTotalOwner = $masukTotalOwner - $keluarTotalOwner;
                $netTunaiDisetor = (float) $bucket['tunai'] - $keluarTunai;

                $masukTotalKlinik = $masukKlinikOwnerView + $privateIncome;
                $keluarTotalKlinik = $keluarKlinik + $privateExpense;
                $netKasKlinik = $masukTotalKlinik - $keluarTotalKlinik;

                $rows[] = [
                    'date' => $day,
                    'payer_label' => $payerLabel,

                    'tunai' => (float) $bucket['tunai'],
                    'bca_transfer' => (float) $bucket['bca_transfer'],
                    'bca_edc' => (float) $bucket['bca_edc'],
                    'bca_qris' => (float) $bucket['bca_qris'],
                    'bni_transfer' => (float) $bucket['bni_transfer'],
                    'bni_edc' => (float) $bucket['bni_edc'],
                    'bni_qris' => (float) $bucket['bni_qris'],
                    'bri_transfer' => (float) $bucket['bri_transfer'],
                    'bri_edc' => (float) $bucket['bri_edc'],
                    'bri_qris' => (float) $bucket['bri_qris'],
                    'lainnya' => (float) $bucket['lainnya'],

                    'other_income_report_total' => $otherIncomeReportTotal,
                    'other_income_cashflow_total' => $otherIncomeCashflowTotal,

                    'total_pembayaran_operasional' => $totalPaymentOperasional,
                    'masuk_klinik_reguler' => $masukKlinikReguler,
                    'masuk_kasus_khusus' => $masukKasusKhusus,

                    'pendapatan_diakui_prostho_retainer' => $pendapatanDiakuiProsthoRetainer,
                    'pendapatan_diakui_dental_lab' => $pendapatanDiakuiDentalLab,
                    'pendapatan_diakui_total' => $pendapatanDiakuiTotal,
                    'masuk_klinik_owner_view' => $masukKlinikOwnerView,

                    'keluar_tunai' => $keluarTunai,
                    'keluar_non_tunai' => $keluarNonTunai,
                    'keluar_klinik' => $keluarKlinik,

                    'owner_mutation_income' => $ownerIncome,
                    'owner_mutation_expense' => $ownerExpense,

                    'private_owner_income' => $privateIncome,
                    'private_owner_expense' => $privateExpense,
                    'private_owner_net' => $privateNet,

                    'masuk_total_owner' => $masukTotalOwner,
                    'keluar_total_owner' => $keluarTotalOwner,
                    'net_total_owner' => $netTotalOwner,

                    'masuk_total_klinik' => $masukTotalKlinik,
                    'keluar_total_klinik' => $keluarTotalKlinik,
                    'net_kas_klinik' => $netKasKlinik,

                    'net_tunai_disetor' => $netTunaiDisetor,
                ];

                if (!isset($dailyTraceDetails[$day])) {
                    $dailyTraceDetails[$day] = [
                        'payments' => [],
                        'expenses' => [],
                        'other_incomes' => [],
                    ];
                } elseif (!isset($dailyTraceDetails[$day]['other_incomes'])) {
                    $dailyTraceDetails[$day]['other_incomes'] = [];
                }

                usort($dailyTraceDetails[$day]['payments'], function ($a, $b) {
                    $dateCompare = strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
                    if ($dateCompare !== 0) {
                        return $dateCompare;
                    }

                    $invoiceCompare = strcmp((string) ($a['invoice_number'] ?? ''), (string) ($b['invoice_number'] ?? ''));
                    if ($invoiceCompare !== 0) {
                        return $invoiceCompare;
                    }

                    return ((int) ($a['payment_id'] ?? 0)) <=> ((int) ($b['payment_id'] ?? 0));
                });

                $cursor = strtotime('+1 day', $cursor);
            }

            $debugNotes[] = 'OK: rows build';
        } catch (Throwable $e) {
            $debugNotes[] = 'ERROR rows build: ' . $e->getMessage();
            Log::error('Kas Harian rows build error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        try {
            $paymentDetailsQuery = DB::table('payments as p')
                ->join('income_transactions as it', 'it.id', '=', 'p.transaction_id')
                ->leftJoin('patients as pt', 'pt.id', '=', 'it.patient_id')
                ->leftJoin('payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
                ->leftJoin('owner_finance_cases as ofc', 'ofc.income_transaction_id', '=', 'it.id')
                ->whereBetween(DB::raw('DATE(p.pay_date)'), [$start, $end])
                ->where('it.status', 'paid')
                ->whereNull('ofc.id')
                ->orderBy(DB::raw('DATE(p.pay_date)'))
                ->orderBy('it.invoice_number')
                ->orderBy('it.id')
                ->orderBy('p.id');

            if ($hasPayerType) {
                $paymentDetailsUmum = $paymentDetailsQuery
                    ->select([
                        DB::raw('DATE(p.pay_date) as date'),
                        'it.id as transaction_id',
                        'it.invoice_number',
                        'pt.name as patient_name',
                        'it.doctor_id',
                        DB::raw("COALESCE(it.payer_type, 'umum') as payer_type"),
                        'pm.name as payment_method_name',
                        DB::raw($hasPaymentChannel ? "COALESCE(p.channel, '') as channel" : "'' as channel"),
                        'p.amount',
                    ])
                    ->get()
                    ->map(function ($row) {
                        $payerType = strtolower(trim((string) ($row->payer_type ?? 'umum')));

                        return [
                            'date' => $row->date,
                            'transaction_id' => (int) $row->transaction_id,
                            'invoice_number' => $row->invoice_number,
                            'patient_name' => $row->patient_name,
                            'payer_type' => $payerType,
                            'payer_label' => $this->mapPayerTypeLabel($payerType),
                            'payment_method_name' => $row->payment_method_name,
                            'channel' => $row->channel,
                            'amount' => (float) $row->amount,
                        ];
                    });
            } else {
                $paymentDetailsUmum = $paymentDetailsQuery
                    ->select([
                        DB::raw('DATE(p.pay_date) as date'),
                        'it.id as transaction_id',
                        'it.invoice_number',
                        'pt.name as patient_name',
                        DB::raw("'umum' as payer_type"),
                        'pm.name as payment_method_name',
                        DB::raw($hasPaymentChannel ? "COALESCE(p.channel, '') as channel" : "'' as channel"),
                        'p.amount',
                    ])
                    ->get()
                    ->map(function ($row) {
                        return [
                            'date' => $row->date,
                            'transaction_id' => (int) $row->transaction_id,
                            'invoice_number' => $row->invoice_number,
                            'patient_name' => $row->patient_name,
                            'payer_type' => 'umum',
                            'payer_label' => 'Umum',
                            'payment_method_name' => $row->payment_method_name,
                            'channel' => $row->channel,
                            'amount' => (float) $row->amount,
                        ];
                    });
            }

            // ZERO VALUE PAID reguler/admin tetap tampil agar owner/admin tidak bingung
            $paymentDetailsZeroPaid = DB::table('income_transactions as it')
                ->leftJoin('patients as pt', 'pt.id', '=', 'it.patient_id')
                ->leftJoin('owner_finance_cases as ofc', 'ofc.income_transaction_id', '=', 'it.id')
                ->whereBetween(DB::raw('DATE(it.trx_date)'), [$start, $end])
                ->where('it.status', 'paid')
                ->whereNull('ofc.id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('payments as p2')
                        ->whereColumn('p2.transaction_id', 'it.id');
                })
                ->where(function ($query) {
                    $query->where('it.pay_total', 0)
                        ->orWhereNull('it.pay_total');
                })
                ->where(function ($query) {
                    $query->where('it.bill_total', 0)
                        ->orWhereNull('it.bill_total');
                })
                ->orderBy(DB::raw('DATE(it.trx_date)'))
                ->orderBy('it.invoice_number')
                ->select([
                    DB::raw('DATE(it.trx_date) as date'),
                    'it.id as transaction_id',
                    'it.invoice_number',
                    'pt.name as patient_name',
                    DB::raw($hasPayerType ? "COALESCE(it.payer_type, 'umum') as payer_type" : "'umum' as payer_type"),
                    DB::raw("'TANPA PEMBAYARAN' as payment_method_name"),
                    DB::raw("'-' as channel"),
                    DB::raw('0 as amount'),
                ])
                ->get()
                ->map(function ($row) {
                    $payerType = strtolower(trim((string) ($row->payer_type ?? 'umum')));

                    return [
                        'date' => $row->date,
                        'transaction_id' => (int) $row->transaction_id,
                        'invoice_number' => $row->invoice_number,
                        'patient_name' => $row->patient_name,
                        'payer_type' => $payerType,
                        'payer_label' => $this->mapPayerTypeLabel($payerType),
                        'payment_method_name' => 'TANPA PEMBAYARAN',
                        'channel' => '-',
                        'amount' => 0.0,
                    ];
                });

            $paymentDetails = $paymentDetailsUmum
                ->concat($paymentDetailsZeroPaid)
                ->sortBy([
                    ['date', 'asc'],
                    ['invoice_number', 'asc'],
                    ['transaction_id', 'asc'],
                ])
                ->values()
                ->all();

            $debugNotes[] = 'OK: paymentDetails reguler/non owner finance';
        } catch (Throwable $e) {
            $debugNotes[] = 'ERROR paymentDetails: ' . $e->getMessage();
            Log::error('Kas Harian paymentDetails error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        try {
            if ($isOwner) {
                $recognizedIncomeDetails = DB::table('owner_finance_cases as ofc')
                    ->join('income_transactions as it', 'it.id', '=', 'ofc.income_transaction_id')
                    ->leftJoin('patients as pt', 'pt.id', '=', 'it.patient_id')
                    ->whereIn('ofc.case_type', ['prostodonti', 'retainer', 'lab'])
                    ->whereNotNull('ofc.revenue_recognized_at')
                    ->whereBetween(DB::raw('DATE(ofc.revenue_recognized_at)'), [$start, $end])
                    ->orderBy('ofc.revenue_recognized_at')
                    ->orderBy('it.invoice_number')
                    ->select([
                        DB::raw('DATE(ofc.revenue_recognized_at) as recognized_date'),
                        'it.id as transaction_id',
                        'it.invoice_number',
                        'pt.name as patient_name',
                        'ofc.case_type',
                        'ofc.clinic_income_amount',
                        'ofc.lab_bill_amount',
                    ])
                    ->get()
                    ->map(function ($row) {
                        $caseType = strtolower((string) ($row->case_type ?? ''));

                        $caseLabel = match ($caseType) {
                            'prostodonti' => 'Prostodonti',
                            'retainer' => 'Retainer',
                            'lab' => 'Dental Laboratory',
                            default => ucfirst($caseType),
                        };

                        return [
                            'recognized_date' => (string) $row->recognized_date,
                            'transaction_id' => (int) $row->transaction_id,
                            'invoice_number' => (string) $row->invoice_number,
                            'patient_name' => (string) ($row->patient_name ?? '-'),
                            'case_type_label' => $caseLabel,
                            'clinic_income_amount' => (float) ($row->clinic_income_amount ?? 0),
                            'lab_bill_amount' => (float) ($row->lab_bill_amount ?? 0),
                        ];
                    })
                    ->values()
                    ->all();
            }

            $debugNotes[] = 'OK: recognizedIncomeDetails';
        } catch (Throwable $e) {
            $debugNotes[] = 'ERROR recognizedIncomeDetails: ' . $e->getMessage();
            Log::error('Kas Harian recognizedIncomeDetails error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        $grandTotalIncome = 0.0;
        $grandTotalExpense = 0.0;
        $netClinicCashflow = 0.0;

        foreach ($rows as $row) {
            $grandTotalIncome += (float) ($row['masuk_total_klinik'] ?? 0);
            $grandTotalExpense += (float) ($row['keluar_total_klinik'] ?? 0);
            $netClinicCashflow += (float) ($row['net_kas_klinik'] ?? 0);
        }

        return view($this->resolveKasHarianView(), [
            'rows' => $rows,
            'start' => $start,
            'end' => $end,
            'paymentDetails' => $paymentDetails,
            'recognizedIncomeDetails' => $recognizedIncomeDetails,
            'ownerMutationDetails' => $ownerMutationDetails,
            'dailyTraceDetails' => $dailyTraceDetails,
            'debugNotes' => $debugNotes,
            'privateOwnerDetails' => $privateOwnerDetails,
            'privateOwnerSummary' => $privateOwnerSummary,
            'otherIncomeDetails' => $otherIncomeDetails,
            'grandTotalIncome' => $grandTotalIncome,
            'grandTotalExpense' => $grandTotalExpense,
            'netClinicCashflow' => $netClinicCashflow,
        ]);
    }

    public function feeDokter(Request $request)
    {
        $this->ensureOwner();

        [$start, $end] = $this->resolvePeriod($request);

        $rawRows = DB::table('income_transaction_items as iti')
            ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
            ->join('doctors as d', 'd.id', '=', 'it.doctor_id')
            ->join('treatments as t', 't.id', '=', 'iti.treatment_id')
            ->leftJoin('owner_finance_cases as ofc', 'ofc.income_transaction_id', '=', 'it.id')
            ->whereBetween(DB::raw('DATE(it.trx_date)'), [$start, $end])
            ->where('it.status', 'paid')
            ->whereNull('ofc.id')
            ->selectRaw("
                d.id as doctor_id,
                d.name as doctor_name,
                d.type as doctor_type,
                t.id as treatment_id,
                t.name as treatment_name,
                SUM(iti.qty) as qty_total,
                COUNT(DISTINCT it.id) as trx_count,
                SUM(iti.subtotal) as gross_total,
                SUM(iti.fee_amount) as fee_total
            ")
            ->groupBy('d.id', 'd.name', 'd.type', 't.id', 't.name')
            ->orderBy('d.name')
            ->orderBy('t.name')
            ->get();

        $doctorGroups = [];
        $doctorChart = [
            'labels' => [],
            'feeTotals' => [],
            'netTotals' => [],
            'enabled' => false,
            'note' => 'Belum ada data grafik fee dokter pada periode ini.',
        ];

        foreach ($rawRows as $row) {
            $doctorId = (int) $row->doctor_id;
            $gross = (float) $row->gross_total;
            $fee = (float) $row->fee_total;
            $net = $gross - $fee;

            $sourceTransactions = DB::table('income_transaction_items as iti')
                ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
                ->leftJoin('patients as p', 'p.id', '=', 'it.patient_id')
                ->leftJoin('owner_finance_cases as ofc', 'ofc.income_transaction_id', '=', 'it.id')
                ->whereBetween(DB::raw('DATE(it.trx_date)'), [$start, $end])
                ->where('it.status', 'paid')
                ->whereNull('ofc.id')
                ->where('it.doctor_id', $doctorId)
                ->where('iti.treatment_id', (int) $row->treatment_id)
                ->select([
                    'it.id as transaction_id',
                    'it.invoice_number',
                    'it.trx_date',
                    'p.name as patient_name',
                ])
                ->orderBy('it.trx_date')
                ->orderBy('it.invoice_number')
                ->distinct()
                ->get()
                ->map(function ($trx) {
                    return [
                        'transaction_id' => (int) $trx->transaction_id,
                        'invoice_number' => (string) $trx->invoice_number,
                        'trx_date' => (string) $trx->trx_date,
                        'patient_name' => (string) ($trx->patient_name ?? '-'),
                    ];
                })
                ->values()
                ->all();

            if (!isset($doctorGroups[$doctorId])) {
                $doctorGroups[$doctorId] = [
                    'doctor_id' => $doctorId,
                    'doctor_name' => (string) $row->doctor_name,
                    'doctor_type' => (string) $row->doctor_type,
                    'rows' => [],
                    'total_qty' => 0,
                    'total_trx' => 0,
                    'total_gross' => 0,
                    'total_fee' => 0,
                    'total_net' => 0,
                ];
            }

            $doctorGroups[$doctorId]['rows'][] = [
                'doctor_name' => (string) $row->doctor_name,
                'doctor_type' => (string) $row->doctor_type,
                'treatment_id' => (int) $row->treatment_id,
                'treatment_name' => (string) $row->treatment_name,
                'qty_total' => (float) $row->qty_total,
                'trx_count' => (int) $row->trx_count,
                'gross_total' => $gross,
                'fee_total' => $fee,
                'net_klinik' => $net,
                'source_transactions' => $sourceTransactions,
            ];

            $doctorGroups[$doctorId]['total_qty'] += (float) $row->qty_total;
            $doctorGroups[$doctorId]['total_trx'] += (int) $row->trx_count;
            $doctorGroups[$doctorId]['total_gross'] += $gross;
            $doctorGroups[$doctorId]['total_fee'] += $fee;
            $doctorGroups[$doctorId]['total_net'] += $net;
        }

        $doctorGroups = array_values($doctorGroups);

        if (count($doctorGroups) > 0) {
            foreach ($doctorGroups as $doctorGroup) {
                $doctorChart['labels'][] = $doctorGroup['doctor_name'];
                $doctorChart['feeTotals'][] = (float) $doctorGroup['total_fee'];
                $doctorChart['netTotals'][] = (float) $doctorGroup['total_net'];
            }

            $doctorChart['enabled'] = true;
            $doctorChart['note'] = null;
        }

        return view('reports.fee_dokter', [
            'doctorGroups' => $doctorGroups,
            'start' => $start,
            'end' => $end,
            'doctorChart' => $doctorChart,
        ]);
    }

    public function exportKasHarianPdf(Request $request): Response
    {
        $view = $this->kasHarian($request);
        $data = $this->extractViewData($view, [
            'exportedAt' => now(),
            'exportFormat' => 'pdf',
        ]);

        $pdf = Pdf::loadView('reports.exports.kas_harian_pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download($this->buildExportFilename('kas_harian', $request, 'pdf'));
    }

    public function exportKasHarianExcel(Request $request): BinaryFileResponse
    {
        $view = $this->kasHarian($request);
        $data = $this->extractViewData($view, [
            'exportedAt' => now(),
            'exportFormat' => 'excel',
        ]);

        return $this->exportExcelFromView(
            $this->buildExportFilename('kas_harian', $request, 'xlsx'),
            'reports.exports.kas_harian_excel',
            $data
        );
    }

    public function exportLabaRugiPdf(Request $request): Response
    {
        $view = $this->labaRugi($request);
        $data = $this->extractViewData($view, [
            'exportedAt' => now(),
            'exportFormat' => 'pdf',
        ]);

        $pdf = Pdf::loadView('reports.exports.laba_rugi_pdf', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download($this->buildExportFilename('laba_rugi', $request, 'pdf'));
    }

    public function exportLabaRugiExcel(Request $request): BinaryFileResponse
    {
        $view = $this->labaRugi($request);
        $data = $this->extractViewData($view, [
            'exportedAt' => now(),
            'exportFormat' => 'excel',
        ]);

        return $this->exportExcelFromView(
            $this->buildExportFilename('laba_rugi', $request, 'xlsx'),
            'reports.exports.laba_rugi_excel',
            $data
        );
    }

    public function exportFeeDokterPdf(Request $request): Response
    {
        $view = $this->feeDokter($request);
        $data = $this->extractViewData($view, [
            'exportedAt' => now(),
            'exportFormat' => 'pdf',
        ]);

        $pdf = Pdf::loadView('reports.exports.fee_dokter_pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download($this->buildExportFilename('fee_dokter', $request, 'pdf'));
    }

    public function exportFeeDokterExcel(Request $request): BinaryFileResponse
    {
        $view = $this->feeDokter($request);
        $data = $this->extractViewData($view, [
            'exportedAt' => now(),
            'exportFormat' => 'excel',
        ]);

        return $this->exportExcelFromView(
            $this->buildExportFilename('fee_dokter', $request, 'xlsx'),
            'reports.exports.fee_dokter_excel',
            $data
        );
    }
}