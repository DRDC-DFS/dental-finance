@extends('layouts.app')

@section('content')
@php
    $role = strtolower((string) (auth()->user()->role ?? ''));
    $isOwner = $role === 'owner';
    $today = now()->toDateString();

    if ($isOwner) {
        $periodStart = $dateStart ?? request('date_start') ?? $today;
        $periodEnd = $dateEnd ?? request('date_end') ?? $today;
    } else {
        $singleDate = $date ?? request('date') ?? $today;
        $periodStart = $singleDate;
        $periodEnd = $singleDate;
    }

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

    $isSingleDayPeriod = $periodStart === $periodEnd;
    $periodeLabel = $isSingleDayPeriod
        ? 'Periode: ' . $formatTanggal($periodStart)
        : 'Periode: ' . $formatTanggal($periodStart) . ' s/d ' . $formatTanggal($periodEnd);
@endphp

<div class="max-w-6xl mx-auto px-6 py-6">

    @if(session('success'))
        <div style="margin-bottom:16px;border-radius:10px;background:#dcfce7;border:1px solid #86efac;padding:12px;color:#166534;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="margin-bottom:16px;border-radius:10px;background:#fee2e2;border:1px solid #fca5a5;padding:12px;color:#991b1b;">
            <div style="font-weight:800;margin-bottom:6px;">Terjadi kesalahan:</div>
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:40px;font-weight:800;margin:0;">Pemasukan</h1>
            <div style="color:#4b5563;margin-top:6px;">Daftar transaksi pasien</div>
            <div style="color:#15803d;font-size:13px;font-weight:800;margin-top:4px;">
                Default tampilan: data hari berjalan
            </div>
            <div style="color:#6b7280;font-size:13px;margin-top:4px;">
                {{ $periodeLabel }}
            </div>
        </div>

        <a href="{{ route('income.create') }}"
           style="background:#2563eb;color:#fff;padding:10px 16px;border-radius:10px;font-weight:800;display:inline-block;text-decoration:none;box-shadow:0 6px 14px rgba(37,99,235,.25);">
            + Buat Transaksi
        </a>
    </div>

    <div style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;box-shadow:0 10px 25px rgba(0,0,0,.08);padding:16px;margin-bottom:16px;">
        <form method="GET" action="{{ route('income.index') }}">
            <div style="display:flex;gap:14px;align-items:end;flex-wrap:wrap;">

                @if($isOwner)
                    <div style="min-width:220px;">
                        <label style="display:block;font-size:13px;font-weight:800;color:#374151;margin-bottom:6px;">
                            Tanggal Mulai
                        </label>
                        <input type="date"
                               name="date_start"
                               value="{{ $periodStart }}"
                               style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:10px 12px;outline:none;">
                    </div>

                    <div style="min-width:220px;">
                        <label style="display:block;font-size:13px;font-weight:800;color:#374151;margin-bottom:6px;">
                            Tanggal Selesai
                        </label>
                        <input type="date"
                               name="date_end"
                               value="{{ $periodEnd }}"
                               style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:10px 12px;outline:none;">
                    </div>
                @else
                    <div style="min-width:220px;">
                        <label style="display:block;font-size:13px;font-weight:800;color:#374151;margin-bottom:6px;">
                            Tanggal
                        </label>
                        <input type="date"
                               name="date"
                               value="{{ $periodStart }}"
                               style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:10px 12px;outline:none;">
                    </div>
                @endif

                <div style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
                    <button type="submit"
                            style="background:#2563eb;color:#fff;padding:10px 16px;border-radius:10px;font-weight:800;border:none;cursor:pointer;box-shadow:0 6px 14px rgba(37,99,235,.25);">
                        Filter
                    </button>

                    @if($isOwner)
                        <a href="{{ route('income.index', ['date_start' => $today, 'date_end' => $today]) }}"
                           style="background:#0f766e;color:#fff;padding:10px 16px;border-radius:10px;font-weight:800;display:inline-block;text-decoration:none;box-shadow:0 6px 14px rgba(15,118,110,.25);">
                            Hari Ini
                        </a>

                        <a href="{{ route('income.index', ['date_start' => $today, 'date_end' => $today]) }}"
                           style="background:#6b7280;color:#fff;padding:10px 16px;border-radius:10px;font-weight:800;display:inline-block;text-decoration:none;box-shadow:0 6px 14px rgba(107,114,128,.25);">
                            Reset
                        </a>
                    @else
                        <a href="{{ route('income.index', ['date' => $today]) }}"
                           style="background:#0f766e;color:#fff;padding:10px 16px;border-radius:10px;font-weight:800;display:inline-block;text-decoration:none;box-shadow:0 6px 14px rgba(15,118,110,.25);">
                            Hari Ini
                        </a>

                        <a href="{{ route('income.index', ['date' => $today]) }}"
                           style="background:#6b7280;color:#fff;padding:10px 16px;border-radius:10px;font-weight:800;display:inline-block;text-decoration:none;box-shadow:0 6px 14px rgba(107,114,128,.25);">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <div style="background:#fff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 10px 25px rgba(0,0,0,.08);">

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:14px;">

                <thead style="background:#f8fafc;border-bottom:1px solid #e5e7eb;">
                    <tr style="text-align:left;">
                        <th style="padding:14px 16px;">Invoice</th>
                        <th style="padding:14px 16px;">Tanggal</th>
                        <th style="padding:14px 16px;">Pasien</th>
                        <th style="padding:14px 16px;">Kategori</th>
                        <th style="padding:14px 16px;">Dokter</th>
                        <th style="padding:14px 16px;">Status</th>
                        <th style="padding:14px 16px;text-align:right;">Total</th>
                        <th style="padding:14px 16px;width:320px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($transactions as $trx)
                        @php
                            $status = strtolower($trx->status ?? 'draft');
                            $isPaid = $status === 'paid';

                            $badgeBg = '#e5e7eb';
                            $badgeTx = '#111827';

                            if ($status === 'paid') { $badgeBg = '#dcfce7'; $badgeTx = '#166534'; }
                            if ($status === 'cancelled' || $status === 'void') { $badgeBg = '#fee2e2'; $badgeTx = '#991b1b'; }

                            $payerType = strtolower((string) ($trx->payer_type ?? 'umum'));
                            $payerLabel = $payerType === 'bpjs' ? 'BPJS' : 'UMUM';
                            $payerBg = $payerType === 'bpjs' ? '#eff6ff' : '#f3f4f6';
                            $payerTx = $payerType === 'bpjs' ? '#1d4ed8' : '#111827';
                        @endphp

                        <tr style="border-bottom:1px solid #e5e7eb;">
                            <td style="padding:14px 16px;font-weight:700;">
                                {{ $trx->invoice_number }}
                            </td>

                            <td style="padding:14px 16px;">
                                {{ \Carbon\Carbon::parse($trx->trx_date)->format('d-m-Y') }}
                            </td>

                            <td style="padding:14px 16px;">
                                {{ $trx->patient?->name ?? '-' }}
                            </td>

                            <td style="padding:14px 16px;">
                                <span style="display:inline-block;background:{{ $payerBg }};color:{{ $payerTx }};padding:4px 10px;border-radius:999px;font-weight:800;">
                                    {{ $payerLabel }}
                                </span>
                            </td>

                            <td style="padding:14px 16px;">
                                {{ $trx->doctor?->name ?? '-' }}
                            </td>

                            <td style="padding:14px 16px;">
                                <span style="display:inline-block;background:{{ $badgeBg }};color:{{ $badgeTx }};padding:4px 10px;border-radius:999px;font-weight:800;">
                                    {{ strtoupper($trx->status) }}
                                </span>
                            </td>

                            <td style="padding:14px 16px;text-align:right;font-weight:900;">
                                {{ format_rupiah($trx->bill_total) }}
                            </td>

                            <td style="padding:14px 16px;">
                                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">

                                    <a href="{{ route('income.edit', $trx->id) }}"
                                       style="background:#2563eb;color:#fff;padding:8px 14px;border-radius:10px;font-weight:900;text-decoration:none;display:inline-block;box-shadow:0 6px 14px rgba(37,99,235,.25);">
                                        Edit
                                    </a>

                                    @if($isPaid)
                                        <a href="{{ route('income.invoice', $trx->id) }}"
                                           style="background:#16a34a;color:#fff;padding:8px 14px;border-radius:10px;font-weight:900;text-decoration:none;display:inline-block;box-shadow:0 6px 14px rgba(22,163,74,.25);">
                                            Invoice
                                        </a>

                                        <a href="{{ route('income.invoice.pdf', $trx->id) }}"
                                           style="background:#7c3aed;color:#fff;padding:8px 14px;border-radius:10px;font-weight:900;text-decoration:none;display:inline-block;box-shadow:0 6px 14px rgba(124,58,237,.25);">
                                            PDF
                                        </a>
                                    @endif

                                    @if($isOwner)
                                        <form method="POST"
                                              action="{{ route('income.destroy', $trx->id) }}"
                                              onsubmit="return confirm('Hapus transaksi ini? Semua item tindakan akan ikut terhapus.')"
                                              style="display:inline;">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    style="background:#dc2626;color:#fff;padding:8px 14px;border-radius:10px;font-weight:900;border:none;cursor:pointer;box-shadow:0 6px 14px rgba(220,38,38,.25);">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:26px 16px;text-align:center;color:#6b7280;">
                                Belum ada transaksi untuk filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <div style="padding:14px 16px;">
            {{ $transactions->links() }}
        </div>
    </div>

</div>
@endsection