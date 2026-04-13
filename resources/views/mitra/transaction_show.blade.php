@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Detail Transaksi Dokter Mitra</h2>
            <div class="text-muted">
                Transaksi terkait pasien dan tindakan yang ditangani dokter mitra.
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('mitra.transaksi') }}" class="btn btn-outline-secondary">
                Kembali ke Transaksi
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
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
                    <div class="text-muted small">Nama Pasien</div>
                    <div class="fw-bold">{{ $incomeTransaction->patient->name ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Status</div>
                    <div class="fw-bold">
                        <span class="badge bg-secondary text-uppercase">
                            {{ $incomeTransaction->status ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Pembayaran</div>
                    <div class="fw-bold">{{ number_format((float) ($incomeTransaction->pay_total ?? 0), 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Nilai Tindakan</div>
                    <div class="fs-5 fw-bold">
                        {{ number_format((float) ($incomeTransaction->bill_total ?? 0), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Fee Dokter</div>
                    <div class="fs-5 fw-bold text-primary">
                        {{ number_format((float) ($incomeTransaction->doctor_fee_total ?? 0), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Jumlah Pembayaran Masuk</div>
                    <div class="fs-5 fw-bold text-success">
                        {{ number_format((float) ($incomeTransaction->pay_total ?? 0), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">
            Rincian Tindakan
        </div>
        <div class="card-body">
            @if($incomeTransaction->items->count() === 0)
                <div class="text-muted">Belum ada rincian tindakan.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px;">No</th>
                                <th>Jenis Tindakan</th>
                                <th style="width:90px;">Qty</th>
                                <th style="width:140px;">Harga</th>
                                <th style="width:140px;">Diskon</th>
                                <th style="width:160px;">Nilai</th>
                                <th style="width:140px;">Fee</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($incomeTransaction->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->treatment->name ?? '-' }}</td>
                                    <td>{{ number_format((float) ($item->qty ?? 0), 2, ',', '.') }}</td>
                                    <td>{{ number_format((float) ($item->unit_price ?? 0), 0, ',', '.') }}</td>
                                    <td>{{ number_format((float) ($item->discount_amount ?? 0), 0, ',', '.') }}</td>
                                    <td>{{ number_format((float) ($item->subtotal ?? 0), 0, ',', '.') }}</td>
                                    <td>{{ number_format((float) ($item->fee_amount ?? 0), 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4" id="catatan">
        <div class="card-header bg-white fw-bold">
            Catatan Koreksi Dokter Mitra
        </div>

        <div class="card-body">
            @if($isDokterMitra)
                <form method="POST" action="{{ route('doctor_mitra.notes.store', $incomeTransaction) }}" class="mb-4">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tulis Catatan Koreksi</label>
                        <textarea
                            name="note"
                            rows="5"
                            class="form-control @error('note') is-invalid @enderror"
                            placeholder="Contoh: tindakan salah input, pasien seharusnya..., fee perlu dicek, atau ada klarifikasi tindakan...">{{ old('note') }}</textarea>
                        @error('note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-warning text-dark fw-semibold">
                        Simpan Catatan
                    </button>
                </form>

                <hr>
            @endif

            @if($doctorNotes->count() === 0)
                <div class="text-muted">Belum ada catatan untuk transaksi ini.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px;">No</th>
                                <th>Catatan</th>
                                <th style="width:150px;">Dokter</th>
                                <th style="width:120px;">Status</th>
                                <th style="width:150px;">Tanggal</th>
                                <th style="width:240px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($doctorNotes as $index => $doctorNote)
                                @php
                                    $status = strtolower((string) $doctorNote->status);
                                    $badgeClass = $status === 'done'
                                        ? 'bg-success'
                                        : ($status === 'archived' ? 'bg-secondary' : 'bg-warning text-dark');
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td style="white-space:pre-line;">{{ $doctorNote->note }}</td>
                                    <td>{{ $doctorNote->doctor->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">
                                            {{ strtoupper($doctorNote->status ?? '-') }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $doctorNote->created_at ? \Carbon\Carbon::parse($doctorNote->created_at)->format('d-m-Y H:i') : '-' }}
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            @if($isDokterMitra && (string) $doctorNote->status === 'active')
                                                <a href="{{ route('doctor_mitra.notes.edit', $doctorNote) }}"
                                                   class="btn btn-sm btn-outline-primary">
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

                                                <form method="POST"
                                                      action="{{ route('doctor_mitra.notes.destroy', $doctorNote) }}"
                                                      onsubmit="return confirm('Hapus catatan ini?')">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection