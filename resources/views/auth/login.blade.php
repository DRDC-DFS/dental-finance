@php
$setting = class_exists(\App\Models\Setting::class)
    ? \App\Models\Setting::query()->first()
    : null;

$clinicName = $setting?->clinic_name ?: 'DentalFinance';
$logoUrl    = $setting?->logo_url ?: null;

/**
 * Background login dari Master Klinik (Setting).
 * Utamakan accessor dari model agar path lebih aman untuk local / production.
 */
$loginBgUrl = $setting?->login_background_url ?: asset('assets/login-bg.jpg');

/**
 * Untuk local pakai HTTP biasa agar php artisan serve tidak error SSL.
 * Untuk online / non-local tetap pakai HTTPS.
 */
$loginAction = app()->environment('local')
    ? url('/login')
    : secure_url('/login');
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

    body{
        margin:0;
        color:var(--df-ink);
    }

    .df-login-wrap{
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:28px 16px;
        position:relative;
        overflow:hidden;
        background:
            radial-gradient(1200px 600px at 15% 15%, rgba(37,99,235,.26), transparent 60%),
            radial-gradient(900px 600px at 85% 35%, rgba(6,182,212,.22), transparent 60%),
            linear-gradient(135deg, rgba(2,6,23,.90), rgba(15,23,42,.82));
        background-repeat:no-repeat;
    }

    /* FOTO BACKGROUND DIPISAH AGAR LEBIH PASTI TAMPIL */
    .df-login-wrap::after{
        content:"";
        position:absolute;
        inset:0;
        background-image:url('{{ $loginBgUrl }}');
        background-size:cover;
        background-position:center;
        background-repeat:no-repeat;
        z-index:0;
    }

    /* OVERLAY GELAP DI ATAS FOTO */
    .df-login-wrap::before{
        content:"";
        position:absolute;
        inset:0;
        background: rgba(0,0,0,.35);
        pointer-events:none;
        z-index:1;
    }

    .df-blob{
        position:absolute;
        width:520px;
        height:520px;
        border-radius:999px;
        filter: blur(70px);
        opacity:.28;
        pointer-events:none;
        transform: translateZ(0);
        z-index:2;
    }

    .df-blob.one{
        left:-200px;
        top:-220px;
        background: radial-gradient(circle at 30% 30%, rgba(37,99,235,.95), rgba(37,99,235,0));
    }

    .df-blob.two{
        right:-240px;
        bottom:-240px;
        background: radial-gradient(circle at 30% 30%, rgba(6,182,212,.95), rgba(6,182,212,0));
    }

    .df-card{
        position:relative;
        z-index:3;
        width:100%;
        max-width:560px;
        border-radius: var(--df-radius);
        background: var(--df-card);
        box-shadow: var(--df-shadow);
        border:1px solid rgba(255,255,255,.28);
        backdrop-filter: blur(10px);
        overflow:hidden;
    }

    .df-body{
        padding:34px 30px;
    }

    @media (min-width: 768px){
        .df-body{
            padding:40px 44px;
        }
    }

    .df-header-center{
        text-align:center;
        margin-bottom:18px;
    }

    .df-logo-center{
        height:130px;
        width:auto;
        margin: 0 auto 10px;
        display:block;
        filter: drop-shadow(0 14px 26px rgba(2,6,23,.25));
    }

    .df-city-center{
        display:inline-flex;
        padding:8px 14px;
        border-radius:999px;
        background: rgba(255,255,255,.55);
        border: 1px solid rgba(15,23,42,.08);
        font-weight: 900;
        letter-spacing: 4px;
        font-size: 12px;
        color: rgba(15,23,42,.78);
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

    .df-alert-success{
        border:1px solid rgba(34,197,94,.25);
        background: rgba(34,197,94,.10);
        color: rgba(20,83,45,.95);
    }

    .df-label{
        font-weight: 900;
        font-size: 13px;
        margin-bottom: 6px;
        color: rgba(15,23,42,.90);
    }

    .df-field{
        margin-bottom: 14px;
    }

    .df-input-wrap{
        position:relative;
    }

    .df-input-icon{
        position:absolute;
        left:14px;
        top:50%;
        transform: translateY(-50%);
        width:18px;
        height:18px;
        opacity:.75;
    }

    .df-input{
        width:100%;
        border-radius: 16px;
        padding: 12px 14px 12px 44px;
        border:1px solid rgba(15,23,42,.12);
        background: rgba(255,255,255,.78);
        font-weight: 800;
        outline: none;
        transition: box-shadow .15s ease, border-color .15s ease, background .15s ease;
    }

    .df-input:focus{
        border-color: rgba(37,99,235,.45);
        box-shadow: 0 0 0 5px rgba(37,99,235,.12);
        background: rgba(255,255,255,.94);
    }

    .df-row{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        margin-top: 8px;
        margin-bottom: 18px;
        flex-wrap:wrap;
    }

    .df-check{
        display:flex;
        align-items:center;
        gap:8px;
        font-weight: 800;
        font-size: 12px;
        color: rgba(15,23,42,.78);
    }

    .df-check input{
        transform: translateY(1px);
    }

    .df-link{
        font-size:12px;
        font-weight:900;
        text-decoration:none;
        color:#2563eb;
    }

    .df-link:hover{
        text-decoration:underline;
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
        transition: transform .08s ease, box-shadow .12s ease, opacity .12s ease;
    }

    .df-btn:hover{
        opacity:.96;
        transform: translateY(-1px);
        box-shadow: 0 18px 38px rgba(37,99,235,.30);
    }

    .df-btn:active{
        transform: translateY(0px);
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

<div class="df-login-wrap">
    <div class="df-blob one"></div>
    <div class="df-blob two"></div>

    <div class="df-card">
        <div class="df-body">
            <div class="df-header-center">
                @if($logoUrl)
                    <img class="df-logo-center" src="{{ $logoUrl }}" alt="Logo Klinik">
                @else
                    <div style="
                        width:130px;height:130px;border-radius:28px;
                        background:rgba(255,255,255,.60);
                        border:1px solid rgba(15,23,42,.08);
                        display:flex;align-items:center;justify-content:center;
                        font-weight:900;font-size:30px;
                        margin:0 auto 10px;
                        box-shadow:0 14px 26px rgba(2,6,23,.12);
                    ">
                        DF
                    </div>
                @endif

                <div class="df-city-center">GORONTALO</div>
            </div>

            @if (session('status'))
                <div class="df-alert df-alert-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="df-alert df-alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ $loginAction }}">
                @csrf

                <div class="df-field">
                    <div class="df-label">Username</div>
                    <div class="df-input-wrap">
                        <svg class="df-input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M20 21a8 8 0 0 0-16 0" stroke="rgba(15,23,42,.80)" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="12" cy="8" r="4" stroke="rgba(15,23,42,.80)" stroke-width="2"/>
                        </svg>
                        <input
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            class="df-input"
                            placeholder="Masukkan username"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                </div>

                <div class="df-field">
                    <div class="df-label">Password</div>
                    <div class="df-input-wrap">
                        <svg class="df-input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 11V8a5 5 0 0 1 10 0v3" stroke="rgba(15,23,42,.80)" stroke-width="2" stroke-linecap="round"/>
                            <path d="M6 11h12v10H6V11Z" stroke="rgba(15,23,42,.80)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input
                            type="password"
                            name="password"
                            class="df-input"
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </div>

                <div class="df-row">
                    <label class="df-check">
                        <input type="checkbox" name="remember">
                        Remember me
                    </label>

                    @if (Route::has('password.request'))
                        <a class="df-link" href="{{ route('password.request') }}">
                            Lupa Password?
                        </a>
                    @endif
                </div>

                <button type="submit" class="df-btn">
                    LOG IN
                </button>
            </form>
        </div>

        <div class="df-footer">
            <span>{{ $clinicName }}</span>
            <span>Secure Login • {{ now()->format('Y') }}</span>
        </div>
    </div>
</div>
</x-guest-layout>