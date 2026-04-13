@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Edit Catatan Dokter Mitra</h3>
            <div class="text-muted">
                Perbarui catatan koreksi untuk transaksi terkait.
            </div>
        </div>

        <a href="{{ route('doctor_mitra.transactions.show', $incomeTransaction) }}" class="btn btn-outline-secondary">
            Kembali
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="mb-3">
                <div class="text-muted small">Pasien</div>
                <div class="fw-semibold">{{ $incomeTransaction->patient->name ?? '-' }}</div>
            </div>

            <div class="mb-3">
                <div class="text-muted small">Tanggal Transaksi</div>
                <div class="fw-semibold">
                    {{ $incomeTransaction->trx_date ? \Carbon\Carbon::parse($incomeTransaction->trx_date)->format('d-m-Y') : '-' }}
                </div>
            </div>

            <form method="POST" action="{{ route('doctor_mitra.notes.update', $doctorNote) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold">Isi Catatan</label>
                    <textarea
                        name="note"
                        rows="8"
                        class="form-control @error('note') is-invalid @enderror"
                        placeholder="Tulis koreksi atau klarifikasi...">{{ old('note', $doctorNote->note) }}</textarea>
                    @error('note')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
                    </button>

                    <a href="{{ route('doctor_mitra.transactions.show', $incomeTransaction) }}" class="btn btn-outline-secondary">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection