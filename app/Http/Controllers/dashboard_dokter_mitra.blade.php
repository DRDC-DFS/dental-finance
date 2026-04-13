@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="fw-bold mb-4">Dashboard Dokter Mitra</h2>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <select name="filter" class="form-select">
                <option value="daily" {{ $filterType=='daily'?'selected':'' }}>Harian</option>
                <option value="weekly" {{ $filterType=='weekly'?'selected':'' }}>Mingguan</option>
                <option value="monthly" {{ $filterType=='monthly'?'selected':'' }}>Bulanan</option>
            </select>
        </div>

        <div class="col-md-3">
            <input type="date" name="date" value="{{ $date }}" class="form-control">
        </div>

        <div class="col-md-2">
            <input type="number" name="month" value="{{ $month }}" class="form-control" min="1" max="12">
        </div>

        <div class="col-md-2">
            <input type="number" name="year" value="{{ $year }}" class="form-control">
        </div>

        <div class="col-md-2">
            <button class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <small>Total Pasien</small>
                <h4>{{ $totalPatients }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <small>Total Transaksi</small>
                <h4>{{ $totalTransactions }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <small>Total Tindakan</small>
                <h4>{{ $totalActions }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <small>Total Fee</small>
                <h4>{{ number_format($totalFee,0,',','.') }}</h4>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header fw-bold">Transaksi Terbaru</div>
        <div class="card-body table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Tanggal</th>
                        <th>Pasien</th>
                        <th>Bill</th>
                        <th>Bayar</th>
                        <th>Fee</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentTransactions as $trx)
                    <tr>
                        <td>{{ $trx->invoice_number }}</td>
                        <td>{{ $trx->trx_date }}</td>
                        <td>{{ $trx->patient_name }}</td>
                        <td>{{ number_format($trx->bill_total,0,',','.') }}</td>
                        <td>{{ number_format($trx->pay_total,0,',','.') }}</td>
                        <td>{{ number_format($trx->doctor_fee_total,0,',','.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
