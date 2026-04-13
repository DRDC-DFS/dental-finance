@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Fee Dokter Mitra</h3>
            <div class="text-muted">
                Ringkasan pembayaran dan fee dokter mitra berdasarkan transaksi yang tercatat.
            </div>
        </div>

        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
            Kembali ke Dashboard
        </a>
    </div>

    {{-- SUMMARY --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Transaksi</div>
                    <div class="fs-3 fw-bold">
                        {{ number_format((int) ($feeSummary['total_transactions'] ?? 0), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Pembayaran</div>
                    <div class="fs-4 fw-bold">
                        {{ number_format((float) ($feeSummary['total_payment'] ?? 0), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Fee Dokter</div>
                    <div class="fs-4 fw-bold text-primary">
                        {{ number_format((float) ($feeSummary['total_fee'] ?? 0), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">
            Rincian Fee (Audit View)
        </div>

        <div class="card-body">
            @if($fees->count() === 0)
                <div class="text-muted">Belum ada data fee untuk dokter mitra ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Invoice</th>
                                <th>Nama Pasien</th>
                                <th>Status</th>
                                <th>Pembayaran</th>
                                <th>Fee (Invoice)</th>
                                <th>Fee (Item)</th>
                                <th>Jumlah Tindakan</th>
                                <th>Status Validasi</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fees as $index => $fee)

                                @php
                                    $isDraft = strtolower($fee->status ?? '') === 'draft';

                                    $feeInvoice = (float) ($fee->doctor_fee_total ?? 0);
                                    $feeItem = (float) ($fee->total_item_fee ?? 0);

                                    $isMatch = abs($feeInvoice - $feeItem) < 1;

                                @endphp

                                <tr>
                                    <td>{{ $fees->firstItem() + $index }}</td>

                                    <td>
                                        {{ $fee->trx_date ? \Carbon\Carbon::parse($fee->trx_date)->format('d-m-Y') : '-' }}
                                    </td>

                                    <td class="fw-semibold">
                                        {{ $fee->invoice_number ?? '-' }}
                                    </td>

                                    <td>{{ $fee->patient_name ?? '-' }}</td>

                                    <td>
                                        <span class="badge {{ $isDraft ? 'bg-warning text-dark' : 'bg-success' }}">
                                            {{ strtoupper($fee->status ?? '-') }}
                                        </span>
                                    </td>

                                    <td>{{ number_format((float) $fee->pay_total, 0, ',', '.') }}</td>

                                    {{-- FEE DARI INVOICE --}}
                                    <td class="fw-bold">
                                        {{ number_format($feeInvoice, 0, ',', '.') }}
                                    </td>

                                    {{-- FEE DARI ITEM --}}
                                    <td>
                                        {{ number_format($feeItem, 0, ',', '.') }}
                                    </td>

                                    {{-- JUMLAH ITEM --}}
                                    <td class="text-center">
                                        {{ $fee->total_items ?? 0 }}
                                    </td>

                                    {{-- VALIDASI --}}
                                    <td>
                                        @if($isMatch)
                                            <span class="badge bg-success">VALID</span>
                                        @else
                                            <span class="badge bg-danger">TIDAK SESUAI</span>
                                        @endif
                                    </td>

                                    {{-- KETERANGAN --}}
                                    <td>
                                        @if($isDraft)
                                            <span class="text-warning fw-semibold">
                                                Draft (belum income klinik)
                                            </span>
                                        @elseif(!$isMatch)
                                            <span class="text-danger fw-semibold">
                                                Selisih fee perlu dicek
                                            </span>
                                        @else
                                            <span class="text-success fw-semibold">
                                                Sudah valid & masuk income
                                            </span>
                                        @endif
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $fees->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection