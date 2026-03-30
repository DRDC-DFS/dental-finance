@extends('layouts.app')

@section('content')
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

    $status = strtolower((string) ($trx->status ?? 'draft'));
    $statusBg = '#e5e7eb';
    $statusTx = '#111827';

    if ($status === 'paid') {
        $statusBg = '#dcfce7';
        $statusTx = '#166534';
    } elseif (in_array($status, ['cancelled', 'void'], true)) {
        $statusBg = '#fee2e2';
        $statusTx = '#991b1b';
    }

    $payerType = strtolower((string) ($trx->payer_type ?? 'umum'));
    $payerLabel = $payerType === 'bpjs' ? 'BPJS' : 'UMUM';
    $payerBg = $payerType === 'bpjs' ? '#eff6ff' : '#f3f4f6';
    $payerTx = $payerType === 'bpjs' ? '#1d4ed8' : '#111827';

    $logoUrl = null;
    if (!empty($setting?->logo_path)) {
        $logoUrl = asset('storage/' . $setting->logo_path);
    }

    $totalItems = $trx->items->sum(function ($item) {
        return (float) ($item->subtotal ?? 0);
    });

    $totalPaid = collect($payments ?? [])->sum(function ($payment) {
        return (float) ($payment->amount ?? 0);
    });

    $remaining = max(0, (float) $trx->bill_total - (float) $totalPaid);
@endphp

