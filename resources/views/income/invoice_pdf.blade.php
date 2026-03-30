<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $incomeTransaction->invoice_number }}</title>
    <style>
        body{
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color:#111827;
            margin: 0;
            padding: 0;
        }
        .page{
            padding: 16px 18px;
        }
        .header{
            width: 100%;
            border-bottom: 1.5px solid #111827;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header-top{
            text-align:center;
            margin-bottom: 8px;
        }
        .logo{
            max-height: 70px;
            max-width: 190px;
            margin: 0 auto 4px auto;
            display: block;
        }
        .clinic-address{
            margin-top: 2px;
            font-size: 9px;
            line-height: 1.25;
            color:#111827;
            text-align: center;
        }
        .header-table{
            width:100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .header-table td{
            vertical-align: top;
            padding: 0;
        }
        .title{
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            line-height: 1.1;
        }
        .meta-head-right{
            text-align: right;
        }
        .label{
            font-size: 8px;
            color:#6b7280;
            text-transform: uppercase;
            margin-bottom: 1px;
            line-height: 1.1;
        }
        .value{
            font-size: 10px;
            font-weight: bold;
            color:#111827;
            line-height: 1.15;
        }
        .meta-table{
            width:100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 10px;
        }
        .meta-table td{
            vertical-align: top;
            width: 50%;
            padding: 5px 6px;
            border:1px solid #d1d5db;
        }
        .section-title{
            font-size: 11px;
            font-weight: bold;
            margin: 10px 0 4px 0;
            line-height: 1.1;
        }
        .table{
            width:100%;
            border-collapse: collapse;
            margin-top: 3px;
        }
        .table th{
            background:#f3f4f6;
            border:1px solid #d1d5db;
            padding:4px 6px;
            text-align:left;
            font-size:9px;
            line-height: 1.15;
        }
        .table td{
            border:1px solid #d1d5db;
            padding:4px 6px;
            font-size:9px;
            vertical-align: top;
            line-height: 1.15;
        }
        .right{
            text-align:right;
        }
        .summary{
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .summary td{
            padding:5px 6px;
            border:1px solid #d1d5db;
            font-size: 9px;
            line-height: 1.15;
        }
        .summary .label-cell{
            background:#f9fafb;
            font-weight:bold;
            width:70%;
        }
        .summary .value-cell{
            text-align:right;
            font-weight:bold;
            width:30%;
        }
        .verify-box{
            margin-top: 10px;
            border:1px solid #93c5fd;
            background:#eff6ff;
            padding:7px 8px;
        }
        .verify-title{
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 3px;
            color:#1e3a8a;
            line-height: 1.1;
        }
        .verify-code{
            font-size: 12px;
            font-weight: bold;
            color:#1e3a8a;
            margin: 3px 0;
            line-height: 1.1;
        }
        .verify-link{
            font-size:8px;
            color:#1e3a8a;
            line-height: 1.2;
            word-break: break-all;
        }
        .signature-block{
            margin-top: 12px;
            width: 100%;
        }
        .signature-table{
            width:100%;
            border-collapse: collapse;
        }
        .signature-table td{
            vertical-align: middle;
        }
        .signature-left{
            width: 62%;
            font-size: 9px;
            color:#111827;
            line-height: 1.25;
        }
        .signature-title{
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .signature-subtitle{
            color:#4b5563;
            margin-bottom: 6px;
        }
        .signature-name{
            font-weight: bold;
            font-size: 10px;
            margin-top: 4px;
        }
        .signature-date{
            margin-top: 4px;
            color:#4b5563;
        }
        .signature-right{
            width: 38%;
            text-align: center;
        }
        .qr-box{
            display: inline-block;
            border:1px solid #d1d5db;
            background:#ffffff;
            padding:8px;
            min-width:121px;
            min-height:121px;
        }
        .qr-box img{
            width: 105px;
            height: 105px;
            display:block;
            object-fit:contain;
        }
        .qr-note{
            margin-top: 4px;
            font-size: 8px;
            color:#6b7280;
            line-height: 1.15;
        }
        .footer{
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #d1d5db;
            font-size: 9px;
            color:#374151;
            text-align: center;
            line-height: 1.2;
        }
        .muted-mini{
            font-size:8px;
            color:#6b7280;
            margin-top:2px;
            line-height: 1.1;
        }
        .no-break{
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
@php
    $trx = $incomeTransaction;

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

    $status = strtoupper((string) ($trx->status ?? 'DRAFT'));
    $payer = strtoupper((string) ($trx->payer_type ?? 'UMUM'));

    $totalItems = $trx->items->sum(function ($item) {
        return (float) ($item->subtotal ?? 0);
    });

    $totalPaid = collect($payments ?? [])->sum(function ($payment) {
        return (float) ($payment->amount ?? 0);
    });

    $remaining = max(0, (float) $trx->bill_total - (float) $totalPaid);

    $logoPath = null;
    if (!empty($setting?->logo_path)) {
        $candidate = public_path('storage/' . $setting->logo_path);
        if (file_exists($candidate)) {
            $logoPath = $candidate;
        }
    }
@endphp

<div class="page">
    <div class="header">
        <div class="header-top">
            @if($logoPath)
                <img src="{{ $logoPath }}" class="logo" alt="Logo Klinik">
            @endif

            <div class="clinic-address">
                Jl. Prof. Dr. H.B. Jassin No.436, Dulalowo<br>
                Kec. Kota Tengah, Kota Gorontalo<br>
                Gorontalo 96128<br>
                Telp / WA : 0811-4320-512
            </div>
        </div>

        <table class="header-table">
            <tr>
                <td style="width: 40%;">
                    <div class="title">INVOICE</div>
                </td>

                <td style="width: 60%;" class="meta-head-right">
                    <div class="label">Nomor Invoice</div>
                    <div class="value">{{ $trx->invoice_number ?? '-' }}</div>

                    <div style="height:4px;"></div>

                    <div class="label">Tanggal Transaksi</div>
                    <div class="value">{{ $formatTanggal($trx->trx_date) }}</div>

                    <div style="height:4px;"></div>

                    <div class="label">Status</div>
                    <div class="value">{{ $status }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td>
                <div class="label">Nama Pasien</div>
                <div class="value">{{ $trx->patient?->name ?? '-' }}</div>
            </td>
            <td>
                <div class="label">Dokter Tindakan</div>
                <div class="value">{{ $trx->doctor?->name ?? '-' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">No HP Pasien</div>
                <div class="value">{{ $trx->patient?->phone ?? '-' }}</div>
            </td>
            <td>
                <div class="label">Kategori Pasien</div>
                <div class="value">{{ $payer }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Detail Tindakan</div>
    <table class="table">
        <thead>
            <tr>
                <th>Tindakan</th>
                <th class="right" style="width:52px;">Qty</th>
                <th class="right" style="width:92px;">Harga</th>
                <th class="right" style="width:98px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($trx->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->treatment?->name ?? '-' }}</strong>
                        <div class="muted-mini">
                            Mode harga: {{ strtoupper((string) ($item->treatment?->price_mode ?? 'fixed')) }}
                        </div>
                    </td>
                    <td class="right">{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                    <td class="right">{{ format_rupiah($item->unit_price) }}</td>
                    <td class="right">{{ format_rupiah($item->subtotal) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;color:#6b7280;">Belum ada item tindakan.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="right"><strong>TOTAL</strong></td>
                <td class="right"><strong>{{ format_rupiah($totalItems) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <table class="summary no-break">
        <tr>
            <td class="label-cell">TOTAL TAGIHAN</td>
            <td class="value-cell">{{ format_rupiah($trx->bill_total) }}</td>
        </tr>
        <tr>
            <td class="label-cell">TOTAL DIBAYAR</td>
            <td class="value-cell">{{ format_rupiah($totalPaid) }}</td>
        </tr>
        <tr>
            <td class="label-cell">SISA TAGIHAN</td>
            <td class="value-cell">{{ format_rupiah($remaining) }}</td>
        </tr>
    </table>

    <div class="section-title">Riwayat Pembayaran</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width:78px;">Tanggal</th>
                <th>Metode</th>
                <th style="width:60px;">Channel</th>
                <th class="right" style="width:98px;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td>{{ $formatTanggal($payment->pay_date ?? null) }}</td>
                    <td>{{ $payment->method_name ?? '-' }}</td>
                    <td>{{ strtoupper((string) ($payment->channel ?? '-')) }}</td>
                    <td class="right">{{ format_rupiah($payment->amount ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;color:#6b7280;">Belum ada pembayaran tersimpan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="verify-box no-break">
        <div class="verify-title">VERIFIKASI INVOICE</div>
        <div>Kode verifikasi invoice:</div>
        <div class="verify-code">{{ $verifyCode }}</div>
        <div class="verify-link">{{ $verifyUrl }}</div>
    </div>

    <div class="signature-block no-break">
        <table class="signature-table">
            <tr>
                <td class="signature-left">
                    <div class="signature-title">Tanda Tangan Dokter</div>
                    <div class="signature-subtitle">Dokumen ini telah diverifikasi secara digital oleh dokter penanggung jawab.</div>
                    <div class="signature-name">{{ $signatureDoctor ?? 'drg. Desly A.C. Luhulima, M.K.M' }}</div>
                    <div class="signature-date">Gorontalo, {{ $formatTanggal($trx->trx_date) }}</div>
                </td>
                <td class="signature-right">
                    <div class="qr-box">
                        @if(!empty($qrSvgPath) && file_exists($qrSvgPath))
                            <img src="{{ $qrSvgPath }}" alt="QR Verifikasi Invoice">
                        @endif
                    </div>
                    <div class="qr-note">Scan untuk verifikasi invoice</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Terima kasih telah mempercayakan perawatan gigi Anda kepada kami.
    </div>
</div>
</body>
</html>