<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabaRugiReportController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->query('start');
        $end   = $request->query('end');

        if (!$start) $start = now()->subDays(30)->toDateString();
        if (!$end)   $end   = now()->toDateString();

        $totalIncome = (float) DB::table('income_transactions')
            ->whereBetween('trx_date', [$start, $end])
            ->where('status', 'LUNAS')
            ->sum('pay_total');

        $totalExpense = (float) DB::table('expenses')
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        $labaBersih = $totalIncome - $totalExpense;

        return view('reports.laba_rugi', [
            'start' => $start,
            'end' => $end,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'labaBersih' => $labaBersih,
        ]);
    }
}