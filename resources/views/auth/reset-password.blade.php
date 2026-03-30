@php
$setting = class_exists(\App\Models\Setting::class)
    ? \App\Models\Setting::query()->first()
    : null;

$logoPath = $setting?->logo_path ?: null;

$loginBgPath = $setting?->login_background_path
    ?: ($setting?->login_bg_path
    ?: ($setting?->background_path
    ?: ($setting?->bg_path
    ?: ($setting?->login_bg ?: null))));

$loginBgUrl = $loginBgPath
    ? asset('storage/' . ltrim((string) $loginBgPath, '/'))
    : asset('assets/login-bg.jpg');
@endphp

<x-guest-layout>
<style>
    :root{
        --df-ink:#0f172a;
        --df-muted:#64748b;
        --df-card:rgba(255,255,255,.86);
        --df-line:rgba(15,23,42,.10);
        --df-shadow:0 30px 80px rgba(2,6,23,.35);
        --df-brand1:#2563eb;
        --df-brand2:#06b6d4;
        --df-radius:26px;
    }

    body{ margin:0; color:var(--df-ink); }

    .df-wrap{
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:28px 16px;
        position:relative;
        overflow:hidden;
        background-image:
            radial-gradient(1200px 600px at 15% 15%, rgba(37,99,235,.26), transparent 60%),
            radial-gradient(900px 600px at 85% 35%, rgba(6,182,212,.22), transparent 60%),
            linear-gradient(135deg, rgba(2,6,23,.90), rgba(15,23,42,.82)),
            url("{{ $loginBgUrl }}");
        background-size: cover, cover, cover, cover;
        background-position: center, center, center, center;
        background-repeat: no-repeat;
    }

    .df-wrap::before{
        content:"";
        position:absolute;
        inset:0;
        background: rgba(0,0,0,.35);
        pointer-events:none;
    }

    .df-card{
        position:relative;
        z-index:2;
        width:100%;
        max-width:560px;
        border-radius: var(--df-radius);
        background: var(--df-card);
        box-shadow: var(--df-shadow);
        border:1px solid rgba(255,255,255,.28);
        backdrop-filter: blur(10px);
        overflow:hidden;
    }

    .df-body{ padding:34px 30px; }
    @media (min-width: 768px){
        .df-body{ padding:40px 44px; }
    }

    .df-header-center{
        text-align:center;
        margin-bottom:18px;
    }

    .df-logo-center{
        height:110px;
        width:auto;
        margin: 0 auto 10px;
        display:block;
        filter: drop-shadow(0 14px 26px rgba(2,6,23,.25));
    }

    .df-title{
        font-weight:900;
        font-size:24px;
        margin-bottom:6px;
    }

    .df-subtitle{
        color:var(--df-muted);
        font-size:13px;
        font-weight:700;
        line-height:1.5;
    }

    .df-alert{
        border-radius:16px;
        padding:12px 14px;
        font-weight:800;
        font-size:13px;
        margin:16px 0 14px;
    }

    .df-alert-danger{
        border:1px solid rgba(239,68,68,.25);
        background: rgba(239,68,68,.10);
        color: rgba(127,29,29,.95);
    }

    .df-label{
        font-weight: 900;
        font-size: 13px;
        margin-bottom: 6px;
        color: rgba(15,23,42,.90);
    }

    .df-field{
        margin-bottom:14px;
    }

    .df-input{
        width:100%;
        border-radius: 16px;
        padding: 12px 14px;
        border:1px solid rgba(15,23,42,.12);
        background: rgba(255,255,255,.78);
        font-weight: 800;
        outline: none;
    }

    .df-input:focus{
        border-color: rgba(37,99,235,.45);
        box-shadow: 0 0 0 5px rgba(37,99,235,.12);
        background: rgba(255,255,255,.94);
    }

    .df-btn{
        width:100%;
        border-radius: 16px;
        padding: 12px 16px;
        border:0;
        color:#fff;
        font-weight: 900;
        letter-spacing: .4px;
        background: linear-gradient(90deg, var(--df-brand1), var(--df-brand2));
        box-shadow: 0 14px 30px rgba(37,99,235,.25);
        margin-top:8px;
    }

    .df-footer{
        padding:14px 18px;
        border-top:1px solid rgba(15,23,42,.08);
        display:flex;
        justify-content:space-between;
        gap:10px;
        color: rgba(100,116,139,.95);
        font-weight: 800;
        font-size: 12px;
        background: rgba(255,255,255,.55);
    }
</style>

<div class="df-wrap">
    <div class="df-card">
        <div class="df-body">
            <div class="df-header-center">
                @if($logoPath)
                    <img class="df-logo-center" src="{{ asset('storage/'.ltrim($logoPath,'/')) }}" alt="Logo Klinik">
                @endif
                <div class="df-title">Reset Password</div>
                <div class="df-subtitle">
                    Masukkan email dan password baru untuk menyelesaikan proses reset password.
                </div>
            </div>

            @if ($errors->any())
                <div class="df-alert df-alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="df-field">
                    <div class="df-label">Email</div>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $request->email) }}"
                        class="df-input"
                        required
                        autofocus
                        autocomplete="username"
                    >
                </div>

                <div class="df-field">
                    <div class="df-label">Password Baru</div>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="df-input"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="df-field">
                    <div class="df-label">Konfirmasi Password Baru</div>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="df-input"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <button type="submit" class="df-btn">
                    Simpan Password Baru
                </button>
            </form>
        </div>

        <div class="df-footer">
            <span>Dental Finance System</span>
            <span>Reset Password</span>
        </div>
    </div>
</div>
</x-guest-layout>