@extends('layouts.app')

@section('title', 'Edit Dokter')

@section('content')
<div class="container-fluid py-2">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Edit Dokter</h4>

        <a href="{{ route('master.doctors.index') }}" class="btn btn-outline-secondary">
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

    <div class="card">
        <div class="card-body">

            <form method="POST" action="{{ route('master.doctors.update', $doctor->id) }}" id="doctorEditForm">
                @csrf
                @method('PATCH')

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Nama Dokter</label>
                        <input type="text"
                               name="name"
                               class="form-control"
                               value="{{ old('name', $doctor->name) }}"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipe</label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="owner" @selected(old('type', $doctor->type) === 'owner')>Owner</option>
                            <option value="mitra" @selected(old('type', $doctor->type) === 'mitra')>Mitra</option>
                            <option value="tamu"  @selected(old('type', $doctor->type) === 'tamu')>Tamu</option>
                        </select>
                        <div class="form-text">
                            Fee dokter tidak lagi diatur di Master Dokter. Fee diinput manual pada Master Tindakan.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Aktif</label>
                        <select name="is_active" class="form-select" required>
                            <option value="1" @selected((int)old('is_active', $doctor->is_active) === 1)>Ya</option>
                            <option value="0" @selected((int)old('is_active', $doctor->is_active) === 0)>Tidak</option>
                        </select>
                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan
                    </button>

                    <a href="{{ route('master.doctors.index') }}" class="btn btn-outline-secondary">
                        Batal
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>
@endsection