@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-6">

    <style>
        .card{
            background:#fff;
            border:1px solid #e5e7eb;
            border-radius:14px;
            box-shadow:0 10px 25px rgba(0,0,0,.08);
        }
        .card-h{
            padding:16px 18px;
            border-bottom:1px solid #e5e7eb;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
        }
        .card-b{padding:16px 18px}
        .btn{
            display:inline-block;
            border-radius:10px;
            padding:10px 16px;
            font-weight:900;
            text-decoration:none;
            border:0;
            cursor:pointer;
        }
        .btn-primary{
            background:#2563eb;
            color:#fff;
            box-shadow:0 6px 14px rgba(37,99,235,.25);
        }
        .btn-primary:hover{background:#1d4ed8}
        .btn-secondary{
            background:#e5e7eb;
            color:#111827;
        }
        .btn-secondary:hover{background:#d1d5db}
        .btn-danger{
            background:#dc2626;
            color:#fff;
            box-shadow:0 6px 14px rgba(220,38,38,.25);
        }
        .btn-danger:hover{background:#b91c1c}
        .btn-outline{
            background:#fff;
            color:#111827;
            border:1px solid #d1d5db;
        }
        .btn-outline:hover{background:#f9fafb}
        .btn-info{
            background:#0ea5e9;
            color:#fff;
            box-shadow:0 6px 14px rgba(14,165,233,.22);
        }
        .btn-info:hover{background:#0284c7}
        .input,.select{
            width:100%;
            border:1px solid #d1d5db;
            border-radius:10px;
            padding:10px 12px;
            font-size:14px;
            outline:none;
        }
        .input:focus,.select:focus{
            border-color:#2563eb;
            box-shadow:0 0 0 3px rgba(37,99,235,.15);
        }
        .label{
            display:block;
            font-size:13px;
            font-weight:800;
            color:#374151;
            margin-bottom:6px;
        }
        .table{
            width:100%;
            border-collapse:collapse;
            font-size:14px;
        }
        .table th{
            background:#f8fafc;
            border-bottom:1px solid #e5e7eb;
            text-align:left;
            padding:14px 16px;
        }
        .table td{
            border-bottom:1px solid #e5e7eb;
            padding:14px 16px;
            vertical-align:top;
        }
        .right{text-align:right}
        .muted{color:#6b7280}
        .topbar{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:16px;
            margin-bottom:16px;
            flex-wrap:wrap;
        }
        .actions{
            display:flex;
            gap:10px;
            align-items:center;
            flex-wrap:wrap;
        }
        .summary-grid{
            display:grid;
            grid-template-columns:1fr;
            gap:12px;
        }
        @media(min-width:768px){
            .summary-grid{
                grid-template-columns:repeat(3,1fr);
            }
        }
        .summary-box{
            border:1px solid #e5e7eb;
            border-radius:12px;
            padding:12px 14px;
            background:#fafafa;
        }
        .summary-box .title{
            font-size:12px;
            color:#6b7280;
            font-weight:700;
            margin-bottom:4px;
        }
        .summary-box .value{
            font-size:24px;
            font-weight:900;
            color:#111827;
        }
        .summary-box.success{
            background:#f0fdf4;
            border-color:#bbf7d0;
        }
        .summary-box.success .value{
            color:#166534;
        }
        .summary-box.info{
            background:#eff6ff;
            border-color:#bfdbfe;
        }
        .summary-box.info .value{
            color:#1d4ed8;
        }
        .grid-filter{
            display:grid;
            grid-template-columns:1fr;
            gap:14px;
        }
        @media(min-width:768px){
            .grid-filter.owner{
                grid-template-columns:1fr 1fr auto;
                align-items:end;
            }
            .grid-filter.admin{
                grid-template-columns:1fr auto;
                align-items:end;
            }
        }
        .badge{
            display:inline-block;
            border-radius:999px;
            padding:4px 10px;
            font-weight:900;
            font-size:12px;
            border:1px solid transparent;
        }
        .notice-box{
            background:#fff7ed;
            border:1px solid #fdba74;
            color:#9a3412;
            border-radius:12px;
            padding:12px 14px;
            margin-bottom:18px;
        }
    </style>

    @php
        $role = strtolower((string) (auth()->user()->role ?? ''));
        $isOwner = isset($isOwner) ? (bool) $isOwner : $role === 'owner';

        $rowsCollection = $rows->getCollection();
        $totalNominal = (float) $rowsCollection->sum('amount');
        $jumlahData = (int) $rowsCollection->count();
        $dateStartValue = $dateStart ?? now()->toDateString();
        $dateEndValue = $dateEnd ?? now()->toDateString();
        $singleDateValue = $dateStartValue;

        $formatShortDate = function ($value) {
            if (function_exists('tgl_id')) {
                return tgl_id($value, 'd M Y');
            }

            return \Carbon\Carbon::parse($value)->format('d-m-Y');
        };
    @endphp

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-100 border border-green-300 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-lg bg-red-100 border border-red-300 p-4 text-red-800">
            <div class="font-semibold mb-1">Terjadi error:</div>
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="topbar">
        <div>
            <h1 style="font-size:40px;font-weight:900;margin:0;">Pemasukan Lain-lain</h1>
            <div class="muted" style="margin-top:6px;">
                Modul ini dipakai untuk pemasukan <b>non-pasien</b>, agar tidak mengganggu invoice, QR, dan laporan pasien.
            </div>
        </div>

        <div class="actions">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">← Dashboard</a>

            @if($isOwner)
                <a href="{{ url('/owner-private') }}" class="btn btn-info">Pemasukan Private</a>
            @endif

            <a href="{{ route('other_income.create') }}" class="btn btn-primary">+ Tambah Pemasukan Lain-lain</a>
        </div>
    </div>

    @if($isOwner)
        <div class="notice-box">
            Owner bisa input pemasukan <b>PUBLIC</b> dari menu ini. Pemasukan <b>PRIVATE</b> tetap memakai menu terpisah.
        </div>
    @endif

    <div class="card mb-5" style="margin-bottom:20px;">
        <div class="card-b">
            <div class="summary-grid">
                <div class="summary-box success">
                    <div class="title">Total Pemasukan (Data Tampil)</div>
                    <div class="value">{{ format_rupiah($totalNominal) }}</div>
                </div>
                <div class="summary-box info">
                    <div class="title">Jumlah Data (Data Tampil)</div>
                    <div class="value">{{ $jumlahData }}</div>
                </div>
                <div class="summary-box">
                    <div class="title">Periode Filter</div>
                    <div class="value" style="font-size:18px;">
                        @if($isOwner)
                            {{ $formatShortDate($dateStartValue) }} - {{ $formatShortDate($dateEndValue) }}
                        @else
                            {{ $formatShortDate($singleDateValue) }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-5" style="margin-bottom:20px;">
        <div class="card-h">
            <h2 style="font-size:18px;font-weight:900;margin:0;">Filter Data</h2>
        </div>

        <div class="card-b">
            @if($isOwner)
                <form method="GET" action="{{ route('other_income.index') }}" class="grid-filter owner">
                    <div>
                        <label class="label">Tanggal Mulai</label>
                        <input type="date" name="date_start" class="input" value="{{ $dateStartValue }}">
                    </div>

                    <div>
                        <label class="label">Tanggal Selesai</label>
                        <input type="date" name="date_end" class="input" value="{{ $dateEndValue }}">
                    </div>

                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('other_income.index') }}" class="btn btn-outline">Reset</a>
                    </div>
                </form>
            @else
                <form method="GET" action="{{ route('other_income.index') }}" class="grid-filter admin">
                    <div>
                        <label class="label">Tanggal</label>
                        <input type="date" name="date" class="input" value="{{ $singleDateValue }}">
                    </div>

                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('other_income.index') }}" class="btn btn-outline">Reset</a>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-h">
            <h2 style="font-size:18px;font-weight:900;margin:0;">Daftar Pemasukan Lain-lain</h2>
            <div class="muted">
                Total data halaman ini: <b>{{ $rows->count() }}</b>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:160px;">Tanggal</th>
                        <th>Nama / Judul</th>
                        <th style="width:180px;">Visibility</th>
                        <th class="right" style="width:220px;">Nominal</th>
                        <th style="width:220px;">Dibuat Oleh</th>
                        <th style="width:220px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $visibility = strtolower((string) ($row->visibility ?? 'public'));
                            $badgeStyle = $visibility === 'private'
                                ? 'background:#fee2e2;color:#991b1b;border-color:#fecaca;'
                                : 'background:#dcfce7;color:#166534;border-color:#86efac;';
                        @endphp
                        <tr>
                            <td>
                                {{ function_exists('tgl_id') ? tgl_id($row->trx_date, 'd F Y') : \Carbon\Carbon::parse($row->trx_date)->format('d-m-Y') }}
                            </td>

                            <td>
                                <div style="font-weight:900;color:#111827;">
                                    {{ $row->title }}
                                </div>

                                @if(!empty($row->notes))
                                    <div class="muted" style="font-size:12px;margin-top:6px;">
                                        {{ $row->notes }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <span class="badge" style="{{ $badgeStyle }}">
                                    {{ strtoupper($row->visibility) }}
                                </span>
                            </td>

                            <td class="right" style="font-weight:900;">
                                {{ format_rupiah($row->amount) }}
                            </td>

                            <td>
                                {{ $row->creator?->name ?? '-' }}
                            </td>

                            <td>
                                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                                    <a href="{{ route('other_income.edit', $row->id) }}" class="btn btn-primary" style="padding:8px 14px;">
                                        Edit
                                    </a>

                                    <form method="POST"
                                          action="{{ route('other_income.destroy', $row->id) }}"
                                          onsubmit="return confirm('Hapus pemasukan lain-lain ini?')"
                                          style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" style="padding:8px 14px;">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:18px 16px;text-align:center;color:#6b7280;">
                                Belum ada data pemasukan lain-lain pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($rows, 'links'))
            <div class="card-b">
                {{ $rows->links() }}
            </div>
        @endif
    </div>

</div>
@endsection