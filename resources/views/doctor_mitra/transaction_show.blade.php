@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Detail Transaksi Dokter Mitra</h2>
            <div class="text-muted">
                Invoice: <strong>{{ $incomeTransaction->invoice_number ?? '-' }}</strong>
            </div>
        </div>

        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                Kembali ke Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Tanggal</div>
                    <div class="fw-bold">
                        {{ $incomeTransaction->trx_date ? \Carbon\Carbon::parse($incomeTransaction->trx_date)->format('d-m-Y') : '-' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Pasien</div>
                    <div class="fw-bold">{{ $incomeTransaction->patient->name ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Dokter</div>
                    <div class="fw-bold">{{ $incomeTransaction->doctor->name ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Status</div>
                    <div class="fw-bold text-uppercase">{{ $incomeTransaction->status ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #0d6efd !important;">
                <div class="card-body">
                    <div class="text-muted small">Total Bill</div>
                    <div class="fs-5 fw-bold">{{ format_rupiah((float) ($incomeTransaction->bill_total ?? 0)) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #198754 !important;">
                <div class="card-body">
                    <div class="text-muted small">Total Pembayaran</div>
                    <div class="fs-5 fw-bold">{{ format_rupiah((float) ($incomeTransaction->pay_total ?? 0)) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #f59e0b !important;">
                <div class="card-body">
                    <div class="text-muted small">Total Fee Dokter</div>
                    <div class="fs-5 fw-bold">{{ format_rupiah((float) ($incomeTransaction->doctor_fee_total ?? 0)) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Daftar Tindakan</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tindakan</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Diskon</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incomeTransaction->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->treatment->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float) ($item->qty ?? 0), 2, ',', '.') }}</td>
                                <td class="text-end">{{ format_rupiah((float) ($item->unit_price ?? 0)) }}</td>
                                <td class="text-end">{{ format_rupiah((float) ($item->discount_amount ?? 0)) }}</td>
                                <td class="text-end">{{ format_rupiah((float) ($item->subtotal ?? 0)) }}</td>
                                <td class="text-end">{{ format_rupiah((float) ($item->fee_amount ?? 0)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada item tindakan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Riwayat Pembayaran</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tanggal Bayar</th>
                            <th>Metode</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incomeTransaction->payments as $index => $payment)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $payment->pay_date ? \Carbon\Carbon::parse($payment->pay_date)->format('d-m-Y') : '-' }}</td>
                                <td>{{ $payment->channel ?? '-' }}</td>
                                <td class="text-end">{{ format_rupiah((float) ($payment->amount ?? 0)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada pembayaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Catatan Dokter Mitra</div>
        <div class="card-body">
            @if($isDokterMitra)
                <form method="POST" action="{{ route('doctor_mitra.notes.store', $incomeTransaction) }}" class="mb-4">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tambah Catatan Koreksi</label>
                        <textarea
                            name="note"
                            rows="4"
                            class="form-control @error('note') is-invalid @enderror"
                            placeholder="Tulis koreksi, klarifikasi tindakan, atau laporan kesalahan input...">{{ old('note') }}</textarea>
                        @error('note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Simpan Catatan
                    </button>
                </form>

                <hr>
            @endif

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">#</th>
                            <th>Catatan</th>
                            <th>Dokter</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th style="width: 220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($doctorNotes as $index => $doctorNote)
                            @php
                                $statusClass = match ((string) $doctorNote->status) {
                                    'active' => 'bg-primary',
                                    'done' => 'bg-success',
                                    'archived' => 'bg-secondary',
                                    default => 'bg-dark',
                                };
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td style="white-space: pre-line;">{{ $doctorNote->note }}</td>
                                <td>{{ $doctorNote->doctor->name ?? '-' }}</td>
                                <td><span class="badge {{ $statusClass }}">{{ strtoupper((string) $doctorNote->status) }}</span></td>
                                <td>{{ $doctorNote->created_at ? \Carbon\Carbon::parse($doctorNote->created_at)->format('d-m-Y H:i') : '-' }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if($isDokterMitra && (string) $doctorNote->status === 'active')
                                            <a href="{{ route('doctor_mitra.notes.edit', $doctorNote) }}" class="btn btn-sm btn-outline-primary">
                                                Edit
                                            </a>
                                        @endif

                                        @if($isOwner)
                                            @if((string) $doctorNote->status !== 'done')
                                                <form method="POST" action="{{ route('doctor_mitra.notes.done', $doctorNote) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                                        Tandai Selesai
                                                    </button>
                                                </form>
                                            @endif

                                            @if((string) $doctorNote->status !== 'archived')
                                                <form method="POST" action="{{ route('doctor_mitra.notes.archive', $doctorNote) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                        Arsipkan
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('doctor_mitra.notes.destroy', $doctorNote) }}" onsubmit="return confirm('Hapus catatan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada catatan dokter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
