@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Dashboard Dokter Mitra</h2>
        <div class="text-muted">
            Ringkasan data dokter mitra berdasarkan data dokter yang terhubung ke akun ini.
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Filter</label>
                        <select name="filter" class="form-select" onchange="this.form.submit()">
                            <option value="daily" {{ $filterType === 'daily' ? 'selected' : '' }}>Harian</option>
                            <option value="weekly" {{ $filterType === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                            <option value="monthly" {{ $filterType === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                        </select>
                    </div>

                    <div class="col-md-4" id="daily-filter" style="{{ $filterType === 'daily' ? '' : 'display:none;' }}">
                        <label class="form-label fw-semibold">Tanggal</label>
                        <input type="date" name="date" value="{{ $date }}" class="form-control">
                    </div>

                    <div class="col-md-2" id="monthly-filter-month" style="{{ $filterType === 'monthly' ? '' : 'display:none;' }}">
                        <label class="form-label fw-semibold">Bulan</label>
                        <select name="month" class="form-select">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-md-2" id="monthly-filter-year" style="{{ $filterType === 'monthly' ? '' : 'display:none;' }}">
                        <label class="form-label fw-semibold">Tahun</label>
                        <select name="year" class="form-select">
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                    </div>
                </div>

                <div class="mt-3 text-muted small">
                    @if($filterType === 'daily')
                        Periode aktif: <strong>{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</strong>
                    @elseif($filterType === 'weekly')
                        Periode aktif: <strong>Minggu ini</strong>
                    @else
                        Periode aktif: <strong>{{ \Carbon\Carbon::create()->month((int) $month)->translatedFormat('F') }} {{ $year }}</strong>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #0d6efd !important;">
                <div class="card-body">
                    <div class="text-muted small">Total Pasien</div>
                    <div class="fs-2 fw-bold">{{ number_format($totalPatients, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #6f42c1 !important;">
                <div class="card-body">
                    <div class="text-muted small">Total Tindakan / Transaksi</div>
                    <div class="fs-2 fw-bold">{{ number_format($totalTransactions, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #198754 !important;">
                <div class="card-body">
                    <div class="text-muted small">Total Pembayaran</div>
                    <div class="fs-5 fw-bold">{{ format_rupiah((float) $totalPayment) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #f59e0b !important;">
                <div class="card-body">
                    <div class="text-muted small">Total Fee</div>
                    <div class="fs-5 fw-bold">{{ format_rupiah((float) $totalFee) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">
            Informasi Akses Dokter Mitra
        </div>
        <div class="card-body">
            <div class="mb-2">Akun dokter mitra hanya dapat:</div>
            <ul class="mb-0">
                <li>melihat data milik dokter yang terhubung ke akun ini</li>
                <li>melihat ringkasan pasien, transaksi, pembayaran, dan fee</li>
                <li>memberikan catatan koreksi pada transaksi terkait</li>
            </ul>

            <hr>

            <div class="text-muted small">
                Dokter mitra tidak dapat mengubah transaksi, pembayaran, fee, maupun data pasien.
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const filterSelect = document.querySelector('select[name="filter"]');
        const dailyFilter = document.getElementById('daily-filter');
        const monthlyMonth = document.getElementById('monthly-filter-month');
        const monthlyYear = document.getElementById('monthly-filter-year');

        function syncFilterVisibility() {
            const value = filterSelect ? filterSelect.value : 'daily';

            if (dailyFilter) {
                dailyFilter.style.display = value === 'daily' ? '' : 'none';
            }

            if (monthlyMonth) {
                monthlyMonth.style.display = value === 'monthly' ? '' : 'none';
            }

            if (monthlyYear) {
                monthlyYear.style.display = value === 'monthly' ? '' : 'none';
            }
        }

        if (filterSelect) {
            filterSelect.addEventListener('change', syncFilterVisibility);
            syncFilterVisibility();
        }
    })();
</script>
@endsection
