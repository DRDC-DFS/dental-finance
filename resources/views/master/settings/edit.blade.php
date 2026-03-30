@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Setting Klinik</h4>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('master.settings.update') }}" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Klinik</label>
                <input type="text" name="clinic_name" class="form-control" value="{{ old('clinic_name', $setting->clinic_name) }}">
                @error('clinic_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Owner (Header/Kwitansi)</label>
                <input type="text" name="owner_doctor_name" class="form-control" value="{{ old('owner_doctor_name', $setting->owner_doctor_name) }}">
                @error('owner_doctor_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <hr>

            <div class="mb-3">
                <label class="form-label fw-semibold">Upload Logo (dipakai Topbar/Login/Dashboard)</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
                @error('logo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                <div class="mt-3">
                    <div class="text-muted small mb-2">Preview Logo:</div>
                    @if(!empty($setting->logo_path))
                        <img src="{{ asset('storage/'.$setting->logo_path) }}" alt="Logo" style="height:90px;object-fit:contain;">
                    @else
                        <div class="text-muted">Belum ada logo.</div>
                    @endif
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Upload Background Aplikasi (dipakai Login)</label>
                <input type="file" name="background" class="form-control" accept="image/*">
                @error('background')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                <div class="mt-3">
                    <div class="text-muted small mb-2">Preview Background:</div>
                    @if(!empty($setting->login_background_path))
                        <img src="{{ asset('storage/'.$setting->login_background_path) }}" alt="Background" style="width:100%; max-width:520px; border-radius:10px; border:1px solid #ddd;">
                    @else
                        <div class="text-muted">Belum ada background.</div>
                    @endif
                </div>

                <div class="text-muted small mt-2">
                    Tips: pakai gambar landscape (misal 1920×1080) supaya hasil rapi.
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection