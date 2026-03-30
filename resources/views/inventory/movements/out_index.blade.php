@extends('layouts.app')

@section('content')
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
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0">Data Inventaris Keluar</h4>
        <div class="text-muted small">{{ $periodLabel ?? '' }}</div>
    </div>

    <a href="{{ route('inventory.movements.create', ['type' => 'out']) }}" class="btn btn-primary btn-sm">
        + Tambah Keluar
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->has('delete'))
    <div class="alert alert-danger">{{ $errors->first('delete') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.movements.index', ['type' => 'out']) }}" class="row g-2 align-items-end">
            @if($isOwner ?? false)
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
                <a href="{{ route('inventory.movements.index', ['type' => 'out']) }}" class="btn btn-outline-secondary w-100">
                    Reset
                </a>
            </div>

            <div class="col-md-2">
                <a href="{{ route('inventory.movements.export.pdf', array_filter([
                    'type' => 'out',
                    'date' => $filterDate ?? null,
                    'date_start' => $dateStart ?? null,
                    'date_end' => $dateEnd ?? null,
                ])) }}" class="btn btn-outline-danger w-100">
                    Export PDF
                </a>
            </div>

            <div class="col-md-2">
                <a href="{{ route('inventory.movements.export.excel', array_filter([
                    'type' => 'out',
                    'date' => $filterDate ?? null,
                    'date_start' => $dateStart ?? null,
                    'date_end' => $dateEnd ?? null,
                ])) }}" class="btn btn-outline-success w-100">
                    Export Excel
                </a>
            </div>
        </form>

        <div class="small text-muted mt-2">
            @if($isOwner ?? false)
                OWNER dapat memilih rentang tanggal untuk melihat data inventori keluar.
            @else
                ADMIN hanya menggunakan 1 tanggal untuk melihat data inventori keluar.
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">

        @if($movements->count() === 0)
            <div class="text-muted">Belum ada data inventori keluar pada filter yang dipilih.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:140px">Tanggal</th>
                            <th>Item</th>
                            <th style="width:120px" class="text-end">Qty</th>
                            <th style="width:180px">Reference</th>
                            <th>Notes</th>
                            <th style="width:140px" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $m)
                            <tr>
                                <td>{{ $formatTanggal($m->date) }}</td>
                                <td>{{ $m->item->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format(abs((float) $m->qty), 2, ',', '.') }}</td>
                                <td>{{ $m->reference ?: '-' }}</td>
                                <td>{{ $m->notes ?: '-' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('inventory.movements.edit', ['type' => 'out', 'id' => $m->id]) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>

                                    <form method="POST"
                                          action="{{ route('inventory.movements.destroy', ['type' => 'out', 'id' => $m->id]) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Hapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2" class="text-end">Total Qty Keluar</th>
                            <th class="text-end">
                                {{ number_format((float) $movements->sum(fn ($row) => abs((float) $row->qty)), 2, ',', '.') }}
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