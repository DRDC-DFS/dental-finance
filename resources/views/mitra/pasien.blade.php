@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Data Pasien Dokter Mitra</h3>
            <div class="text-muted">
                Daftar pasien yang pernah ditangani oleh dokter mitra ini.
            </div>
        </div>

        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
            Kembali ke Dashboard
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">
            Daftar Pasien
        </div>

        <div class="card-body">
            @if($patients->count() === 0)
                <div class="text-muted">Belum ada data pasien untuk dokter mitra ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px;">No</th>
                                <th>Nama Pasien</th>
                                <th style="width:150px;">Total Transaksi</th>
                                <th style="width:150px;">Kunjungan Awal</th>
                                <th style="width:150px;">Kunjungan Terakhir</th>
                                <th style="width:180px;">Total Pembayaran</th>
                                <th style="width:160px;">Total Fee</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patients as $index => $patient)
                                <tr>
                                    <td>{{ $patients->firstItem() + $index }}</td>
                                    <td class="fw-semibold">{{ $patient->patient_name }}</td>
                                    <td>{{ number_format((int) $patient->total_transactions, 0, ',', '.') }}</td>
                                    <td>
                                        {{ $patient->first_visit_date ? \Carbon\Carbon::parse($patient->first_visit_date)->format('d-m-Y') : '-' }}
                                    </td>
                                    <td>
                                        {{ $patient->last_visit_date ? \Carbon\Carbon::parse($patient->last_visit_date)->format('d-m-Y') : '-' }}
                                    </td>
                                    <td>{{ number_format((float) $patient->total_payment, 0, ',', '.') }}</td>
                                    <td>{{ number_format((float) $patient->total_fee, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $patients->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection