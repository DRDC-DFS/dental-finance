@extends('layouts.app')

@section('title', 'Tambah Pemasukan')

@section('content')
<div class="container-fluid py-2">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Tambah Pemasukan</h4>

        <a href="{{ route('income.index') }}" class="btn btn-outline-secondary">
            Kembali
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $role = strtolower((string) (auth()->user()->role ?? ''));
        $isAdmin = $role === 'admin';
    @endphp

    <div class="card">
        <div class="card-body">

            <form method="POST" action="{{ route('income.store') }}">
                @csrf

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date"
                               name="trx_date"
                               class="form-control"
                               value="{{ old('trx_date', now()->toDateString()) }}"
                               required>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Dokter</label>
                        <select name="doctor_id" class="form-select" required>
                            <option value="">- pilih dokter -</option>
                            @foreach($doctors as $d)
                                <option value="{{ $d->id }}" @selected(old('doctor_id') == $d->id)>
                                    {{ $d->name }} ({{ strtoupper($d->type) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Visibility</label>

                        @if($isAdmin)
                            <input type="text" class="form-control" value="Public" readonly>
                            <input type="hidden" name="visibility" value="public">
                        @else
                            <select name="visibility" class="form-select" required>
                                <option value="public" @selected(old('visibility','public') === 'public')>Public</option>
                                <option value="private" @selected(old('visibility','public') === 'private')>Private (Owner saja)</option>
                            </select>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nama Pasien</label>
                        <input type="text"
                               name="patient_name"
                               class="form-control"
                               value="{{ old('patient_name') }}"
                               placeholder="contoh: Budi Santoso"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Kategori Pasien</label>
                        <select name="payer_type" class="form-select" required>
                            <option value="umum" @selected(old('payer_type', 'umum') === 'umum')>Umum</option>
                            <option value="bpjs" @selected(old('payer_type') === 'bpjs')>BPJS</option>
                            <option value="khusus" @selected(old('payer_type') === 'khusus')>Khusus</option>
                        </select>
                        <div class="form-text">
                            Khusus = pasien free / keluarga / diskon manual.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">No HP (opsional)</label>
                        <input type="text"
                               name="patient_phone"
                               class="form-control"
                               value="{{ old('patient_phone') }}"
                               placeholder="contoh: 08xxxxxxxxxx">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="catatan transaksi">{{ old('notes') }}</textarea>
                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Buat Transaksi
                    </button>

                    <a href="{{ route('income.index') }}" class="btn btn-outline-secondary">
                        Batal
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>
@endsection