<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $setting = class_exists(\App\Models\Setting::class)
            ? \App\Models\Setting::query()->first()
            : null;

        $logoPath = $setting?->logo_path ?: '';

        $today = now()->toDateString();
        $userRole = strtolower((string) ($request->user()->role ?? ''));
        $isOwner = $userRole === 'owner';
        $isAdmin = $userRole === 'admin';

        /*
        |--------------------------------------------------------------------------
        | TANGGAL PILIHAN ADMIN
        |--------------------------------------------------------------------------
        */
        $adminDate = (string) $request->query('admin_date', $today);

        try {
            $adminDateObject = \Carbon\Carbon::parse($adminDate);
            $adminDate = $adminDateObject->toDateString();
        } catch (\Throwable $e) {
            $adminDate = $today;
            $adminDateObject = \Carbon\Carbon::parse($adminDate);
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER RENTANG WAKTU GRAFIK OWNER
        |--------------------------------------------------------------------------
        */
        $allowedRanges = ['weekly', 'monthly', 'yearly'];
        $range = strtolower((string) $request->query('range', 'weekly'));

        if (!in_array($range, $allowedRanges, true)) {
            $range = 'weekly';
        }

        $currentYear = (int) now()->year;
        $selectedYear = (int) $request->query('year', $currentYear);

        $availableYears = collect(
            DB::table('income_transactions')
                ->selectRaw('YEAR(trx_date) as year')
                ->whereNotNull('trx_date')
                ->groupByRaw('YEAR(trx_date)')
                ->orderByRaw('YEAR(trx_date) DESC')
                ->pluck('year')
                ->map(fn ($y) => (int) $y)
                ->all()
        );

        if (!$availableYears->contains($selectedYear)) {
            $selectedYear = $availableYears->first() ?: $currentYear;
        }

        $periodLabels = collect();
        $periodKeys = collect();
        $rangeStart = null;
        $rangeEnd = null;
        $rangeTitle = '';

        if ($range === 'weekly') {
            $rangeStart = now()->copy()->subDays(6)->startOfDay();
            $rangeEnd = now()->copy()->endOfDay();
            $rangeTitle = 'Mingguan';

            $periodLabels = collect(range(6, 0))->map(function ($minusDays) {
                return now()->copy()->subDays($minusDays)->format('d M');
            })->values();

            $periodKeys = collect(range(6, 0))->map(function ($minusDays) {
                return now()->copy()->subDays($minusDays)->toDateString();
            })->values();
        } elseif ($range === 'monthly') {
            $rangeStart = now()->copy()->startOfMonth();
            $rangeEnd = now()->copy()->endOfMonth();
            $rangeTitle = 'Bulanan';

            $daysInMonth = (int) now()->daysInMonth;

            $periodLabels = collect(range(1, $daysInMonth))->map(function ($day) {
                return str_pad((string) $day, 2, '0', STR_PAD_LEFT);
            })->values();

            $periodKeys = collect(range(1, $daysInMonth))->map(function ($day) {
                return now()->copy()->startOfMonth()->day($day)->toDateString();
            })->values();
        } else {
            $rangeStart = now()->copy()->setYear($selectedYear)->startOfYear();
            $rangeEnd = now()->copy()->setYear($selectedYear)->endOfYear();
            $rangeTitle = 'Tahunan ' . $selectedYear;

            $periodLabels = collect([
                'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
            ]);

            $periodKeys = collect(range(1, 12))->map(fn ($month) => str_pad((string) $month, 2, '0', STR_PAD_LEFT));
        }

        /*
        |--------------------------------------------------------------------------
        | RINGKASAN HARI INI (OWNER / UMUM)
        |--------------------------------------------------------------------------
        */
        $todayIncomeCount = DB::table('income_transactions')
            ->whereDate('trx_date', $today)
            ->count();

        $todayIncomeTotal = (float) DB::table('income_transactions')
            ->whereDate('trx_date', $today)
            ->sum('bill_total');

        $todayExpenseTotal = (float) DB::table('expenses')
            ->whereDate('expense_date', $today)
            ->sum('amount');

        $todayCashIn = (float) DB::table('income_transactions')
            ->whereDate('trx_date', $today)
            ->whereRaw('LOWER(COALESCE(status, "")) = ?', ['paid'])
            ->sum('pay_total');

        $todayCashBalance = $todayCashIn - $todayExpenseTotal;

        $todayPatientCount = (int) DB::table('income_transactions')
            ->whereDate('trx_date', $today)
            ->whereNotNull('patient_id')
            ->distinct('patient_id')
            ->count('patient_id');

        $todayActionCount = (int) DB::table('income_transaction_items as iti')
            ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
            ->whereDate('it.trx_date', $today)
            ->count('iti.id');

        /*
        |--------------------------------------------------------------------------
        | ADMIN BASE QUERY — SAMAKAN DENGAN MODUL PEMASUKAN
        |--------------------------------------------------------------------------
        */
        $adminIncomeBaseQuery = DB::table('income_transactions')
            ->whereDate('trx_date', $adminDate);

        $adminIncomeTotal = (float) (clone $adminIncomeBaseQuery)->sum('bill_total');

        $adminExpenseTotal = (float) DB::table('expenses')
            ->whereDate('expense_date', $adminDate)
            ->sum('amount');

        $adminPatientCount = (int) (clone $adminIncomeBaseQuery)
            ->whereNotNull('patient_id')
            ->distinct('patient_id')
            ->count('patient_id');

        $adminActionCount = (int) DB::table('income_transaction_items as iti')
            ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
            ->whereDate('it.trx_date', $adminDate)
            ->count('iti.id');

        $adminTransactionCount = (int) (clone $adminIncomeBaseQuery)->count();

        /*
        |--------------------------------------------------------------------------
        | OWNER FINANCE SUMMARY (OWNER ONLY)
        |--------------------------------------------------------------------------
        */
        $ownerNeedsSetupCount = 0;
        $ownerInProgressCount = 0;
        $ownerDoneCount = 0;
        $ownerOrthoRunningFunds = 0;
        $ownerFinanceAlerts = collect();

        if ($isOwner && DB::getSchemaBuilder()->hasTable('owner_finance_cases')) {
            $ownerNeedsSetupCount = (int) DB::table('owner_finance_cases')
                ->where(function ($q) {
                    $q->where('needs_setup', 1)
                        ->orWhere('owner_followup_status', 'needs_setup')
                        ->orWhereNull('owner_followup_status');
                })
                ->count();

            $ownerInProgressCount = (int) DB::table('owner_finance_cases')
                ->whereIn('owner_followup_status', ['followed_up', 'in_progress'])
                ->count();

            $ownerDoneCount = (int) DB::table('owner_finance_cases')
                ->where('owner_followup_status', 'done')
                ->count();

            $ownerOrthoRunningFunds = (float) DB::table('owner_finance_cases')
                ->where('case_type', 'ortho')
                ->where(function ($q) {
                    $q->where('needs_setup', 1)
                        ->orWhereIn('owner_followup_status', ['needs_setup', 'followed_up', 'in_progress'])
                        ->orWhereNull('owner_followup_status');
                })
                ->sum('ortho_remaining_balance');

            $ownerFinanceAlerts = DB::table('owner_finance_cases as ofc')
                ->leftJoin('income_transactions as it', 'it.id', '=', 'ofc.income_transaction_id')
                ->leftJoin('patients as p', 'p.id', '=', 'it.patient_id')
                ->leftJoin('doctors as d', 'd.id', '=', 'it.doctor_id')
                ->select([
                    'ofc.id',
                    'ofc.case_type',
                    'ofc.owner_followup_status',
                    'ofc.case_progress_status',
                    'ofc.owner_last_action_note',
                    'it.invoice_number',
                    'it.trx_date',
                    'p.name as patient_name',
                    'd.name as doctor_name',
                ])
                ->where(function ($q) {
                    $q->where('ofc.needs_setup', 1)
                        ->orWhere('ofc.owner_followup_status', 'needs_setup')
                        ->orWhereNull('ofc.owner_followup_status');
                })
                ->orderByDesc('it.trx_date')
                ->orderByDesc('ofc.id')
                ->limit(5)
                ->get();
        }
// =========================
// OWNER CONTROL TOWER SUMMARY (TAMBAHAN AMAN)
// =========================

$ownerTotalHoldingFunds = 0;
$ownerTotalRunningFunds = 0;
$ownerTotalRecognizedIncome = 0;
$ownerTotalPotentialIncome = 0;
$ownerPriorityCases = collect();

if ($isOwner && DB::getSchemaBuilder()->hasTable('owner_finance_cases')) {

    // Dana tertahan (belum siap / belum follow up)
    $ownerTotalHoldingFunds = (float) DB::table('owner_finance_cases')
        ->where(function ($q) {
            $q->where('needs_setup', 1)
              ->orWhereNull('owner_followup_status')
              ->orWhere('owner_followup_status', 'needs_setup');
        })
        ->sum('clinic_income_amount');

    // Dana berjalan
    $ownerTotalRunningFunds = (float) DB::table('owner_finance_cases')
        ->whereIn('owner_followup_status', ['followed_up', 'in_progress'])
        ->sum('clinic_income_amount');

    // Sudah jadi income
    $ownerTotalRecognizedIncome = (float) DB::table('owner_finance_cases')
        ->whereNotNull('revenue_recognized_at')
        ->sum('clinic_income_amount');

    // Potensi income
    $ownerTotalPotentialIncome = (float) DB::table('owner_finance_cases')
        ->whereNull('revenue_recognized_at')
        ->sum('clinic_income_amount');

    // =========================
    // PRIORITY CASES (TOP 5)
    // =========================

    $ownerPriorityCases = DB::table('owner_finance_cases as ofc')
        ->leftJoin('income_transactions as it', 'it.id', '=', 'ofc.income_transaction_id')
        ->leftJoin('patients as p', 'p.id', '=', 'it.patient_id')
        ->leftJoin('doctors as d', 'd.id', '=', 'it.doctor_id')
        ->select([
            'ofc.id',
            'ofc.case_type',
            'ofc.owner_followup_status',
            'ofc.needs_setup',
            'ofc.lab_paid',
            'ofc.installed',
            'it.trx_date',
            'it.invoice_number',
            'p.name as patient_name',
            'd.name as doctor_name',
        ])
        ->orderByRaw("
            CASE
                WHEN ofc.needs_setup = 1 THEN 1
                WHEN ofc.lab_paid = 0 OR ofc.installed = 0 THEN 2
                WHEN ofc.owner_followup_status IN ('followed_up','in_progress') THEN 3
                ELSE 4
            END ASC
        ")
        ->orderBy('it.trx_date', 'asc')
        ->limit(5)
        ->get();
}
        /*
        |--------------------------------------------------------------------------
        | KPI OWNER / EXECUTIVE PERIODE AKTIF
        |--------------------------------------------------------------------------
        */
        $periodGrossIncome = (float) DB::table('income_transactions')
            ->whereBetween('trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->sum('bill_total');

        $periodPaidIncome = (float) DB::table('income_transactions')
            ->whereBetween('trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->whereRaw('LOWER(COALESCE(status, "")) = ?', ['paid'])
            ->sum('pay_total');

        $periodExpenseTotal = (float) DB::table('expenses')
            ->whereBetween('expense_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->sum('amount');

        $periodActionCount = (int) DB::table('income_transaction_items as iti')
            ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
            ->whereBetween('it.trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->count('iti.id');

        $periodProfit = $periodPaidIncome - $periodExpenseTotal;

        /*
        |--------------------------------------------------------------------------
        | GRAFIK OWNER - PERIODE AKTIF
        |--------------------------------------------------------------------------
        */
        if ($range === 'yearly') {
            $incomeRows = DB::table('income_transactions')
                ->selectRaw('MONTH(trx_date) as period_key, SUM(bill_total) as total_amount')
                ->whereBetween('trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->groupByRaw('MONTH(trx_date)')
                ->orderByRaw('MONTH(trx_date)')
                ->get();

            $paidIncomeRows = DB::table('income_transactions')
                ->selectRaw('MONTH(trx_date) as period_key, SUM(pay_total) as total_amount')
                ->whereBetween('trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->whereRaw('LOWER(COALESCE(status, "")) = ?', ['paid'])
                ->groupByRaw('MONTH(trx_date)')
                ->orderByRaw('MONTH(trx_date)')
                ->get();

            $expenseRows = DB::table('expenses')
                ->selectRaw('MONTH(expense_date) as period_key, SUM(amount) as total_amount')
                ->whereBetween('expense_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->groupByRaw('MONTH(expense_date)')
                ->orderByRaw('MONTH(expense_date)')
                ->get();

            $actionRows = DB::table('income_transaction_items as iti')
                ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
                ->selectRaw('MONTH(it.trx_date) as period_key, COUNT(iti.id) as total_items')
                ->whereBetween('it.trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->groupByRaw('MONTH(it.trx_date)')
                ->orderByRaw('MONTH(it.trx_date)')
                ->get();
        } else {
            $incomeRows = DB::table('income_transactions')
                ->selectRaw('DATE(trx_date) as period_key, SUM(bill_total) as total_amount')
                ->whereBetween('trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->groupByRaw('DATE(trx_date)')
                ->orderByRaw('DATE(trx_date)')
                ->get();

            $paidIncomeRows = DB::table('income_transactions')
                ->selectRaw('DATE(trx_date) as period_key, SUM(pay_total) as total_amount')
                ->whereBetween('trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->whereRaw('LOWER(COALESCE(status, "")) = ?', ['paid'])
                ->groupByRaw('DATE(trx_date)')
                ->orderByRaw('DATE(trx_date)')
                ->get();

            $expenseRows = DB::table('expenses')
                ->selectRaw('DATE(expense_date) as period_key, SUM(amount) as total_amount')
                ->whereBetween('expense_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->groupByRaw('DATE(expense_date)')
                ->orderByRaw('DATE(expense_date)')
                ->get();

            $actionRows = DB::table('income_transaction_items as iti')
                ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
                ->selectRaw('DATE(it.trx_date) as period_key, COUNT(iti.id) as total_items')
                ->whereBetween('it.trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->groupByRaw('DATE(it.trx_date)')
                ->orderByRaw('DATE(it.trx_date)')
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | GRAFIK DOKTER OWNER
        |--------------------------------------------------------------------------
        */
        $doctorRows = DB::table('income_transactions as it')
            ->leftJoin('doctors as d', 'd.id', '=', 'it.doctor_id')
            ->selectRaw('COALESCE(d.name, "Tanpa Dokter") as doctor_name, SUM(it.bill_total) as total_amount')
            ->whereBetween('it.trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->groupBy('doctor_name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        $doctorLabels = $doctorRows->pluck('doctor_name')->values();
        $doctorSeries = $doctorRows->map(fn ($row) => (float) ($row->total_amount ?? 0))->values();

        /*
        |--------------------------------------------------------------------------
        | GRAFIK KATEGORI OWNER
        |--------------------------------------------------------------------------
        */
        $categoryRows = DB::table('income_transaction_items as iti')
            ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
            ->join('treatments as t', 't.id', '=', 'iti.treatment_id')
            ->leftJoin('treatment_categories as tc', 'tc.id', '=', 't.category_id')
            ->selectRaw('COALESCE(tc.name, "Tanpa Kategori") as category_name, SUM(iti.subtotal) as total_amount')
            ->whereBetween('it.trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->groupBy('category_name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        $categoryLabels = $categoryRows->pluck('category_name')->values();
        $categorySeries = $categoryRows->map(fn ($row) => (float) ($row->total_amount ?? 0))->values();

        /*
        |--------------------------------------------------------------------------
        | TOP TINDAKAN OWNER
        |--------------------------------------------------------------------------
        */
        $topActionsRows = DB::table('income_transaction_items as iti')
            ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
            ->leftJoin('treatments as t', 't.id', '=', 'iti.treatment_id')
            ->selectRaw('
                COALESCE(t.name, "Tanpa Tindakan") as treatment_name,
                COUNT(iti.id) as total_qty,
                SUM(iti.subtotal) as total_amount
            ')
            ->whereBetween('it.trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->groupBy('treatment_name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        $topActionLabels = $topActionsRows->pluck('treatment_name')->values();
        $topActionSeries = $topActionsRows->map(fn ($row) => (float) ($row->total_amount ?? 0))->values();

        $topDoctorsRows = $doctorRows;

        /*
        |--------------------------------------------------------------------------
        | DROPDOWN & TREND PER TINDAKAN OWNER
        |--------------------------------------------------------------------------
        */
        $treatmentOptions = DB::table('treatments')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $selectedTreatmentId = (int) $request->query('treatment_id', 0);

        if (!$treatmentOptions->pluck('id')->contains($selectedTreatmentId)) {
            $selectedTreatmentId = (int) ($treatmentOptions->first()->id ?? 0);
        }

        $selectedTreatmentName = (string) ($treatmentOptions->firstWhere('id', $selectedTreatmentId)->name ?? 'Belum ada tindakan');

        if ($selectedTreatmentId > 0) {
            if ($range === 'yearly') {
                $treatmentTrendRows = DB::table('income_transaction_items as iti')
                    ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
                    ->selectRaw('MONTH(it.trx_date) as period_key, COUNT(iti.id) as total_qty, SUM(iti.subtotal) as total_amount')
                    ->where('iti.treatment_id', $selectedTreatmentId)
                    ->whereBetween('it.trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                    ->groupByRaw('MONTH(it.trx_date)')
                    ->orderByRaw('MONTH(it.trx_date)')
                    ->get();
            } else {
                $treatmentTrendRows = DB::table('income_transaction_items as iti')
                    ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
                    ->selectRaw('DATE(it.trx_date) as period_key, COUNT(iti.id) as total_qty, SUM(iti.subtotal) as total_amount')
                    ->where('iti.treatment_id', $selectedTreatmentId)
                    ->whereBetween('it.trx_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                    ->groupByRaw('DATE(it.trx_date)')
                    ->orderByRaw('DATE(it.trx_date)')
                    ->get();
            }
        } else {
            $treatmentTrendRows = collect();
        }

        /*
        |--------------------------------------------------------------------------
        | MAPPING SERIES OWNER
        |--------------------------------------------------------------------------
        */
        if ($range === 'yearly') {
            $incomeMap = $incomeRows->keyBy(fn ($row) => str_pad((string) $row->period_key, 2, '0', STR_PAD_LEFT));
            $paidIncomeMap = $paidIncomeRows->keyBy(fn ($row) => str_pad((string) $row->period_key, 2, '0', STR_PAD_LEFT));
            $expenseMap = $expenseRows->keyBy(fn ($row) => str_pad((string) $row->period_key, 2, '0', STR_PAD_LEFT));
            $actionMap = $actionRows->keyBy(fn ($row) => str_pad((string) $row->period_key, 2, '0', STR_PAD_LEFT));
            $treatmentTrendMap = $treatmentTrendRows->keyBy(fn ($row) => str_pad((string) $row->period_key, 2, '0', STR_PAD_LEFT));
        } else {
            $incomeMap = $incomeRows->keyBy('period_key');
            $paidIncomeMap = $paidIncomeRows->keyBy('period_key');
            $expenseMap = $expenseRows->keyBy('period_key');
            $actionMap = $actionRows->keyBy('period_key');
            $treatmentTrendMap = $treatmentTrendRows->keyBy('period_key');
        }

        $incomeSeries = $periodKeys->map(fn ($key) => (float) ($incomeMap[$key]->total_amount ?? 0))->values();
        $paidIncomeSeries = $periodKeys->map(fn ($key) => (float) ($paidIncomeMap[$key]->total_amount ?? 0))->values();
        $expenseSeries = $periodKeys->map(fn ($key) => (float) ($expenseMap[$key]->total_amount ?? 0))->values();
        $actionSeries = $periodKeys->map(fn ($key) => (int) ($actionMap[$key]->total_items ?? 0))->values();
        $profitSeries = $periodKeys->map(function ($key) use ($paidIncomeMap, $expenseMap) {
            return (float) (($paidIncomeMap[$key]->total_amount ?? 0) - ($expenseMap[$key]->total_amount ?? 0));
        })->values();

        $treatmentTrendQtySeries = $periodKeys->map(fn ($key) => (int) ($treatmentTrendMap[$key]->total_qty ?? 0))->values();
        $treatmentTrendAmountSeries = $periodKeys->map(fn ($key) => (float) ($treatmentTrendMap[$key]->total_amount ?? 0))->values();

        /*
        |--------------------------------------------------------------------------
        | GRAFIK ADMIN - PER JAM UNTUK TANGGAL PILIHAN
        |--------------------------------------------------------------------------
        */
        $adminHourlyLabels = collect(range(0, 23))->map(function ($hour) {
            return str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
        })->values();

        $adminIncomeHourlyRows = DB::table('income_transactions')
            ->selectRaw('HOUR(created_at) as hour_key, SUM(bill_total) as total_amount')
            ->whereDate('trx_date', $adminDate)
            ->groupByRaw('HOUR(created_at)')
            ->orderByRaw('HOUR(created_at)')
            ->get();

        $adminActionHourlyRows = DB::table('income_transaction_items as iti')
            ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
            ->selectRaw('HOUR(it.created_at) as hour_key, COUNT(iti.id) as total_items')
            ->whereDate('it.trx_date', $adminDate)
            ->groupByRaw('HOUR(it.created_at)')
            ->orderByRaw('HOUR(it.created_at)')
            ->get();

        $adminIncomeHourlyMap = $adminIncomeHourlyRows->keyBy('hour_key');
        $adminActionHourlyMap = $adminActionHourlyRows->keyBy('hour_key');

        $adminIncomeHourlySeries = collect(range(0, 23))->map(function ($hour) use ($adminIncomeHourlyMap) {
            return (float) ($adminIncomeHourlyMap[$hour]->total_amount ?? 0);
        })->values();

        $adminActionHourlySeries = collect(range(0, 23))->map(function ($hour) use ($adminActionHourlyMap) {
            return (int) ($adminActionHourlyMap[$hour]->total_items ?? 0);
        })->values();

        /*
        |--------------------------------------------------------------------------
        | TRANSAKSI TAMPIL
        |--------------------------------------------------------------------------
        */
        $recentTransactionsQuery = DB::table('income_transactions as it')
            ->leftJoin('patients as p', 'p.id', '=', 'it.patient_id')
            ->leftJoin('doctors as d', 'd.id', '=', 'it.doctor_id')
            ->select([
                'it.invoice_number',
                'it.trx_date',
                'it.status',
                'it.bill_total',
                'p.name as patient_name',
                'd.name as doctor_name',
            ])
            ->whereDate('it.trx_date', $isAdmin ? $adminDate : $today)
            ->orderByDesc('it.id');

        $recentTransactions = $isAdmin
            ? $recentTransactionsQuery->get()
            : $recentTransactionsQuery->limit(5)->get();

        /*
        |--------------------------------------------------------------------------
        | ALERT STOK MINIMUM
        |--------------------------------------------------------------------------
        */
        $stockAlertItems = DB::table('inventory_items as ii')
            ->leftJoin('inventory_movements as im', 'im.item_id', '=', 'ii.id')
            ->where('ii.is_active', 1)
            ->groupBy('ii.id', 'ii.name', 'ii.unit', 'ii.minimum_stock')
            ->selectRaw('
                ii.id,
                ii.name,
                ii.unit,
                COALESCE(ii.minimum_stock, 0) as minimum_stock,
                COALESCE(SUM(im.qty), 0) as current_stock
            ')
            ->havingRaw('COALESCE(ii.minimum_stock, 0) > 0')
            ->havingRaw('COALESCE(SUM(im.qty), 0) <= COALESCE(ii.minimum_stock, 0)')
            ->orderBy('current_stock')
            ->orderBy('ii.name')
            ->limit(5)
            ->get();

        $stockAlertCount = $stockAlertItems->count();

        return view('dashboard', compact(
            'logoPath',
            'today',
            'userRole',
            'isOwner',
            'isAdmin',
            'adminDate',
            'adminDateObject',
            'range',
            'currentYear',
            'selectedYear',
            'availableYears',
            'periodLabels',
            'periodKeys',
            'rangeStart',
            'rangeEnd',
            'rangeTitle',
            'todayIncomeCount',
            'todayIncomeTotal',
            'todayExpenseTotal',
            'todayCashIn',
            'todayCashBalance',
            'todayPatientCount',
            'todayActionCount',
            'adminIncomeTotal',
            'adminExpenseTotal',
            'adminPatientCount',
            'adminActionCount',
            'adminTransactionCount',
            'ownerNeedsSetupCount',
            'ownerInProgressCount',
            'ownerDoneCount',
            'ownerOrthoRunningFunds',
            'ownerFinanceAlerts',
            'periodGrossIncome',
            'periodPaidIncome',
            'periodExpenseTotal',
            'periodActionCount',
            'periodProfit',
            'incomeSeries',
            'paidIncomeSeries',
            'expenseSeries',
            'actionSeries',
            'profitSeries',
            'doctorLabels',
            'doctorSeries',
            'categoryLabels',
            'categorySeries',
            'topActionsRows',
            'topActionLabels',
            'topActionSeries',
            'topDoctorsRows',
            'treatmentOptions',
            'selectedTreatmentId',
            'selectedTreatmentName',
            'treatmentTrendQtySeries',
            'treatmentTrendAmountSeries',
            'adminHourlyLabels',
            'adminIncomeHourlySeries',
            'adminActionHourlySeries',
            'recentTransactions',
            'stockAlertItems',
            'stockAlertCount'
        ));
    }
}