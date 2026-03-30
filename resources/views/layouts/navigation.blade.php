<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DentalFinance</title>

<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

@php
  // Logo aman: taruh file di public/assets/logo.png (atau ganti nama file jika beda)
  $logoPath = public_path('assets/logo.png');
  $hasLogo = file_exists($logoPath);

  $role = strtolower((string) (auth()->user()->role ?? ''));
  $isOwner = $role === 'owner';

  // helper: class tombol aktif
  $btnClass = function(bool $active) {
      return $active ? 'btn btn-primary btn-sm' : 'btn btn-outline-primary btn-sm';
  };

  // helper: link aman kalau route belum ada
  $safeHref = function(string $routeName, array $params = []) {
      return \Illuminate\Support\Facades\Route::has($routeName)
          ? route($routeName, $params)
          : '#';
  };

  $disabledIfMissing = function(string $routeName) {
      return \Illuminate\Support\Facades\Route::has($routeName) ? '' : ' disabled';
  };
@endphp

<nav class="navbar navbar-dark bg-info">
  <div class="container-fluid">
    <a class="navbar-brand mb-0 h1 d-flex align-items-center gap-2 text-decoration-none" href="{{ $safeHref('dashboard') }}">
      @if($hasLogo)
        <img src="{{ asset('assets/logo.png') }}" alt="Logo" style="height:28px;width:auto;">
      @endif
      <span>DentalFinance</span>
    </a>

    <div class="d-flex align-items-center gap-2">
      <span class="text-white small">
        @auth
          {{ auth()->user()->name }}
        @endauth
      </span>

      @auth
        <form method="POST" action="{{ route('logout') }}" class="mb-0">
          @csrf
          <button type="submit" class="btn btn-sm btn-outline-light">Logout</button>
        </form>
      @endauth
    </div>
  </div>
</nav>

<div class="bg-light border-bottom">
  <div class="container py-2 d-flex flex-wrap gap-2">

    {{-- DASHBOARD --}}
    <a href="{{ $safeHref('dashboard') }}"
       class="{{ $btnClass(request()->routeIs('dashboard')) }}{{ $disabledIfMissing('dashboard') }}">
      Dashboard
    </a>

    {{-- PEMASUKAN --}}
    <a href="{{ $safeHref('income.index') }}"
       class="{{ $btnClass(request()->routeIs('income.*')) }}{{ $disabledIfMissing('income.index') }}">
      Pemasukan
    </a>

    {{-- PENGELUARAN --}}
    @php
      $expenseRoute = \Illuminate\Support\Facades\Route::has('expense.index') ? 'expense.index' : 'expenses.index';
      $expenseActive = request()->routeIs('expense.*') || request()->routeIs('expenses.*');
    @endphp
    <a href="{{ $safeHref($expenseRoute) }}"
       class="{{ $btnClass($expenseActive) }}{{ $disabledIfMissing($expenseRoute) }}">
      Pengeluaran
    </a>

    {{-- BARANG INVENTARIS (Inventory Items) --}}
    <a href="{{ $safeHref('inventory.items.index') }}"
       class="{{ $btnClass(request()->routeIs('inventory.items.*')) }}{{ $disabledIfMissing('inventory.items.index') }}">
      Barang Inventaris
    </a>

    {{-- BARANG MASUK --}}
    <a href="{{ $safeHref('inv.in.create') }}"
       class="{{ $btnClass(request()->routeIs('inv.in.*')) }}{{ $disabledIfMissing('inv.in.create') }}">
      Barang Masuk
    </a>

    {{-- BARANG KELUAR --}}
    <a href="{{ $safeHref('inv.out.create') }}"
       class="{{ $btnClass(request()->routeIs('inv.out.*')) }}{{ $disabledIfMissing('inv.out.create') }}">
      Barang Keluar
    </a>

    {{-- STOK --}}
    <a href="{{ $safeHref('inventory.stok') }}"
       class="{{ $btnClass(request()->routeIs('inventory.stok')) }}{{ $disabledIfMissing('inventory.stok') }}">
      Stok
    </a>

    {{-- OWNER FINANCE (OWNER ONLY) --}}
    @if($isOwner)
      <a href="{{ $safeHref('owner_finance.index') }}"
         class="{{ $btnClass(request()->routeIs('owner_finance.*')) }}{{ $disabledIfMissing('owner_finance.index') }}">
        Owner Finance
      </a>
    @endif

    {{-- PRIVATE OWNER (OWNER ONLY) --}}
    @if($isOwner)
      <a href="{{ $safeHref('owner_private.index') }}"
         class="{{ $btnClass(request()->routeIs('owner_private.*')) }}{{ $disabledIfMissing('owner_private.index') }}">
        Private Owner
      </a>
    @endif

    {{-- LAPORAN (dropdown) --}}
    @php
      $reportsActive = request()->routeIs('reports.*');
      $reportsBtnClass = $reportsActive ? 'btn btn-primary btn-sm dropdown-toggle' : 'btn btn-outline-primary btn-sm dropdown-toggle';
      $reportsDisabled = \Illuminate\Support\Facades\Route::has('reports.laba-rugi')
                          || \Illuminate\Support\Facades\Route::has('reports.fee_dokter.index')
                          || \Illuminate\Support\Facades\Route::has('reports.daily_cash.index')
                        ? '' : ' disabled';
    @endphp

    <div class="btn-group">
      <button type="button" class="{{ $reportsBtnClass }}{{ $reportsDisabled }}" data-bs-toggle="dropdown" aria-expanded="false">
        Laporan
      </button>
      <ul class="dropdown-menu">
        <li>
          <a class="dropdown-item {{ request()->routeIs('reports.daily_cash.*') ? 'active' : '' }}"
             href="{{ $safeHref('reports.daily_cash.index') }}">
            Kas Harian
          </a>
        </li>
        <li>
          <a class="dropdown-item {{ request()->routeIs('reports.fee_dokter.*') ? 'active' : '' }}"
             href="{{ $safeHref('reports.fee_dokter.index') }}">
            Fee Dokter
          </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <a class="dropdown-item {{ request()->routeIs('reports.laba-rugi') ? 'active' : '' }}"
             href="{{ $safeHref('reports.laba-rugi') }}">
            Laba Rugi
          </a>
        </li>
      </ul>
    </div>

    {{-- MASTER DOKTER --}}
    <a href="{{ $safeHref('master.doctors.index') }}"
       class="{{ $btnClass(request()->routeIs('master.doctors.*')) }}{{ $disabledIfMissing('master.doctors.index') }}">
      Master Dokter
    </a>

    {{-- MASTER TINDAKAN --}}
    <a href="{{ $safeHref('master.treatments.index') }}"
       class="{{ $btnClass(request()->routeIs('master.treatments.*')) }}{{ $disabledIfMissing('master.treatments.index') }}">
      Master Tindakan
    </a>

    {{-- MASTER USER --}}
    <a href="{{ $safeHref('master.users.index') }}"
       class="{{ $btnClass(request()->routeIs('master.users.*')) }}{{ $disabledIfMissing('master.users.index') }}">
      Master User
    </a>

  </div>
</div>

<div class="container mt-4">
  @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>