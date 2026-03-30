<!DOCTYPE html>

<html lang="id">
<head>
<meta charset="UTF-8">
<title>Verifikasi Invoice</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>

body{
    font-family: Arial, Helvetica, sans-serif;
    background:#f1f5f9;
    margin:0;
}

.wrap{
    max-width:700px;
    margin:auto;
    padding:30px 20px;
}

.card{
    background:#fff;
    border-radius:14px;
    border:1px solid #e5e7eb;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    padding:26px;
}

.title{
    font-size:28px;
    font-weight:900;
    margin-bottom:6px;
}

.subtitle{
    color:#6b7280;
    font-size:14px;
    margin-bottom:20px;
}

.status{
    padding:6px 12px;
    border-radius:999px;
    font-weight:900;
    font-size:12px;
    display:inline-block;
}

.status-valid{
    background:#dcfce7;
    color:#166534;
}

.status-invalid{
    background:#fee2e2;
    color:#991b1b;
}

.grid{
    display:grid;
    grid-template-columns:1fr;
    gap:14px;
    margin-top:16px;
}

@media(min-width:600px){
    .grid{
        grid-template-columns:1fr 1fr;
    }
}

.box{
    border:1px solid #e5e7eb;
    border-radius:10px;
    padding:14px;
}

.label{
    font-size:12px;
    color:#6b7280;
    font-weight:700;
    text-transform:uppercase;
}

.value{
    font-size:16px;
    font-weight:900;
    margin-top:4px;
}

.total{
    margin-top:20px;
    border-top:1px solid #e5e7eb;
    padding-top:16px;
}

.total-row{
    display:flex;
    justify-content:space-between;
    margin-bottom:6px;
}

.total-big{
    font-size:24px;
    font-weight:900;
}

.footer{
    margin-top:18px;
    font-size:12px;
    color:#6b7280;
}

.invalid-box{
    margin-top:16px;
    padding:16px;
    border:1px solid #fecaca;
    background:#fef2f2;
    border-radius:10px;
}

</style>

</head>

<body>

@php

$trx = $incomeTransaction;

$status = strtolower((string) ($trx->status ?? 'draft'));

$totalPaid = collect($payments ?? [])->sum(function($p){
return (float)($p->amount ?? 0);
});

$remaining = max(0, (float)$trx->bill_total - (float)$totalPaid);

$formatTanggal = function ($value) {
if (!$value) return "-";
try{
return \Carbon\Carbon::parse($value)->format('d-m-Y');
}catch(\Throwable $e){
return (string)$value;
}
};

@endphp

<div class="wrap">

<div class="card">

<div class="title">Verifikasi Invoice</div>
<div class="subtitle">
Sistem Dental Finance System (DFS)
</div>

@if($isValid)

<span class="status status-valid">
INVOICE VALID
</span>

<div class="grid">

<div class="box">
<div class="label">Nomor Invoice</div>
<div class="value">{{ $trx->invoice_number }}</div>
</div>

<div class="box">
<div class="label">Tanggal</div>
<div class="value">{{ $formatTanggal($trx->trx_date) }}</div>
</div>

<div class="box">
<div class="label">Pasien</div>
<div class="value">{{ $trx->patient?->name ?? '-' }}</div>
</div>

<div class="box">
<div class="label">Dokter</div>
<div class="value">{{ $trx->doctor?->name ?? '-' }}</div>
</div>

</div>

<div class="total">

<div class="total-row">
<div>Total Tagihan</div>
<div>{{ format_rupiah($trx->bill_total) }}</div>
</div>

<div class="total-row">
<div>Total Dibayar</div>
<div>{{ format_rupiah($totalPaid) }}</div>
</div>

<div class="total-row total-big">
<div>Sisa Tagihan</div>
<div>{{ format_rupiah($remaining) }}</div>
</div>

</div>

<div class="footer">
Invoice ini telah diverifikasi oleh sistem klinik.<br>
Jika ada pertanyaan silakan hubungi klinik terkait.
</div>

@else

<span class="status status-invalid">
INVOICE TIDAK VALID
</span>

<div class="invalid-box">
Kode verifikasi invoice tidak cocok.<br><br>
Invoice ini tidak dapat diverifikasi oleh sistem.
</div>

@endif

</div>
</div>

</body>
</html>
