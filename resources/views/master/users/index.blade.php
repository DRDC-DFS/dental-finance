@extends('layouts.app')

@section('content')
@php
    $setting = class_exists(\App\Models\Setting::class)
        ? \App\Models\Setting::query()->first()
        : null;

    $logoUrl = !empty($setting?->logo_path)
        ? asset('storage/' . ltrim((string) $setting->logo_path, '/'))
        : null;

    $loginBgUrl = !empty($setting?->login_background_path)
        ? asset('storage/' . ltrim((string) $setting->login_background_path, '/'))
        : null;

    $profileUrl = \Illuminate\Support\Facades\Route::has('profile.edit')
        ? route('profile.edit')
        : url('/profile');
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Master User</h4>
        <div class="text-muted small">
            Owner dapat mengelola admin, dokter mitra, branding sistem, dan menuju profil akun sendiri untuk upload foto akun.
        </div>
    </div>
    <a href="{{ route('master.users.create') }}" class="btn btn-primary btn-sm">+ Tambah User</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
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

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2" method="GET" action="{{ route('master.users.index') }}">
            <div class="col-md-10">
                <input
                    type="text"
                    name="q"
                    value="{{ $q ?? '' }}"
                    class="form-control"
                    placeholder="Cari nama / email user..."
                >
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-secondary">Cari</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        @if($users->count() === 0)
            <div class="text-muted">Belum ada data user.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:90px;">Foto</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th style="width:140px;">Role</th>
                            <th style="width:180px;">Dokter Terkait</th>
                            <th style="width:140px;">Status</th>
                            <th style="width:460px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $u)
                            @php
                                $roleValue = strtolower((string) $u->role);
                                $roleText = strtoupper((string) $u->role);
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <img
                                        src="{{ $u->photo_url }}"
                                        alt="Foto {{ $u->name }}"
                                        style="width:54px;height:54px;border-radius:50%;object-fit:cover;border:2px solid #e5e7eb;box-shadow:0 3px 8px rgba(0,0,0,.12);"
                                    >
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ $u->name }}</div>
                                    @if(auth()->id() === $u->id)
                                        <div class="small text-primary">Akun yang sedang login</div>
                                    @endif
                                </td>

                                <td>{{ $u->email }}</td>

                                <td>
                                    @if($roleValue === 'owner')
                                        <span class="badge bg-dark text-uppercase">{{ $roleText }}</span>
                                    @elseif($roleValue === 'admin')
                                        <span class="badge bg-primary text-uppercase">{{ $roleText }}</span>
                                    @elseif($roleValue === 'dokter_mitra')
                                        <span class="badge bg-info text-dark text-uppercase">DOKTER MITRA</span>
                                    @else
                                        <span class="badge bg-secondary text-uppercase">{{ $roleText }}</span>
                                    @endif
                                </td>

                                <td>
                                    @if($roleValue === 'dokter_mitra')
                                        <div class="fw-semibold">{{ $u->doctor->name ?? '-' }}</div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>

                                <td>
                                    @if((int) $u->is_active === 1)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>

                                <td>
                                    @if($roleValue === 'owner')
                                        <div class="d-flex flex-wrap gap-2">
                                            @if(auth()->id() === $u->id)
                                                <a href="{{ $profileUrl }}" class="btn btn-sm btn-outline-primary">
                                                    Profil / Upload Foto
                                                </a>

                                                <a href="{{ route('owner.password.edit') }}" class="btn btn-sm btn-warning">
                                                    Ubah Password
                                                </a>
                                            @else
                                                <span class="text-muted small">Owner dikelola lewat akun sendiri</span>
                                            @endif
                                        </div>
                                    @elseif(in_array($roleValue, ['admin', 'dokter_mitra'], true))
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="{{ route('master.users.edit', $u) }}" class="btn btn-sm btn-primary">
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('master.users.toggle', $u) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm {{ (int) $u->is_active === 1 ? 'btn-danger' : 'btn-success' }}"
                                                    onclick="return confirm('Yakin ubah status user ini?')"
                                                >
                                                    {{ (int) $u->is_active === 1 ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('master.users.reset-password', $u) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-warning"
                                                    onclick="return confirm('Yakin reset password user ini menjadi 12345678?')"
                                                >
                                                    Reset Password
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-muted small">Tidak ada aksi</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header fw-bold">
        Branding Sistem
    </div>
    <div class="card-body">
        <div class="text-muted small mb-3">
            Upload logo klinik dan background halaman login. Perubahan di sini akan otomatis dipakai oleh sistem.
        </div>

        <form method="POST" action="{{ route('master.branding.update') }}" enctype="multipart/form-data">
            @csrf

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Logo Klinik</label>
                    <input
                        type="file"
                        name="logo"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp,.svg"
                    >
                    <div class="form-text">
                        Format: JPG, JPEG, PNG, WEBP, SVG. Maksimal 2MB.
                    </div>

                    <div class="mt-3">
                        <div class="small text-muted mb-2">Preview Logo Saat Ini</div>

                        @if($logoUrl)
                            <div class="border rounded bg-light p-3 text-center">
                                <img src="{{ $logoUrl }}" alt="Logo Klinik" style="max-height:120px; width:auto;">
                            </div>
                        @else
                            <div class="border rounded bg-light p-3 text-muted text-center">
                                Belum ada logo diupload.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Background Login</label>
                    <input
                        type="file"
                        name="login_background"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp"
                    >
                    <div class="form-text">
                        Format: JPG, JPEG, PNG, WEBP. Maksimal 4MB.
                    </div>

                    <div class="mt-3">
                        <div class="small text-muted mb-2">Preview Background Login Saat Ini</div>

                        @if($loginBgUrl)
                            <div class="border rounded bg-light p-2">
                                <img
                                    src="{{ $loginBgUrl }}"
                                    alt="Background Login"
                                    class="img-fluid rounded"
                                    style="max-height:180px; width:100%; object-fit:cover;"
                                >
                            </div>
                        @else
                            <div class="border rounded bg-light p-3 text-muted text-center">
                                Belum ada background login diupload.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    Simpan Branding
                </button>
                <a href="{{ route('master.users.index') }}" class="btn btn-outline-secondary">
                    Refresh Halaman
                </a>
            </div>
        </form>
    </div>
</div>
@endsection