@extends('layouts.app')

@section('content')
@php
    $isOwner = $isOwner ?? false;
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0">Inventori - Laporan Stok</h4>
        <div class="text-muted small">{{ $periodLabel ?? '' }}</div>
    </div>
</div>

@if(isset($alertItems) && $alertItems->count() > 0)
    <div class="alert alert-warning border-warning shadow-sm">
        <div class="fw-bold mb-2">⚠ Alert Stok Minimum</div>
        <div class="small text-muted mb-2">
            Item berikut sudah mencapai atau berada di bawah minimum stok akhir:
        </div>
        <ul class="mb-0">
            @foreach($alertItems as $alert)
                <li>
                    <strong>{{ $alert->name }}</strong>
                    — stok akhir {{ number_format((float) $alert->current_stock, 2, ',', '.') }}
                    {{ $alert->unit }}
                    / minimum {{ number_format((float) $alert->minimum_stock_value, 2, ',', '.') }}
                    {{ $alert->unit }}
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.stok') }}" class="row g-2 align-items-end">
            @if($isOwner)
                <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="date_start" class="form-control" value="{{ $dateStart ?? '' }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="date_end" class="form-control" value="{{ $dateEnd ?? '' }}">
                </div>
            @else
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" class="form-control" value="{{ $filterDate ?? '' }}">
                </div>
            @endif

            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100">Tampilkan</button>
            </div>

            <div class="col-md-2">
                <a href="{{ route('inventory.stok') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>

            <div class="col-md-2">
                <a href="{{ route('inventory.stok.export.pdf', array_filter([
                    'date' => $filterDate ?? null,
                    'date_start' => $dateStart ?? null,
                    'date_end' => $dateEnd ?? null,
                ])) }}" class="btn btn-outline-danger w-100">
                    Export PDF
                </a>
            </div>

            <div class="col-md-2">
                <a href="{{ route('inventory.stok.export.excel', array_filter([
                    'date' => $filterDate ?? null,
                    'date_start' => $dateStart ?? null,
                    'date_end' => $dateEnd ?? null,
                ])) }}" class="btn btn-outline-success w-100">
                    Export Excel
                </a>
            </div>
        </form>

        <div class="small text-muted mt-2">
            @if($isOwner)
                OWNER dapat memilih rentang tanggal. Kolom Masuk/Keluar menunjukkan pergerakan selama periode, sedangkan Stok Akhir dihitung sampai tanggal akhir periode.
            @else
                ADMIN hanya menggunakan 1 tanggal. Kolom Masuk/Keluar menunjukkan pergerakan pada tanggal tersebut, sedangkan Stok Akhir dihitung sampai tanggal itu.
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if(($items ?? collect())->count() === 0)
            <div class="text-muted">Belum ada data item inventori.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th style="width:120px">Satuan</th>
                            <th style="width:150px" class="text-end">Masuk Periode</th>
                            <th style="width:150px" class="text-end">Keluar Periode</th>
                            <th style="width:150px" class="text-end">Stok Akhir</th>
                            <th style="width:150px" class="text-end">Minimum Stok</th>
                            <th style="width:170px">Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($items as $item)
                            @php
                                $qtyIn = (float) ($periodIn[$item->id] ?? 0);
                                $qtyOut = (float) ($periodOut[$item->id] ?? 0);
                                $stock = (float) ($stockEnd[$item->id] ?? 0);
                                $minimum = (float) ($item->minimum_stock ?? 0);

                                $isBelow = $minimum > 0 && $stock < $minimum;
                                $isAtMinimum = $minimum > 0 && $stock == $minimum;
                                $isAlert = $minimum > 0 && $stock <= $minimum;

                                $rowClass = $isAlert ? 'table-warning' : '';
                            @endphp

                            <tr class="{{ $rowClass }}">
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->unit ?: '-' }}</td>

                                <td class="text-end">
                                    {{ number_format($qtyIn, 2, ',', '.') }}
                                </td>

                                <td class="text-end">
                                    {{ number_format($qtyOut, 2, ',', '.') }}
                                </td>

                                <td class="text-end fw-bold">
                                    {{ number_format($stock, 2, ',', '.') }}
                                </td>

                                <td class="text-end">
                                    {{ number_format($minimum, 2, ',', '.') }}
                                </td>

                                <td>
                                    @if($isBelow)
                                        <span class="badge bg-danger">DI BAWAH MINIMUM</span>
                                    @elseif($isAtMinimum)
                                        <span class="badge bg-warning text-dark">MINIMUM</span>
                                    @else
                                        <span class="badge bg-success">AMAN</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    Belum ada data item inventori.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2" class="text-end">Total</th>
                            <th class="text-end">
                                {{ number_format((float) collect($periodIn)->sum(), 2, ',', '.') }}
                            </th>
                            <th class="text-end">
                                {{ number_format((float) collect($periodOut)->sum(), 2, ',', '.') }}
                            </th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection