<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dental Finance</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

/* ===== GLOBAL BACKGROUND ===== */

html, body{
    margin:0;
    min-height:100%;
}

body{
    min-height:100vh;
    background:#f1f5f9;
}

/* wrapper background sistem */
.dfs-page-bg{
    position:relative;
    min-height:100vh;
    overflow:hidden;
    background:#f8fafc;
}

/* layer gambar background */
.dfs-page-bg::before{
    content:"";
    position:fixed;
    inset:0;
    z-index:0;
    background-image:var(--dfs-bg-image);
    background-size:cover;
    background-position:center center;
    background-repeat:no-repeat;
    filter:brightness(1.14) contrast(1.06) saturate(1.08);
    transform:scale(1.02);
}

/* overlay agar tulisan tetap terbaca tetapi background tetap terlihat */
.dfs-page-overlay{
    position:relative;
    z-index:1;
    min-height:100vh;
    background:
        linear-gradient(
            to bottom,
            rgba(255,255,255,0.68),
            rgba(255,255,255,0.74)
        );
    backdrop-filter:blur(0.4px);
}

/* ===== TOP BAR ===== */

.dfs-topbar{
    background:#1fb6d5;
    height:90px;
    display:flex;
    align-items:center;
}

.dfs-brand{
    display:flex;
    align-items:center;
}

.dfs-logo-box{
    height:80px;
    display:flex;
    align-items:center;
    margin-left:22px;
}

.dfs-clinic-logo{
    height:70px;
    width:auto;
    display:block;
    filter:drop-shadow(0 6px 12px rgba(0,0,0,.25));
}

.dfs-top-actions{
    display:flex;
    align-items:center;
    gap:12px;
}

.dfs-userbox{
    display:flex;
    align-items:center;
    gap:10px;
    background:rgba(255,255,255,0.14);
    border:1px solid rgba(255,255,255,0.18);
    padding:8px 12px;
    border-radius:14px;
    box-shadow:0 4px 12px rgba(0,0,0,.12);
}

.dfs-user-photo{
    width:42px;
    height:42px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid rgba(255,255,255,.95);
    background:#ffffff;
    box-shadow:0 3px 8px rgba(0,0,0,.18);
    flex-shrink:0;
}

.dfs-user-meta{
    line-height:1.15;
}

.dfs-user-name{
    color:#ffffff;
    font-weight:700;
    font-size:14px;
    max-width:220px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.dfs-user-role{
    color:#FFD700;
    font-weight:700;
    font-size:11px;
    letter-spacing:.5px;
}

.dfs-btn-logout{
    background:#FFD700;
    color:#1e293b;
    border:none;
    padding:8px 18px;
    border-radius:8px;
    font-weight:700;
    box-shadow:0 4px 12px rgba(0,0,0,.2);
    margin-right:22px;
}

.dfs-btn-logout:hover{
    opacity:.9;
}

/* ===== OWNER NOTIFICATION ===== */

.dfs-notif-dropdown-wrap{
    position:relative;
}

.dfs-notif-toggle{
    position:relative;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:46px;
    height:46px;
    border:none;
    border-radius:14px;
    background:rgba(255,255,255,0.14);
    border:1px solid rgba(255,255,255,0.18);
    box-shadow:0 4px 12px rgba(0,0,0,.12);
    color:#ffffff;
    font-size:22px;
    line-height:1;
}

.dfs-notif-toggle:hover,
.dfs-notif-toggle:focus{
    background:rgba(255,255,255,0.20);
    color:#ffffff;
}

.dfs-notif-badge{
    position:absolute;
    top:-5px;
    right:-5px;
    min-width:21px;
    height:21px;
    padding:0 6px;
    border-radius:999px;
    background:#ef4444;
    color:#ffffff;
    font-size:11px;
    font-weight:700;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 4px 10px rgba(0,0,0,.18);
    border:2px solid #1fb6d5;
}

.dfs-notif-menu{
    width:360px;
    max-width:92vw;
    border-radius:14px;
    border:1px solid rgba(0,0,0,.08);
    box-shadow:0 14px 34px rgba(0,0,0,.14);
    padding:0;
    overflow:hidden;
}

.dfs-notif-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding:12px 14px;
    background:#f8fafc;
    border-bottom:1px solid #e2e8f0;
}

