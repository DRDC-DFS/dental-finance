@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Profil Saya</h4>
            <div class="text-muted small">
                Kelola data akun, foto profil, dan password akun yang sedang login.
            </div>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
    </div>

    @if (session('status') === 'profile-updated')
        <div class="alert alert-success">
            Profil berhasil diperbarui.
        </div>
    @endif

    @if (session('status') === 'password-updated')
        <div class="alert alert-success">
            Password berhasil diperbarui.
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
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

    <div class="row g-4">
        {{-- INFORMASI PROFIL --}}
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">
                    Informasi Profil
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label fw-semibold d-block">Foto Profil Saat Ini</label>

                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <img
                                    src="{{ $user->photo_url }}"
                                    alt="Foto Profil"
                                    style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid #e5e7eb;box-shadow:0 4px 10px rgba(0,0,0,.12);">

                                <div class="text-muted small">
                                    Foto ini tampil di top bar sistem dan di Master User.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Upload Foto Baru</label>
                            <input
                                type="file"
                                name="photo"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp">
                            <div class="form-text">
                                Format: JPG, JPEG, PNG, WEBP. Maksimal 2MB.
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

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama</label>
                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                value="{{ old('name', $user->name) }}"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="{{ old('email', $user->email) }}"
                                required>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Simpan Profil
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            {{-- UBAH PASSWORD --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-bold">
                    Ubah Password
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password Saat Ini</label>
                            <input
                                type="password"
                                name="current_password"
                                class="form-control"
                                autocomplete="current-password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password Baru</label>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                autocomplete="new-password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                            <input
                                type="password"
                                name="password_confirmation"
                                class="form-control"
                                autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-warning">
                            Simpan Password
                        </button>
                    </form>
                </div>
            </div>

            {{-- HAPUS AKUN --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold text-danger">
                    Hapus Akun
                </div>
                <div class="card-body">
                    <div class="text-muted small mb-3">
                        Menghapus akun akan menghapus akses login akun ini dari sistem. Tindakan ini tidak dapat dibatalkan.
                    </div>

                    <form method="POST" action="{{ route('profile.destroy') }}"
                          onsubmit="return confirm('Yakin ingin menghapus akun ini? Tindakan ini tidak dapat dibatalkan.')">
                        @csrf
                        @method('DELETE')

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Konfirmasi Password</label>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Masukkan password untuk konfirmasi"
                                required>
                        </div>

                        <button type="submit" class="btn btn-danger">
                            Hapus Akun
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection