<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export PDF Kas Harian</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 11px;
            margin-bottom: 2px;
        }

        .section-title {
            margin-top: 18px;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        th, td {
            border: 1px solid #9ca3af;
            padding: 6px 7px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: #6b7280;
        }

        .total-row {
            font-weight: bold;
            background: #f9fafb;
        }

        .summary-box {
            width: 32%;
            display: inline-block;
            vertical-align: top;
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            margin-right: 1%;
            box-sizing: border-box;
        }

        .summary-label {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 16px;
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
@php
    $role = strtolower((string) (auth()->user()->role ?? ''));
    $isOwner = $role === 'owner';

    $rows = is_array($rows ?? null) ? $rows : [];
    $paymentDetails = is_array($paymentDetails ?? null) ? $paymentDetails : [];
    $recognizedIncomeDetails = is_array($recognizedIncomeDetails ?? null) ? $recognizedIncomeDetails : [];
    $ownerMutationDetails = is_array($ownerMutationDetails ?? null) ? $ownerMutationDetails : [];
    $privateOwnerDetails = is_array($privateOwnerDetails ?? null) ? $privateOwnerDetails : [];
    $otherIncomeDetails = is_array($otherIncomeDetails ?? null) ? $otherIncomeDetails : [];
    $privateOwnerSummary = is_array($privateOwnerSummary ?? null) ? $privateOwnerSummary : [
        'income_total' => 0,
        'expense_total' => 0,
        'net_total' => 0,
    ];

    $formatNominal = function ($value) {
        return number_format((float) $value, 0, ',', '.');
    };

    $formatTanggal = function ($value) {
        if (!$value) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d-m-Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $totalsAdmin = [
        'other_income_report_total' => 0,
        'total_pembayaran_operasional' => 0,
        'masuk_klinik_reguler' => 0,
        'masuk_kasus_khusus' => 0,
        'keluar_tunai' => 0,
        'keluar_non_tunai' => 0,
        'net_tunai_disetor' => 0,
    ];

    $totalsOwner = [
        'masuk_klinik_owner_view' => 0,
        'private_owner_income' => 0,
        'masuk_total_klinik' => 0,
        'keluar_klinik' => 0,
        'private_owner_expense' => 0,
        'keluar_total_klinik' => 0,
        'net_kas_klinik' => 0,
        'pendapatan_diakui_prostho_retainer' => 0,
        'pendapatan_diakui_dental_lab' => 0,
        'pendapatan_diakui_total' => 0,
        'owner_mutation_income' => 0,
        'owner_mutation_expense' => 0,
        'private_owner_net' => 0,
        'masuk_total_owner' => 0,
        'keluar_total_owner' => 0,
        'net_total_owner' => 0,
    ];

    foreach ($rows as $r) {
        if (!is_array($r)) {
            continue;
        }

        foreach (array_keys($totalsAdmin) as $key) {
            $totalsAdmin[$key] += (float) ($r[$key] ?? 0);
        }

        foreach (array_keys($totalsOwner) as $key) {
            $totalsOwner[$key] += (float) ($r[$key] ?? 0);
        }
    }

    $grandTotalIncome = (float) ($grandTotalIncome ?? 0);
    $grandTotalExpense = (float) ($grandTotalExpense ?? 0);
    $netClinicCashflow = (float) ($netClinicCashflow ?? 0);

    $periodStart = $start ?? now()->toDateString();
    $periodEnd = $end ?? now()->toDateString();

    $periodeLabel = $periodStart === $periodEnd
        ? $formatTanggal($periodStart)
        : $formatTanggal($periodStart) . ' s/d ' . $formatTanggal($periodEnd);
@endphp

<div class="title">Laporan Kas Harian</div>
<div class="subtitle">Periode: {{ $periodeLabel }}</div>
<div class="subtitle">Role Export: {{ $isOwner ? 'OWNER' : 'ADMIN' }}</div>
<div class="subtitle">Diexport pada: {{ $formatTanggal(($exportedAt ?? now())->format('Y-m-d')) }} {{ ($exportedAt ?? now())->format('H:i:s') }}</div>

@if($isOwner)
    <div style="margin-top: 14px; margin-bottom: 14px;">
        <div class="summary-box">
            <div class="summary-label">Total Pendapatan Klinik</div>
            <div class="summary-value">{{ $formatNominal($grandTotalIncome) }}</div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Total Pengeluaran Klinik</div>
            <div class="summary-value">{{ $formatNominal($grandTotalExpense) }}</div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Net Arus Kas Klinik</div>
            <div class="summary-value">{{ $formatNominal($netClinicCashflow) }}</div>
        </div>
    </div>
@endif

<div class="section-title">Ringkasan Kas Harian</div>
<table>
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Keterangan</th>
            <th class="text-end">Total Penerimaan (Tunai + Bank)</th>
            <th class="text-end">Pendapatan Klinik (Reguler)</th>
            <th class="text-end">Pemasukan Non-Pasien</th>
            <th class="text-end">Pembayaran Kasus Khusus</th>
            <th class="text-end">Pengeluaran Tunai Klinik</th>
            <th class="text-end">Pengeluaran Non Tunai Klinik</th>
            <th class="text-end">Net Setoran Kas Harian</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $r)
            <tr>
                <td>{{ $formatTanggal($r['date'] ?? '-') }}</td>
                <td>{{ $r['payer_label'] ?? '-' }}</td>
                <td class="text-end">{{ $formatNominal($r['total_pembayaran_operasional'] ?? 0) }}</td>
                <td class="text-end">{{ $formatNominal($r['masuk_klinik_reguler'] ?? 0) }}</td>
                <td class="text-end">{{ $formatNominal($r['other_income_report_total'] ?? 0) }}</td>
                <td class="text-end">{{ $formatNominal($r['masuk_kasus_khusus'] ?? 0) }}</td>
                <td class="text-end">{{ $formatNominal($r['keluar_tunai'] ?? 0) }}</td>
                <td class="text-end">{{ $formatNominal($r['keluar_non_tunai'] ?? 0) }}</td>
                <td class="text-end">{{ $formatNominal($r['net_tunai_disetor'] ?? 0) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9">Tidak ada data pada periode ini.</td>
            </tr>
        @endforelse
    </tbody>
    @if(!empty($rows))
        <tfoot>
            <tr class="total-row">
                <td colspan="2">TOTAL</td>
                <td class="text-end">{{ $formatNominal($totalsAdmin['total_pembayaran_operasional']) }}</td>
                <td class="text-end">{{ $formatNominal($totalsAdmin['masuk_klinik_reguler']) }}</td>
                <td class="text-end">{{ $formatNominal($totalsAdmin['other_income_report_total']) }}</td>
                <td class="text-end">{{ $formatNominal($totalsAdmin['masuk_kasus_khusus']) }}</td>
                <td class="text-end">{{ $formatNominal($totalsAdmin['keluar_tunai']) }}</td>
                <td class="text-end">{{ $formatNominal($totalsAdmin['keluar_non_tunai']) }}</td>
                <td class="text-end">{{ $formatNominal($totalsAdmin['net_tunai_disetor']) }}</td>
            </tr>
        </tfoot>
    @endif
</table>

@if($isOwner)
    <div class="section-title">Ringkasan Total Klinik</div>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th class="text-end">Pendapatan Klinik</th>
                <th class="text-end">Pemasukan Non-Pasien</th>
                <th class="text-end">Masuk Private Owner</th>
                <th class="text-end">Total Pendapatan Klinik</th>
                <th class="text-end">Keluar Operasional Klinik</th>
                <th class="text-end">Keluar Private Owner</th>
                <th class="text-end">Total Pengeluaran Klinik</th>
                <th class="text-end">Net Pendapatan Klinik</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $formatTanggal($r['date'] ?? '-') }}</td>
                    <td class="text-end">{{ $formatNominal(($r['masuk_klinik_owner_view'] ?? 0) - ($r['other_income_report_total'] ?? 0)) }}</td>
                    <td class="text-end">{{ $formatNominal($r['other_income_report_total'] ?? 0) }}</td>
                    <td class="text-end">{{ $formatNominal($r['private_owner_income'] ?? 0) }}</td>
                    <td class="text-end">{{ $formatNominal($r['masuk_total_klinik'] ?? 0) }}</td>
                    <td class="text-end">{{ $formatNominal($r['keluar_klinik'] ?? 0) }}</td>
                    <td class="text-end">{{ $formatNominal($r['private_owner_expense'] ?? 0) }}</td>
                    <td class="text-end">{{ $formatNominal($r['keluar_total_klinik'] ?? 0) }}</td>
                    <td class="text-end">{{ $formatNominal($r['net_kas_klinik'] ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Tidak ada data total klinik pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if(!empty($rows))
            <tfoot>
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td class="text-end">{{ $formatNominal($totalsOwner['masuk_klinik_owner_view'] - $totalsAdmin['other_income_report_total']) }}</td>
                    <td class="text-end">{{ $formatNominal($totalsAdmin['other_income_report_total']) }}</td>
                    <td class="text-end">{{ $formatNominal($totalsOwner['private_owner_income']) }}</td>
                    <td class="text-end">{{ $formatNominal($grandTotalIncome) }}</td>
                    <td class="text-end">{{ $formatNominal($totalsOwner['keluar_total_owner'] - $totalsOwner['owner_mutation_expense'] - $totalsOwner['private_owner_expense']) }}</td>
                    <td class="text-end">{{ $formatNominal($totalsOwner['private_owner_expense']) }}</td>
                    <td class="text-end">{{ $formatNominal($grandTotalExpense) }}</td>
                    <td class="text-end">{{ $formatNominal($netClinicCashflow) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="section-title">Ringkasan Private Owner</div>
    <table>
        <thead>
            <tr>
                <th class="text-end">Total Pemasukan Private</th>
                <th class="text-end">Total Pengeluaran Private</th>
                <th class="text-end">Net Private Owner</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-end">{{ $formatNominal($privateOwnerSummary['income_total'] ?? 0) }}</td>
                <td class="text-end">{{ $formatNominal($privateOwnerSummary['expense_total'] ?? 0) }}</td>
                <td class="text-end">{{ $formatNominal($privateOwnerSummary['net_total'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>
@endif

</body>
</html>