.dfs-notif-title{
    font-size:14px;
    font-weight:800;
    color:#0f172a;
    margin:0;
}

.dfs-notif-count{
    font-size:11px;
    font-weight:700;
    color:#475569;
    background:#e2e8f0;
    border-radius:999px;
    padding:3px 8px;
}

.dfs-notif-list{
    max-height:380px;
    overflow:auto;
}

.dfs-notif-item{
    display:block;
    padding:12px 14px;
    text-decoration:none;
    color:#0f172a;
    border-bottom:1px solid #f1f5f9;
    background:#ffffff;
}

.dfs-notif-item:hover{
    background:#f8fafc;
    color:#0f172a;
}

.dfs-notif-item.is-unread{
    background:#eff6ff;
}

.dfs-notif-item.is-unread:hover{
    background:#dbeafe;
}

.dfs-notif-item-title{
    font-size:13px;
    font-weight:700;
    line-height:1.35;
    margin-bottom:4px;
    color:#0f172a;
}

.dfs-notif-item-message{
    font-size:12px;
    line-height:1.45;
    color:#475569;
    margin-bottom:5px;
}

.dfs-notif-item-time{
    font-size:11px;
    color:#64748b;
}

.dfs-notif-empty{
    padding:18px 14px;
    font-size:13px;
    color:#64748b;
    text-align:center;
    background:#ffffff;
}

/* ===== MENU BAR ===== */

.dfs-nav-shell{
    position:relative;
    z-index:50;
}

.dfs-nav .nav-link{
    border-radius:10px;
    padding:.45rem .75rem;
    transition:.12s ease;
    white-space:nowrap;
    font-weight:700;
}

.dfs-nav .nav-link:hover{
    transform:translateY(-1px);
    box-shadow:0 6px 18px rgba(0,0,0,.06);
}

.dfs-nav .nav-link.active{
    background:#2563eb !important;
    color:#ffffff !important;
    font-weight:700;
    text-decoration:none;
    box-shadow:0 8px 18px rgba(37,99,235,.22);
}

.dfs-nav-wrap{
    overflow:visible;
}

.dfs-nav .nav-link.disabled{
    opacity:.45;
    pointer-events:none;
}

.dfs-badge-soon{
    font-size:10px;
    padding:2px 6px;
    border-radius:999px;
    background:rgba(0,0,0,.06);
    color:rgba(0,0,0,.55);
    margin-left:6px;
}

/* warna menu per navbar */
.dfs-nav .nav-link.nav-dashboard{
    color:#64748b;
}
.dfs-nav .nav-link.nav-dashboard:hover{
    background:#f1f5f9;
    color:#475569;
}

.dfs-nav .nav-link.nav-income{
    color:#16a34a;
}
.dfs-nav .nav-link.nav-income:hover{
    background:#dcfce7;
    color:#15803d;
}

.dfs-nav .nav-link.nav-expense{
    color:#dc2626;
}
.dfs-nav .nav-link.nav-expense:hover{
    background:#fee2e2;
    color:#b91c1c;
}

.dfs-nav .nav-link.nav-inventory{
    color:#7c3aed;
}
.dfs-nav .nav-link.nav-inventory:hover{
    background:#ede9fe;
    color:#6d28d9;
}

.dfs-nav .nav-link.nav-warehouse{
    color:#0891b2;
}
.dfs-nav .nav-link.nav-warehouse:hover{
    background:#cffafe;
    color:#0e7490;
}

.dfs-nav .nav-link.nav-report{
    color:#ea580c;
}
.dfs-nav .nav-link.nav-report:hover{
    background:#ffedd5;
    color:#c2410c;
}

