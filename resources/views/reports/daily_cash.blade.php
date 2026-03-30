@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Kas Harian (TUNAI)</h4>
</div>

<form class="row g-2 mb-3" method="GET" action="{{ url('/reports/daily-cash') }}">
    <div class="col-auto">
        <label class="form-label mb-0 small text-muted">Dari</label>
        <input type="date" name="from" class="form-control" value="{{ $from }}">
    </div>
    <div class="col-auto">
        <label class="form-label mb-0 small text-muted">Sampai</label>
        <input type="date" name="to" class="form-control" value="{{ $to }}">
    </div>
    <div class="col-auto d-flex align-items-end">
        <button class="btn btn-primary">Filter</button>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Cash In (TUNAI)</div>
                <div class="fs-4 fw-bold">Rp {{ number_format($cashIn, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Cash Out (TUNAI)</div>
                <div class="fs-4 fw-bold">Rp {{ number_format($cashOut, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Net Cash</div>
                <div class="fs-4 fw-bold">Rp {{ number_format($cashNet, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header fw-semibold">Detail Cash In (Pembayaran TUNAI)</div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width:120px;">Tanggal</th>
                            <th>No Invoice</th>
                            <th>Pasien</th>
                            <th style="width:160px;" class="text-end">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashInRows as $row)
                            <tr>
                                <td>{{ $row->pay_date }}</td>
                                <td>{{ $row->invoice_number }}</td>
                                <td>{{ $row->patient_name ?: '-' }}</td>
                                <td class="text-end">Rp {{ number_format($row->amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted p-3">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header fw-semibold">Detail Cash Out (Pengeluaran TUNAI)</div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width:120px;">Tanggal</th>
                            <th>Nama Pengeluaran</th>
                            <th style="width:160px;" class="text-end">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashOutRows as $row)
                            <tr>
                                <td>{{ $row->expense_date }}</td>
                                <td>{{ $row->category_name ?: '-' }}</td>
                                <td class="text-end">Rp {{ number_format($row->amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted p-3">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection