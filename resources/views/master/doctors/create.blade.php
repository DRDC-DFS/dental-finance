@extends('layouts.app')

@section('content')

<div class="container">

    <h2>Tambah Dokter</h2>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('master.doctors.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Nama Dokter</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Tipe</label>
            <select name="type" id="type" class="form-control" required>
                <option value="owner" @selected(old('type') === 'owner')>Owner</option>
                <option value="mitra" @selected(old('type') === 'mitra')>Dokter Mitra</option>
                <option value="tamu" @selected(old('type') === 'tamu')>Dokter Tamu</option>
            </select>
            <small class="text-muted">
                Fee dokter tidak lagi diatur di Master Dokter. Fee diinput manual pada Master Tindakan.
            </small>
        </div>

        <div class="mb-3 form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Aktif</label>
        </div>

        <button class="btn btn-primary">Simpan</button>
        <a href="{{ route('master.doctors.index') }}" class="btn btn-secondary">Kembali</a>
    </form>

</div>

@endsection