.dfs-nav .nav-link.nav-master-doctor{
    color:#0f766e;
}
.dfs-nav .nav-link.nav-master-doctor:hover{
    background:#ccfbf1;
    color:#115e59;
}

.dfs-nav .nav-link.nav-master-treatment{
    color:#a16207;
}
.dfs-nav .nav-link.nav-master-treatment:hover{
    background:#fef3c7;
    color:#854d0e;
}

.dfs-nav .nav-link.nav-master-user{
    color:#be185d;
}
.dfs-nav .nav-link.nav-master-user:hover{
    background:#fce7f3;
    color:#9d174d;
}

.dfs-nav .nav-link.active:hover{
    background:#2563eb !important;
    color:#ffffff !important;
}

/* dropdown menu navbar */
.dfs-nav .dropdown{
    position:relative;
}

.dfs-nav .dropdown-menu{
    border-radius:14px;
    border:1px solid rgba(0,0,0,.08);
    box-shadow:0 12px 30px rgba(0,0,0,.12);
    padding:8px;
    margin-top:8px;
    z-index:1080;
}

.dfs-nav .dropdown-item{
    border-radius:10px;
    padding:.55rem .8rem;
    font-weight:600;
}

.dfs-nav .dropdown-item:hover,
.dfs-nav .dropdown-item:focus{
    background:#f8fafc;
}

.dfs-nav .dropdown-item.active,
.dfs-nav .dropdown-item:active{
    background:#2563eb;
    color:#ffffff;
}

/* ===== DFS ACTIVE BUTTON STYLE (NON NAVBAR) ===== */

.btn.dfs-btn-active,
.btn.dfs-btn-active:focus,
.btn.dfs-btn-active:active,
.btn.active:not(.nav-link),
.btn.active:not(.nav-link):focus,
.btn.active:not(.nav-link):active{
    background-color:#FFC107 !important;
    border-color:#FFB300 !important;
    color:#212529 !important;
    font-weight:700;
    box-shadow:0 0 0 .1rem rgba(255, 193, 7, .18) !important;
}

.btn.dfs-btn-active:hover,
.btn.active:not(.nav-link):hover{
    background-color:#FFCA28 !important;
    border-color:#FFB300 !important;
    color:#212529 !important;
}

.btn.dfs-btn-active.btn-outline-secondary,
.btn.dfs-btn-active.btn-outline-primary,
.btn.dfs-btn-active.btn-outline-dark,
.btn.dfs-btn-active.btn-outline-success,
.btn.dfs-btn-active.btn-outline-danger,
.btn.active.btn-outline-secondary:not(.nav-link),
.btn.active.btn-outline-primary:not(.nav-link),
.btn.active.btn-outline-dark:not(.nav-link),
.btn.active.btn-outline-success:not(.nav-link),
.btn.active.btn-outline-danger:not(.nav-link){
    background-color:#FFC107 !important;
    border-color:#FFB300 !important;
    color:#212529 !important;
}

@media (max-width: 767.98px){
    .dfs-topbar{
        height:auto;
        padding:10px 0;
    }

    .dfs-logo-box{
        margin-left:10px;
        height:60px;
    }

    .dfs-clinic-logo{
        height:52px;
    }

    .dfs-top-actions{
        gap:8px;
    }

    .dfs-userbox{
        padding:6px 10px;
        border-radius:12px;
    }

    .dfs-user-photo{
        width:36px;
        height:36px;
    }

    .dfs-user-name{
        max-width:120px;
        font-size:13px;
    }

    .dfs-btn-logout{
        margin-right:10px;
        padding:7px 14px;
    }

    .dfs-nav-wrap{
        overflow-x:auto;
        overflow-y:visible;
        -webkit-overflow-scrolling:touch;
    }

    .dfs-notif-menu{
        width:92vw;
    }
}

</style>
</head>

<body>

@php
$setting = class_exists(\App\Models\Setting::class)
    ? \App\Models\Setting::query()->first()
    : null;

