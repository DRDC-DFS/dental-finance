@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Edit Admin</h4>
    <a href="{{ route('master.users.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('master.users.update', $user) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Nama</label>
                <input class="form-control" name="name" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Foto Admin Saat Ini</label>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <img
                        src="{{ $user->photo_url }}"
                        alt="Foto Admin"
                        style="width:84px;height:84px;border-radius:50%;object-fit:cover;border:3px solid #e5e7eb;box-shadow:0 4px 10px rgba(0,0,0,.12);">

                    <div class="text-muted small">
                        Foto akan tampil pada top bar saat akun ini login.
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Ganti / Upload Foto Baru</label>
                <input class="form-control" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
                <div class="form-text">
                    Opsional. Format: JPG, JPEG, PNG, WEBP. Maksimal 2MB.
                </div>
            </div>

            @if(!empty($user->photo_path))
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="remove_photo">
                    <label class="form-check-label" for="remove_photo">
                        Hapus foto saat ini
                    </label>
                </div>
            @endif

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Password Baru (opsional)</label>
                    <input class="form-control" type="password" name="password">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input class="form-control" type="password" name="password_confirmation">
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ (int)$user->is_active === 1 ? 'checked' : '' }}>
                <label class="form-check-label">Aktif</label>
            </div>

            <button class="btn btn-primary">Simpan Perubahan</button>
        </form>
    </div>
</div>
@endsection