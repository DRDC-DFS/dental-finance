<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kwitansi Pembayaran</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }

        .header { width: 100%; margin-bottom: 10px; }
        .brand { display: flex; align-items: center; gap: 10px; }
        .logo { width: 52px; height: 52px; object-fit: contain; }

        .clinic { font-size: 14px; font-weight: 700; text-transform: uppercase; line-height: 1.2; }
        .owner  { font-size: 12px; font-weight: 600; margin-top: 2px; line-height: 1.2; }
        .small  { font-size: 11px; color: #444; }

        .title { text-align: center; font-size: 14px; font-weight: 800; margin: 10px 0 12px; letter-spacing: 0.4px; }

        .meta { width: 100%; margin-bottom: 10px; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .meta .label { width: 90px; color: #333; }

        .items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .items th, .items td { border: 1px solid #ddd; padding: 6px; }
        .items th { background: #f3f6ff; text-align: left; }

        .right { text-align: right; }

        .totalWrap { margin-top: 10px; width: 100%; }
        .totalRow { display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; border: 1px solid #111; }
        .totalRow .label { font-weight: 800; font-size: 13px; }
        .totalRow .value { font-weight: 900; font-size: 13px; }

        .thanks { margin-top: 12px; text-align: left; }

        .sign { margin-top: 18px; width: 100%; }
        .signBox { width: 45%; text-align: center; float: right; }

        /* Jarak tanda tangan (2 baris) */
        .signSpace { height: 32px; }

        /* Garis DI BAWAH nama (bukan di atas) */
        .signName {
            display: inline-block;
            padding-bottom: 4px;
            border-bottom: 1px solid #111;
            font-weight: 600;
        }

        .ttdImg { width: 140px; height: auto; margin-top: 6px; }

        .clearfix:after { content:""; display:block; clear:both; }
    </style>
</head>
<body>

    <div class="header">
        <div class="brand">
            @if(!empty($logoBase64))
                <img class="logo" src="{{ $logoBase64 }}" alt="Logo">
            @endif
            <div>
                <div class="clinic">{{ $clinicName ?? 'DR DENTAL CARE' }}</div>
                <div class="owner">{{ $ownerName ?? '' }}</div>
                <div class="small">KWITANSI PEMBAYARAN</div>
            </div>
        </div>
    </div>

    <div class="title">KWITANSI PEMBAYARAN</div>

    <table class="meta">
        <tr>
            <td class="label">Pasien</td>
            <td>: {{ $trx->patient_name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal</td>
            <td>: {{ \Carbon\Carbon::parse($trx->trx_date)->format('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Dokter</td>
            <td>: {{ $trx->doctor_name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">No. Kwitansi</td>
            <td>: {{ $trx->invoice_code ?? $trx->id }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 50%;">Tindakan</th>
                <th style="width: 10%;" class="right">Qty</th>
                <th style="width: 20%;" class="right">Harga</th>
                <th style="width: 20%;" class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $it)
                <tr>
                    <td>{{ $it->item_name ?? '-' }}</td>
                    <td class="right">{{ (int)($it->qty ?? 0) }}</td>
                    <td class="right">Rp {{ number_format((float)($it->price ?? 0), 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format((float)($it->subtotal ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="small">Tidak ada item tindakan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totalWrap">
        <div class="totalRow">
            <div class="label">TOTAL</div>
            <div class="value">Rp {{ number_format((float)($trx->pay_total ?? 0), 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="thanks">
        Terima kasih 🙏
    </div>

    <div class="sign clearfix">
        <div class="signBox">
            <div class="small">Hormat kami,</div>

            <div class="signSpace"></div>

            @if(!empty($ttdBase64))
                <img class="ttdImg" src="{{ $ttdBase64 }}" alt="TTD">
            @endif

            <div class="signName">{{ $ownerName ?? '' }}</div>
        </div>
    </div>

</body>
</html>