$logoUrl = $setting?->logo_url;
$bgUrl = $setting?->login_background_url;

$role = strtolower((string) (auth()->user()->role ?? ''));
$isOwner = $role === 'owner';
$isAdmin = $role === 'admin';
$isMitra = $role === 'dokter_mitra';

$isActive = function(string $pattern){
    return request()->is($pattern) ? 'active' : '';
};

$navLink = function(string $label, ?string $routeName, string $fallbackUrl, string $activePattern) use ($isActive){
    $has = $routeName ? \Illuminate\Support\Facades\Route::has($routeName) : false;

    $href = $has
        ? route($routeName)
        : url($fallbackUrl);

    $cls = 'nav-link '.$isActive($activePattern).($has ? '' : ' disabled');

    return [
        'href'  => $href,
        'cls'   => trim($cls),
        'soon'  => !$has,
        'label' => $label
    ];
};

$isIncomeMenuActive = request()->is('income*')
    || request()->is('other-income*')
    || request()->is('owner-private*');

$isInventoryMenuActive = request()->is('inventory*')
    || request()->is('inv/in*')
    || request()->is('inv/out*');

$ownerUnreadNotifications = collect();
$ownerUnreadNotificationsCount = 0;

if ($isOwner && class_exists(\App\Models\Notification::class)) {
    $ownerUnreadNotifications = \App\Models\Notification::query()
        ->where('user_id', auth()->id())
        ->latest()
        ->take(10)
        ->get();

    $ownerUnreadNotificationsCount = \App\Models\Notification::query()
        ->where('user_id', auth()->id())
        ->whereNull('read_at')
        ->count();
}
@endphp

