<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export PDF Laba Rugi</title>
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
            padding: 7px 8px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
        }

        .text-end {
            text-align: right;
        }

        .card {
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            margin-bottom: 10px;
        }

        .label {
            color: #6b7280;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .value {
            font-size: 16px;
            font-weight: bold;
        }
    </style>
</head>
<body>
@php
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

    $rupiah = function ($value) {
        return number_format((float) $value, 0, ',', '.');
    };

    $periodStart = $start ?? now()->toDateString();
    $periodEnd = $end ?? now()->toDateString();
    $periodeLabel = $periodStart === $periodEnd
        ? $formatTanggal($periodStart)
        : $formatTanggal($periodStart) . ' s/d ' . $formatTanggal($periodEnd);
@endphp

<div class="title">Laporan Laba Rugi</div>
<div class="subtitle">Periode: {{ $periodeLabel }}</div>
<div class="subtitle">Diexport pada: {{ $formatTanggal(($exportedAt ?? now())->format('Y-m-d')) }} {{ ($exportedAt ?? now())->format('H:i:s') }}</div>

<div class="section-title">Ringkasan Utama</div>
<table>
    <tr>
        <th>Total Pendapatan Klinik</th>
        <td class="text-end">{{ $rupiah($totalClinicIncome ?? 0) }}</td>
    </tr>
    <tr>
        <th>Total Pengeluaran Klinik</th>
        <td class="text-end">{{ $rupiah($totalExpense ?? 0) }}</td>
    </tr>
    <tr>
        <th>Net Arus Kas Klinik</th>
        <td class="text-end">{{ $rupiah($netClinicCashflow ?? 0) }}</td>
    </tr>
    <tr>
        <th>Pendapatan Kotor Klinik</th>
        <td class="text-end">{{ $rupiah($grossIncome ?? 0) }}</td>
    </tr>
    <tr>
        <th>Fee Dokter</th>
        <td class="text-end">{{ $rupiah($doctorFee ?? 0) }}</td>
    </tr>
    <tr>
        <th>Pendapatan Bersih Klinik</th>
        <td class="text-end">{{ $rupiah($netClinicIncome ?? 0) }}</td>
    </tr>
    <tr>
        <th>Laba Bersih Operasional Klinik</th>
        <td class="text-end">{{ $rupiah($netProfit ?? 0) }}</td>
    </tr>
</table>

<div class="section-title">Breakdown Pendapatan Klinik</div>
<table>
    <tr>
        <th>Pendapatan Reguler Non Owner Finance</th>
        <td class="text-end">{{ $rupiah($grossIncomeRegular ?? 0) }}</td>
    </tr>
    <tr>
        <th>Pendapatan Prosto / Retainer Diakui</th>
        <td class="text-end">{{ $rupiah($recognizedProsthoRetainerIncome ?? 0) }}</td>
    </tr>
    <tr>
        <th>Pendapatan Klinik Dental Laboratory</th>
        <td class="text-end">{{ $rupiah($recognizedDentalLaboratoryIncome ?? 0) }}</td>
    </tr>
    <tr>
        <th>Pendapatan Private Owner</th>
        <td class="text-end">{{ $rupiah($privateOwnerIncome ?? 0) }}</td>
    </tr>
    <tr>
        <th><strong>Total Pendapatan Klinik</strong></th>
        <td class="text-end"><strong>{{ $rupiah($totalClinicIncome ?? 0) }}</strong></td>
    </tr>
</table>

<div class="section-title">Breakdown Pengeluaran Klinik</div>
<table>
    <tr>
        <th>Pengeluaran Operasional Klinik</th>
        <td class="text-end">{{ $rupiah($operationalExpense ?? 0) }}</td>
    </tr>
    <tr>
        <th>Pengeluaran Private Owner</th>
        <td class="text-end">{{ $rupiah($privateOwnerExpense ?? 0) }}</td>
    </tr>
    <tr>
        <th><strong>Total Pengeluaran Klinik</strong></th>
        <td class="text-end"><strong>{{ $rupiah($totalExpense ?? 0) }}</strong></td>
    </tr>
</table>

<div class="section-title">Mutasi Owner</div>
<table>
    <tr>
        <th>Mutasi Owner Masuk</th>
        <td class="text-end">{{ $rupiah($ownerMutationIncome ?? 0) }}</td>
    </tr>
    <tr>
        <th>Mutasi Owner Keluar</th>
        <td class="text-end">{{ $rupiah($ownerMutationExpense ?? 0) }}</td>
    </tr>
    <tr>
        <th>Arus Bersih Mutasi Owner</th>
        <td class="text-end">{{ $rupiah($ownerNetCashflow ?? 0) }}</td>
    </tr>
</table>

</body>
</html>