<div class="max-w-5xl mx-auto px-6 py-6 invoice-page-shell">

    <style>
        .invoice-wrap{
            background:#fff;
            border:1px solid #e5e7eb;
            border-radius:16px;
            box-shadow:0 10px 30px rgba(0,0,0,.08);
            overflow:hidden;
        }
        .invoice-head{
            padding:24px 28px;
            border-bottom:1px solid #e5e7eb;
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:20px;
            flex-wrap:wrap;
        }
        .invoice-body{
            padding:24px 28px;
        }
        .invoice-top-actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-bottom:16px;
        }
        .btnx{
            display:inline-block;
            padding:10px 16px;
            border-radius:10px;
            font-weight:800;
            text-decoration:none;
            border:none;
            cursor:pointer;
        }
        .btnx-primary{
            background:#2563eb;
            color:#fff;
            box-shadow:0 6px 14px rgba(37,99,235,.25);
        }
        .btnx-secondary{
            background:#e5e7eb;
            color:#111827;
        }
        .btnx-outline{
            background:#fff;
            color:#111827;
            border:1px solid #d1d5db;
        }
        .label-muted{
            font-size:12px;
            color:#6b7280;
            font-weight:700;
            letter-spacing:.03em;
            text-transform:uppercase;
        }
        .value-strong{
            font-size:15px;
            color:#111827;
            font-weight:800;
        }
        .badge{
            display:inline-block;
            padding:4px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:900;
        }
        .grid-info{
            display:grid;
            grid-template-columns:1fr;
            gap:16px;
            margin-top:18px;
        }
        @media(min-width:768px){
            .grid-info{
                grid-template-columns:1fr 1fr;
            }
        }
        .info-card{
            border:1px solid #e5e7eb;
            border-radius:12px;
            padding:16px;
            background:#fafafa;
        }
        .info-card .row{
            margin-bottom:10px;
        }
        .info-card .row:last-child{
            margin-bottom:0;
        }
        .summary-grid{
            display:grid;
            grid-template-columns:1fr;
            gap:14px;
            margin:22px 0;
        }
        @media(min-width:768px){
            .summary-grid{
                grid-template-columns:repeat(4,1fr);
            }
        }
        .summary-box{
            border:1px solid #e5e7eb;
            border-radius:12px;
            padding:14px 16px;
            background:#fff;
        }
        .summary-box .title{
            font-size:12px;
            color:#6b7280;
            font-weight:700;
            margin-bottom:6px;
            text-transform:uppercase;
        }
        .summary-box .value{
            font-size:24px;
            font-weight:900;
            color:#111827;
            line-height:1.15;
        }
        .summary-box.success{
            background:#f0fdf4;
            border-color:#bbf7d0;
        }
        .summary-box.success .value{
            color:#166534;
        }
        .summary-box.warning{
            background:#fff7ed;
            border-color:#fdba74;
        }
        .summary-box.warning .value{
            color:#9a3412;
        }
        .table-wrap{
            overflow-x:auto;
            border:1px solid #e5e7eb;
            border-radius:12px;
        }
        .tablex{
            width:100%;
            border-collapse:collapse;
            font-size:14px;
        }
        .tablex th{
            background:#f8fafc;
            border-bottom:1px solid #e5e7eb;
            text-align:left;
            padding:14px 16px;
        }
        .tablex td{
            border-bottom:1px solid #e5e7eb;
            padding:14px 16px;
            vertical-align:top;
        }
        .right{
            text-align:right;
        }
        .section-title{
            font-size:18px;
            font-weight:900;
            color:#111827;
            margin:0 0 12px 0;
        }
        .verify-box{
            margin-top:22px;
            background:#eff6ff;
            border:1px solid #bfdbfe;
            color:#1e3a8a;
            border-radius:12px;
            padding:16px;
        }
        .verify-link-text{
            overflow-wrap:anywhere;
            word-break:break-word;
        }
        .doctor-sign-box{
            margin-top:22px;
            border:1px solid #e5e7eb;
            border-radius:14px;
            background:#ffffff;
            padding:18px;
        }
        .doctor-sign-grid{
            display:grid;
            grid-template-columns:1fr;
            gap:18px;
            align-items:center;
        }
        @media(min-width:768px){
            .doctor-sign-grid{
                grid-template-columns:1.2fr .8fr;
            }
        }
        .doctor-sign-title{
            font-size:16px;
            font-weight:900;
            color:#111827;
            margin-bottom:8px;
        }
        .doctor-sign-sub{
            color:#6b7280;
            font-size:13px;
            margin-bottom:10px;
        }
        .doctor-sign-name{
            font-size:18px;
            font-weight:900;
            color:#111827;
        }
        .doctor-sign-date{
            margin-top:8px;
            font-size:13px;
            color:#4b5563;
        }
        .doctor-sign-qr{
            text-align:center;
        }
        .doctor-sign-qr .qr-wrap{
            display:inline-flex;
            justify-content:center;
            align-items:center;
            padding:12px;
            border:1px solid #d1d5db;
            border-radius:14px;
            background:#fff;
            min-width:194px;
            min-height:194px;
        }
        .doctor-sign-qr .qr-wrap svg{
            width:170px;
            height:170px;
            display:block;
        }
        .doctor-sign-qr-note{
            margin-top:10px;
            font-size:12px;
            color:#6b7280;
            font-weight:700;
        }
        .footer-note{
            margin-top:18px;
            color:#6b7280;
            font-size:13px;
        }

        @media print{
            @page{
                size:A4 portrait;
                margin:12mm;
            }

            html, body{
                background:#ffffff !important;
            }

            body *{
                visibility:hidden;
            }

            .invoice-page-shell,
            .invoice-page-shell *{
                visibility:visible;
            }

            .invoice-page-shell{
                max-width:none !important;
                width:100% !important;
                margin:0 !important;
                padding:0 !important;
            }

            .invoice-top-actions{
                display:none !important;
            }

            .invoice-wrap{
                border:none !important;
                border-radius:0 !important;
                box-shadow:none !important;
                overflow:visible !important;
            }

            .invoice-head{
                padding:0 0 14px 0 !important;
            }

            .invoice-body{
                padding:14px 0 0 0 !important;
            }

            .table-wrap{
                overflow:visible !important;
            }

            .tablex{
                font-size:12px !important;
            }

            .tablex th,
            .tablex td{
                padding:8px 10px !important;
            }

            .summary-box .value{
                font-size:20px !important;
            }

            .doctor-sign-box,
            .verify-box,
            .info-card,
            .summary-box,
            .table-wrap{
                break-inside:avoid;
                page-break-inside:avoid;
            }

            a{
                color:inherit !important;
                text-decoration:none !important;
            }
        }
    </style>

    <div class="invoice-top-actions">
        <a href="{{ route('income.edit', $trx->id) }}" class="btnx btnx-secondary">← Kembali ke Transaksi</a>
        <a href="{{ route('income.invoice.pdf', $trx->id) }}" class="btnx btnx-primary">Download PDF</a>
        <a href="{{ $verifyUrl }}" target="_blank" class="btnx btnx-outline">Cek Verifikasi</a>
        <button type="button" onclick="window.print()" class="btnx btnx-outline">Print</button>
    </div>

    <div class="invoice-wrap">
        <div class="invoice-head">
            <div style="display:flex;gap:16px;align-items:flex-start;">
                @if($logoUrl)
                    <div>
                        <img src="{{ $logoUrl }}" alt="Logo Klinik" style="max-height:72px;max-width:120px;object-fit:contain;">
                    </div>
                @endif

                <div>
                    <div style="font-size:28px;font-weight:900;color:#111827;line-height:1.1;">INVOICE</div>
                    <div style="color:#6b7280;margin-top:6px;">Dokumen transaksi klinik</div>

                    <div style="margin-top:12px;">
                        <span class="badge" style="background:{{ $statusBg }};color:{{ $statusTx }};">
                            {{ strtoupper($trx->status ?? 'draft') }}
                        </span>

                        <span class="badge" style="background:{{ $payerBg }};color:{{ $payerTx }};margin-left:8px;">
                            {{ $payerLabel }}
                        </span>
                    </div>
                </div>
            </div>

            <div style="min-width:240px;">
                <div class="label-muted">Nomor Invoice</div>
                <div class="value-strong">{{ $trx->invoice_number ?? '-' }}</div>

                <div style="height:12px;"></div>

                <div class="label-muted">Tanggal Transaksi</div>
                <div class="value-strong">{{ $formatTanggal($trx->trx_date) }}</div>

                <div style="height:12px;"></div>

                <div class="label-muted">Kode Verifikasi</div>
                <div class="value-strong">{{ $verifyCode }}</div>
            </div>
        </div>

        <div class="invoice-body">

            <div class="grid-info">
                <div class="info-card">
                    <div class="row">
                        <div class="label-muted">Pasien</div>
                        <div class="value-strong">{{ $trx->patient?->name ?? '-' }}</div>
                    </div>

                    <div class="row">
                        <div class="label-muted">No. HP</div>
                        <div class="value-strong">{{ $trx->patient?->phone ?? '-' }}</div>
                    </div>

                    <div class="row">
                        <div class="label-muted">Catatan Transaksi</div>
                        <div class="value-strong">{{ $trx->notes ?: '-' }}</div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="row">
                        <div class="label-muted">Dokter Tindakan</div>
                        <div class="value-strong">{{ $trx->doctor?->name ?? '-' }}</div>
                    </div>

                    <div class="row">
                        <div class="label-muted">Mode Ortho</div>
                        <div class="value-strong">{{ strtoupper((string) ($trx->ortho_case_mode ?? 'none')) }}</div>
                    </div>

                    <div class="row">
                        <div class="label-muted">Visibility</div>
                        <div class="value-strong">{{ strtoupper((string) ($trx->visibility ?? 'public')) }}</div>
                    </div>
                </div>
            </div>

            <div class="summary-grid">
                <div class="summary-box">
                    <div class="title">Total Tagihan</div>
                    <div class="value">{{ format_rupiah($trx->bill_total) }}</div>
                </div>

                <div class="summary-box success">
                    <div class="title">Total Dibayar</div>
                    <div class="value">{{ format_rupiah($totalPaid) }}</div>
                </div>

                <div class="summary-box warning">
                    <div class="title">Sisa Tagihan</div>
                    <div class="value">{{ format_rupiah($remaining) }}</div>
                </div>

                <div class="summary-box">
                    <div class="title">Jumlah Tindakan</div>
                    <div class="value">{{ $trx->items->count() }}</div>
                </div>
            </div>

            <div style="margin-top:10px;">
                <h2 class="section-title">Detail Tindakan</h2>

                <div class="table-wrap">
                    <table class="tablex">
                        <thead>
                            <tr>
                                <th>Tindakan</th>
                                <th class="right" style="width:120px;">Qty</th>
                                <th class="right" style="width:180px;">Harga</th>
                                <th class="right" style="width:180px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trx->items as $item)
                                <tr>
                                    <td>
                                        <div style="font-weight:800;color:#111827;">
                                            {{ $item->treatment?->name ?? '-' }}
                                        </div>
                                        <div style="font-size:12px;color:#6b7280;margin-top:4px;">
                                            Mode harga:
                                            {{ strtoupper((string) ($item->treatment?->price_mode ?? 'fixed')) }}
                                        </div>
                                    </td>
                                    <td class="right">{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                                    <td class="right">{{ format_rupiah($item->unit_price) }}</td>
                                    <td class="right" style="font-weight:900;">{{ format_rupiah($item->subtotal) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align:center;color:#6b7280;">
                                        Belum ada item tindakan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="right" style="font-weight:900;">TOTAL</td>
                                <td class="right" style="font-weight:900;">{{ format_rupiah($totalItems) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div style="margin-top:22px;">
                <h2 class="section-title">Riwayat Pembayaran</h2>

                <div class="table-wrap">
                    <table class="tablex">
                        <thead>
                            <tr>
                                <th style="width:160px;">Tanggal</th>
                                <th>Metode</th>
                                <th style="width:140px;">Channel</th>
                                <th class="right" style="width:180px;">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td>{{ $formatTanggal($payment->pay_date ?? null) }}</td>
                                    <td>{{ $payment->method_name ?? '-' }}</td>
                                    <td>{{ strtoupper((string) ($payment->channel ?? '-')) }}</td>
                                    <td class="right" style="font-weight:900;">{{ format_rupiah($payment->amount ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align:center;color:#6b7280;">
                                        Belum ada pembayaran tersimpan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="right" style="font-weight:900;">TOTAL DIBAYAR</td>
                                <td class="right" style="font-weight:900;">{{ format_rupiah($totalPaid) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="verify-box">
                <div style="font-weight:900;font-size:16px;margin-bottom:6px;">Verifikasi Invoice</div>
                <div>Gunakan kode verifikasi ini untuk mengecek keaslian invoice:</div>
                <div style="font-size:22px;font-weight:900;margin-top:8px;">{{ $verifyCode }}</div>
                <div style="margin-top:10px;" class="verify-link-text">
                    Link verifikasi:
                    <a href="{{ $verifyUrl }}" target="_blank" style="font-weight:800;color:#1d4ed8;text-decoration:underline;">
                        {{ $verifyUrl }}
                    </a>
                </div>
            </div>

            <div class="doctor-sign-box">
                <div class="doctor-sign-grid">
                    <div>
                        <div class="doctor-sign-title">Tanda Tangan Dokter</div>
                        <div class="doctor-sign-sub">Dokumen ini telah diverifikasi secara digital oleh dokter penanggung jawab.</div>
                        <div class="doctor-sign-name">{{ $signatureDoctor ?? 'drg. Desly A.C. Luhulima, M.K.M' }}</div>
                        <div class="doctor-sign-date">Gorontalo, {{ $formatTanggal($trx->trx_date) }}</div>
                    </div>

                    <div class="doctor-sign-qr">
                        <div class="qr-wrap">
                            @if(!empty($qrSvg))
                                {!! $qrSvg !!}
                            @endif
                        </div>
                        <div class="doctor-sign-qr-note">Scan untuk verifikasi invoice</div>
                    </div>
                </div>
            </div>

            <div class="footer-note">
                Dokumen ini dihasilkan dari sistem Dental Finance System (DFS) dan hanya menampilkan data transaksi yang sudah tersimpan di sistem.
            </div>
        </div>
    </div>
</div>
@endsection