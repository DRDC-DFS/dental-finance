<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export PDF Fee Dokter</title>
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
            margin-bottom: 8px;
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

        .total-row {
            font-weight: bold;
            background: #f9fafb;
        }
    </style>
</head>
<body>
@php
    $today = now()->toDateString();

    $rupiah = function ($value) {
        return number_format((float) $value, 0, ',', '.');
    };

    $angka = function ($value) {
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

    $periodStart = $start ?? $today;
    $periodEnd = $end ?? $today;
    $periodeLabel = $periodStart === $periodEnd
        ? $formatTanggal($periodStart)
        : $formatTanggal($periodStart) . ' s/d ' . $formatTanggal($periodEnd);

    $totalQty   = 0;
    $totalTrx   = 0;
    $totalGross = 0;
    $totalFee   = 0;
    $totalNet   = 0;
@endphp

<div class="title">Laporan Fee Dokter per Tindakan</div>
<div class="subtitle">Periode: {{ $periodeLabel }}</div>
<div class="subtitle">Diexport pada: {{ $formatTanggal(($exportedAt ?? now())->format('Y-m-d')) }} {{ ($exportedAt ?? now())->format('H:i:s') }}</div>

<div class="section-title">Ringkasan Fee Dokter</div>
<table>
    <thead>
        <tr>
            <th>Dokter</th>
            <th>Tipe</th>
            <th>Tindakan</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Jml Transaksi</th>
            <th class="text-end">Gross</th>
            <th class="text-end">Fee Dokter</th>
            <th class="text-end">Net Klinik</th>
        </tr>
    </thead>
    <tbody>
        @forelse(($rows ?? []) as $r)
            @php
                $qty   = (float)($r['qty_total'] ?? 0);
                $trx   = (int)($r['trx_count'] ?? 0);
                $gross = (float)($r['gross_total'] ?? 0);
                $fee   = (float)($r['fee_total'] ?? 0);
                $net   = (float)($r['net_klinik'] ?? 0);

                $totalQty += $qty;
                $totalTrx += $trx;
                $totalGross += $gross;
                $totalFee += $fee;
                $totalNet += $net;
            @endphp
            <tr>
                <td>{{ $r['doctor_name'] ?? '-' }}</td>
                <td>{{ $r['doctor_type'] ?? '-' }}</td>
                <td>{{ $r['treatment_name'] ?? '-' }}</td>
                <td class="text-end">{{ $angka($qty) }}</td>
                <td class="text-end">{{ $angka($trx) }}</td>
                <td class="text-end">{{ $rupiah($gross) }}</td>
                <td class="text-end">{{ $rupiah($fee) }}</td>
                <td class="text-end">{{ $rupiah($net) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8">Tidak ada data pada periode ini.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="3">TOTAL</td>
            <td class="text-end">{{ $angka($totalQty) }}</td>
            <td class="text-end">{{ $angka($totalTrx) }}</td>
            <td class="text-end">{{ $rupiah($totalGross) }}</td>
            <td class="text-end">{{ $rupiah($totalFee) }}</td>
            <td class="text-end">{{ $rupiah($totalNet) }}</td>
        </tr>
    </tfoot>
</table>

</body>
</html>