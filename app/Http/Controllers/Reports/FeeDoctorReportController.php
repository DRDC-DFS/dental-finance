<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeDoctorReportController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->query('start');
        $end   = $request->query('end');

        if (!$start) $start = now()->subDays(30)->toDateString();
        if (!$end)   $end   = now()->toDateString();

        // =========================
        // 1) GROSS PER DOKTER (dari transaksi)
        // =========================
        $grossSub = DB::table('income_transactions as it')
            ->join('doctors as d', 'd.id', '=', 'it.doctor_id')
            ->whereRaw("LOWER(it.status) IN ('lunas','paid')")
            ->whereBetween('it.trx_date', [$start, $end])
            ->selectRaw("
                d.id as doctor_id,
                d.name as doctor_name,
                d.type as doctor_type,
                COUNT(it.id) as trx_count,
                SUM(COALESCE(it.pay_total, 0)) as gross_total
            ")
            ->groupBy('d.id', 'd.name', 'd.type');

        // =========================
        // 2) FEE PER DOKTER (snapshot dari items.fee_amount)
        // FK item => transaction_id
        // =========================
        $feeSub = DB::table('income_transaction_items as iti')
            ->join('income_transactions as it', 'it.id', '=', 'iti.transaction_id')
            ->join('doctors as d', 'd.id', '=', 'it.doctor_id')
            ->whereRaw("LOWER(it.status) IN ('lunas','paid')")
            ->whereBetween('it.trx_date', [$start, $end])
            ->selectRaw("
                d.id as doctor_id,
                SUM(COALESCE(iti.fee_amount, 0)) as fee_total
            ")
            ->groupBy('d.id');

        // =========================
        // 3) JOIN & OUTPUT (KEEP AS OBJECTS for Blade)
        // =========================
        $rows = DB::query()
            ->fromSub($grossSub, 'g')
            ->leftJoinSub($feeSub, 'f', function ($join) {
                $join->on('f.doctor_id', '=', 'g.doctor_id');
            })
            ->selectRaw("
                g.doctor_id,
                g.doctor_name,
                g.doctor_type,
                g.trx_count,
                g.gross_total,
                COALESCE(f.fee_total, 0) as fee_total
            ")
            ->orderBy('g.doctor_name')
            ->get();

        // Tambahkan property net_klinik + aturan owner fee = 0
        foreach ($rows as $r) {
            $gross = (float)($r->gross_total ?? 0);
            $fee   = (float)($r->fee_total ?? 0);

            if (strtolower((string)$r->doctor_type) === 'owner') {
                $fee = 0;
                $r->fee_total = 0;
            }

            $r->net_klinik = $gross - $fee;
        }

        // Kirim variabel yang aman untuk blade lama & baru
        return view('reports.fee_dokter', [
            'rows'      => $rows,
            'perDoctor' => $rows,
            'start'     => $start,
            'end'       => $end,
        ]);
    }
}