<div class="dfs-page-bg"
     style="{{ $bgUrl ? '--dfs-bg-image:url(\''.$bgUrl.'\')' : '--dfs-bg-image:none' }}">
    <div class="dfs-page-overlay">

        {{-- TOP BAR --}}
        <nav class="dfs-topbar">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <div class="dfs-brand">
                    <div class="dfs-logo-box">
                        @if($logoUrl)
                            <img
                                src="{{ $logoUrl }}"
                                class="dfs-clinic-logo"
                                alt="Logo Klinik">
                        @endif
                    </div>
                </div>

                <div class="dfs-top-actions">
                    @auth

                        @if($isOwner)
                            <div class="dropdown dfs-notif-dropdown-wrap">
                                <button
                                    class="dfs-notif-toggle"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside"
                                    aria-expanded="false"
                                    title="Notifikasi Dokter Mitra">
                                    🔔
                                    @if($ownerUnreadNotificationsCount > 0)
                                        <span class="dfs-notif-badge">
                                            {{ $ownerUnreadNotificationsCount > 99 ? '99+' : $ownerUnreadNotificationsCount }}
                                        </span>
                                    @endif
                                </button>

                                <div class="dropdown-menu dropdown-menu-end dfs-notif-menu">
                                    <div class="dfs-notif-header">
                                        <div class="dfs-notif-title">Notifikasi Dokter Mitra</div>
                                        <div class="dfs-notif-count">
                                            {{ $ownerUnreadNotificationsCount }} belum dibaca
                                        </div>
                                    </div>

                                    <div class="dfs-notif-list">
                                        @forelse($ownerUnreadNotifications as $notif)
                                            @php
                                                $isUnread = empty($notif->read_at);
                                                $notifTitle = trim((string) ($notif->title ?? 'Catatan koreksi baru dari dokter mitra'));
                                                $notifMessage = trim((string) ($notif->message ?? 'Klik untuk membuka transaksi terkait.'));
                                            @endphp

                                            <a
                                                href="{{ route('doctor_mitra.notifications.open', $notif->id) }}"
                                                class="dfs-notif-item {{ $isUnread ? 'is-unread' : '' }}">
                                                <div class="dfs-notif-item-title">
                                                    {{ $notifTitle }}
                                                </div>
                                                <div class="dfs-notif-item-message">
                                                    {{ \Illuminate\Support\Str::limit($notifMessage, 120) }}
                                                </div>
                                                <div class="dfs-notif-item-time">
                                                    {{ optional($notif->created_at)->translatedFormat('d M Y H:i') }}
                                                </div>
                                            </a>
                                        @empty
                                            <div class="dfs-notif-empty">
                                                Belum ada notifikasi dari dokter mitra.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="dfs-userbox">
                            <img
                                src="{{ auth()->user()->photo_url }}"
                                class="dfs-user-photo"
                                alt="Foto Akun">

                            <div class="dfs-user-meta">
                                <div class="dfs-user-name">
                                    {{ auth()->user()->name }}
                                </div>
                                <div class="dfs-user-role">
                                    {{ auth()->user()->role_label }}
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dfs-btn-logout">
                                Logout
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </nav>

        {{-- MENU BAR --}}
        <div class="bg-white border-bottom dfs-nav-shell">
            <div class="container-fluid dfs-nav-wrap">
                <ul class="nav dfs-nav nav-pills flex-nowrap gap-1 py-2">

                    @if($isMitra)

                        <li class="nav-item">
                            <a class="nav-link nav-dashboard {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                               href="{{ route('dashboard') }}">
                                Dashboard
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link nav-income {{ request()->routeIs('mitra.pasien') ? 'active' : '' }}"
                               href="{{ route('mitra.pasien') }}">
                                Data Pasien
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link nav-income {{ request()->routeIs('mitra.transaksi') ? 'active' : '' }}"
                               href="{{ route('mitra.transaksi') }}">
                                Transaksi
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link nav-report {{ request()->routeIs('mitra.fee') ? 'active' : '' }}"
                               href="{{ route('mitra.fee') }}">
                                Fee Saya
                            </a>
                        </li>

                    @else

                        <li class="nav-item">
                            <a class="nav-link nav-dashboard {{ $isActive('dashboard') }}" href="{{ url('/dashboard') }}">Dashboard</a>
                        </li>

                        <li class="nav-item dropdown">
                            <a
                                class="nav-link nav-income dropdown-toggle {{ $isIncomeMenuActive ? 'active' : '' }}"
                                href="#"
                                role="button"
                                data-bs-toggle="dropdown"
                                data-bs-display="static"
                                aria-expanded="false">
                                Pemasukan
                            </a>

                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item {{ request()->is('income*') ? 'active' : '' }}" href="{{ url('/income') }}">
                                        Pemasukan Pasien
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item {{ request()->is('other-income*') ? 'active' : '' }}" href="{{ url('/other-income') }}">
                                        Pemasukan Lain-lain
                                    </a>
                                </li>
                                @if($isOwner)
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item {{ request()->is('owner-private*') ? 'active' : '' }}" href="{{ url('/owner-private') }}">
                                            Pemasukan Private
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link nav-expense {{ $isActive('expenses*') }}" href="{{ url('/expenses') }}">Pengeluaran</a>
                        </li>

                        <li class="nav-item dropdown">
                            <a
                                class="nav-link nav-inventory dropdown-toggle {{ $isInventoryMenuActive ? 'active' : '' }}"
                                href="#"
                                role="button"
                                data-bs-toggle="dropdown"
                                data-bs-display="static"
                                aria-expanded="false">
                                Inventory
                            </a>

                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item {{
                                        (request()->routeIs('inventory.panel') && request('tab', 'items') === 'items')
                                        || request()->routeIs('inventory.items.*')
                                            ? 'active' : ''
                                    }}"
                                       href="{{ \Illuminate\Support\Facades\Route::has('inventory.panel') ? route('inventory.panel', ['tab' => 'items']) : url('/inventory?tab=items') }}">
                                        Data Item
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item {{
                                        (request()->routeIs('inventory.panel') && request('tab') === 'in')
                                        || (request()->routeIs('inventory.movements.*') && request()->route('type') === 'in')
                                            ? 'active' : ''
                                    }}"
                                       href="{{ \Illuminate\Support\Facades\Route::has('inventory.panel') ? route('inventory.panel', ['tab' => 'in']) : url('/inventory?tab=in') }}">
                                        Inventori Masuk
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item {{
                                        (request()->routeIs('inventory.panel') && request('tab') === 'out')
                                        || (request()->routeIs('inventory.movements.*') && request()->route('type') === 'out')
                                            ? 'active' : ''
                                    }}"
                                       href="{{ \Illuminate\Support\Facades\Route::has('inventory.panel') ? route('inventory.panel', ['tab' => 'out']) : url('/inventory?tab=out') }}">
                                        Inventori Keluar
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item {{
                                        (request()->routeIs('inventory.panel') && request('tab') === 'stock')
                                        || request()->routeIs('inventory.stok')
                                            ? 'active' : ''
                                    }}"
                                       href="{{ \Illuminate\Support\Facades\Route::has('inventory.panel') ? route('inventory.panel', ['tab' => 'stock']) : url('/inventory?tab=stock') }}">
                                        Stok Inventory
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item {{ request()->is('inventory') && !request()->has('tab') ? 'active' : '' }}"
                                       href="{{ \Illuminate\Support\Facades\Route::has('inventory.panel') ? route('inventory.panel') : url('/inventory') }}">
                                        Panel Inventory
                                    </a>
                                </li>
                            </ul>
                        </li>

                        @if($isOwner)
                            @php($gudang = $navLink('Gudang','warehouse.panel','/warehouse','warehouse*'))
                            <li class="nav-item">
                                <a class="{{ $gudang['cls'] }} nav-warehouse" href="{{ $gudang['href'] }}">
                                    {{ $gudang['label'] }}
                                    @if($gudang['soon']) <span class="dfs-badge-soon">Soon</span>@endif
                                </a>
                            </li>

                            @php($lap = $navLink('Laporan','reports.laba-rugi','/reports/laba-rugi','reports*'))
                            <li class="nav-item">
                                <a class="{{ $lap['cls'] }} nav-report" href="{{ $lap['href'] }}">
                                    {{ $lap['label'] }}
                                    @if($lap['soon']) <span class="dfs-badge-soon">Soon</span>@endif
                                </a>
                            </li>

                            @php($md = $navLink('Master Dokter','master.doctors.index','/master/doctors','master/doctors*'))
                            <li class="nav-item">
                                <a class="{{ $md['cls'] }} nav-master-doctor" href="{{ $md['href'] }}">
                                    {{ $md['label'] }}
                                    @if($md['soon']) <span class="dfs-badge-soon">Soon</span>@endif
                                </a>
                            </li>

                            @php($mt = $navLink('Master Tindakan','master.treatments.index','/master/treatments','master/treatments*'))
                            <li class="nav-item">
                                <a class="{{ $mt['cls'] }} nav-master-treatment" href="{{ $mt['href'] }}">
                                    {{ $mt['label'] }}
                                    @if($mt['soon']) <span class="dfs-badge-soon">Soon</span>@endif
                                </a>
                            </li>

                            @php($mu = $navLink('Master User','master.users.index','/master/users','master/users*'))
                            <li class="nav-item">
                                <a class="{{ $mu['cls'] }} nav-master-user" href="{{ $mu['href'] }}">
                                    {{ $mu['label'] }}
                                    @if($mu['soon']) <span class="dfs-badge-soon">Soon</span>@endif
                                </a>
                            </li>
                        @elseif($isAdmin)
                            @php($kas = $navLink('Kas Harian','reports.daily_cash.index','/reports/kas-harian','reports/kas-harian*'))
                            <li class="nav-item">
                                <a class="{{ $kas['cls'] }} nav-report" href="{{ $kas['href'] }}">
                                    {{ $kas['label'] }}
                                    @if($kas['soon']) <span class="dfs-badge-soon">Soon</span>@endif
                                </a>
                            </li>
                        @endif

                    @endif

                </ul>
            </div>
        </div>

        <div class="container py-4">
            @yield('content')
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>