@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Data Transaksi Dokter Mitra</h3>
            <div class="text-muted">
                Detail tindakan, pasien, nilai transaksi, dan fee dokter mitra.
            </div>
        </div>

        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
            Kembali ke Dashboard
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">
            Daftar Transaksi
        </div>

        <div class="card-body">
            @if($transactions->count() === 0)
                <div class="text-muted">Belum ada data transaksi untuk dokter mitra ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nama Pasien</th>
                                <th>Jenis Tindakan</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Diskon</th>
                                <th>Nilai</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th style="width:180px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $index => $trx)
                                <tr>
                                    <td>{{ $transactions->firstItem() + $index }}</td>

                                    <td>
                                        {{ $trx->trx_date ? \Carbon\Carbon::parse($trx->trx_date)->format('d-m-Y') : '-' }}
                                    </td>

                                    <td class="fw-semibold">{{ $trx->patient_name ?? '-' }}</td>

                                    <td>{{ $trx->treatment_name ?? '-' }}</td>

                                    <td>{{ number_format((float) $trx->qty, 2, ',', '.') }}</td>

                                    <td>{{ number_format((float) $trx->unit_price, 0, ',', '.') }}</td>

                                    <td>{{ number_format((float) $trx->discount_amount, 0, ',', '.') }}</td>

                                    <td>{{ number_format((float) $trx->subtotal, 0, ',', '.') }}</td>

                                    <td>{{ number_format((float) $trx->fee_amount, 0, ',', '.') }}</td>

                                    <td>
                                        <span class="badge bg-secondary text-uppercase">
                                            {{ $trx->status ?? '-' }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('doctor_mitra.transactions.show', $trx->transaction_id) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                Detail
                                            </a>

                                            <a href="{{ route('doctor_mitra.transactions.show', $trx->transaction_id) }}#catatan"
                                               class="btn btn-sm btn-warning text-dark">
                                                Catatan
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection