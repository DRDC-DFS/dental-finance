@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Recalculate Fee Dokter</h4>
            <div class="text-muted small">
                Transaksi: <span class="fw-semibold">{{ $trx->invoice_number ?? ('#'.$trx->id) }}</span> •
                Status: <span class="fw-semibold text-uppercase">{{ $trx->status }}</span>
            </div>
            <div class="text-muted small">
                Dokter: <span class="fw-semibold">{{ $trx->doctor->name ?? '-' }}</span> •
                Pasien: <span class="fw-semibold">{{ $trx->patient->name ?? '-' }}</span> •
                Tanggal: <span class="fw-semibold">{{ $trx->trx_date }}</span>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('income.edit', $trx->id) }}" class="btn btn-outline-secondary btn-sm">
                Kembali ke Edit
            </a>

            <form method="POST" action="{{ route('income.recalculate_fee.run', $trx->id) }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    Recalculate Fee
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tindakan</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Fee Item</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sumSub = 0; $sumFee = 0; @endphp
                        @forelse($items as $it)
                            @php
                                $sumSub += (float)$it->subtotal;
                                $sumFee += (float)$it->fee_amount;
                            @endphp
                            <tr>
                                <td>{{ $it->treatment_name }}</td>
                                <td class="text-end">{{ number_format($it->qty, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($it->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($it->subtotal, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($it->fee_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">Belum ada item tindakan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="3">TOTAL</td>
                            <td class="text-end">{{ number_format($sumSub, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($sumFee, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="small text-muted mt-2">
                Catatan:
                <ul class="mb-0">
                    <li>Fee diambil dari <code>doctor_treatment_fees</code> (fee_type + fee_value).</li>
                    <li><code>manual</code> tidak di-override (tetap pakai fee_amount yang sudah ada).</li>
                    <li>Jika tidak ada mapping, fallback ke <code>doctors.default_fee_percent</code>.</li>
                </ul>
            </div>
        </div>
    </div>

</div>
@endsection