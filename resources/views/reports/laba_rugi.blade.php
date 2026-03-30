@extends('layouts.app')

@section('content')

<style>
/* =========================
   REPORT FILTER / SUB NAVBAR
========================= */
.btn-report-nav{
    border-radius:10px;
    font-weight:700;
    transition:all .15s ease;
}

/* warna default */
.btn-report-today{
    border-color:#f59e0b;
    color:#b45309;
    background:#fff;
}
.btn-report-today:hover{
    background:#fef3c7;
    color:#92400e;
}

.btn-report-month{
    border-color:#6366f1;
    color:#4338ca;
    background:#fff;
}
.btn-report-month:hover{
    background:#e0e7ff;
    color:#3730a3;
}

.btn-report-lastmonth{
    border-color:#8b5cf6;
    color:#6d28d9;
    background:#fff;
}
.btn-report-lastmonth:hover{
    background:#ede9fe;
    color:#5b21b6;
}

.btn-report-reset{
    border-color:#ef4444;
    color:#b91c1c;
    background:#fff;
}
.btn-report-reset:hover{
    background:#fee2e2;
    color:#991b1b;
}

.btn-report-kas{
    border-color:#0ea5e9;
    color:#0369a1;
    background:#fff;
}
.btn-report-kas:hover{
    background:#e0f2fe;
    color:#075985;
}

.btn-report-fee{
    border-color:#14b8a6;
    color:#0f766e;
    background:#fff;
}
.btn-report-fee:hover{
    background:#ccfbf1;
    color:#115e59;
}

.btn-report-owner{
    border-color:#2563eb;
    color:#1d4ed8;
    background:#fff;
}
.btn-report-owner:hover{
    background:#dbeafe;
    color:#1e40af;
}

.btn-report-private{
    border-color:#374151;
    color:#374151;
    background:#fff;
}
.btn-report-private:hover{
    background:#f3f4f6;
    color:#111827;
}

/* =========================
   STICKY FILTER BAR + GLASS
========================= */
.report-filter-wrapper{
    margin-bottom:14px;
}

.report-filter-bar{
    position:sticky;
    top:70px;
    z-index:100;
    padding:12px 14px;
    border-radius:16px;
    border:1px solid rgba(255,255,255,.55);
    background:rgba(255,255,255,.72);
    backdrop-filter:blur(10px);
    -webkit-backdrop-filter:blur(10px);
    box-shadow:0 8px 22px rgba(0,0,0,.08);
    transition:all .18s ease;
}

.report-filter-bar.scrolled{
    background:rgba(255,255,255,.82);
    border-color:rgba(255,255,255,.75);
    box-shadow:0 14px 34px rgba(15,23,42,.16);
    backdrop-filter:blur(14px);
    -webkit-backdrop-filter:blur(14px);
}

.report-filter-divider{
    height:1px;
    background:linear-gradient(
        to right,
        rgba(148,163,184,.15),
        rgba(148,163,184,.5),
        rgba(148,163,184,.15)
    );
    margin:10px 0 12px;
}

@media (max-width: 767.98px){
    .report-filter-bar{
        top:64px;
        padding:10px 10px;
        border-radius:14px;
    }
}
</style>

@php
    $today = now()->toDateString();

    $rupiah = function ($value) {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    };

    $nominalClass = function ($value, $default = 'fw-bold') {
        return (float) $value < 0 ? 'text-danger fw-bold' : $default;
    };

    $formatTanggal = function ($value) {
        if (!$value) {
            return '-';
        }

        return \Carbon\Carbon::parse($value)->format('d-m-Y');
    };

    $payload = $chartData ?? [
        'labels'  => [],
        'series'  => [],
        'metric'  => 'qty',
        'enabled' => false,
        'note'    => null,
    ];

    $periodStart = request('start', $start ?? $today);
    $periodEnd = request('end', $end ?? $today);
    $metricSelected = request('metric', $payload['metric'] ?? 'qty');

    $privateOwnerIncome = (float) ($privateOwnerIncome ?? 0);
    $privateOwnerExpense = (float) ($privateOwnerExpense ?? 0);
    $totalClinicIncome = (float) ($totalClinicIncome ?? 0);
    $operationalExpense = (float) ($operationalExpense ?? 0);
    $totalExpense = (float) ($totalExpense ?? 0);
    $netClinicCashflow = (float) ($netClinicCashflow ?? 0);

    $isSingleDayPeriod = $periodStart === $periodEnd;
    $currentRoute = request()->route()?->getName();
    $isTodayMode = ($periodStart === $today && $periodEnd === $today);
    $isMonthlyMode = ($periodStart === now()->startOfMonth()->toDateString() && $periodEnd === now()->endOfMonth()->toDateString());
    $isLastMonthMode = (
        $periodStart === now()->subMonth()->startOfMonth()->toDateString()
        && $periodEnd === now()->subMonth()->endOfMonth()->toDateString()
    );

    $exportParams = [
        'start' => $periodStart,
        'end' => $periodEnd,
        'metric' => $metricSelected,
    ];

    $periodeLabel = $isSingleDayPeriod
        ? 'Periode: ' . $formatTanggal($periodStart)
        : 'Periode: ' . $formatTanggal($periodStart) . ' s/d ' . $formatTanggal($periodEnd);
@endphp

<div class="container py-4">

    <div class="d-flex flex-column gap-2 mb-3">
        <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
            <div>
                <h4 class="mb-1">Laporan Laba Rugi</h4>
                <div class="text-success small fw-bold">
                    Default tampilan: data hari berjalan
                </div>
                <div class="text-danger small fw-bold">
                    ✅ OWNER Only • Hanya transaksi PAID • Pendapatan Prosto/Retainer/Dental Laboratory diakui saat status sudah memenuhi syarat
                </div>
                <div class="text-muted small">
                    {{ $periodeLabel }}
                </div>
            </div>

            <div class="report-filter-wrapper w-100" style="max-width: 760px;">
                <div id="reportFilterBar" class="report-filter-bar">
                    <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-outline-secondary btn-sm btn-report-nav btn-report-today {{ $isTodayMode ? 'dfs-btn-active' : '' }}"
                           href="{{ route('reports.laba-rugi', [
                                'start' => $today,
                                'end' => $today,
                                'metric' => $metricSelected
                           ]) }}">
                            Hari Ini
                        </a>

                        <a class="btn btn-outline-secondary btn-sm btn-report-nav btn-report-month {{ $isMonthlyMode ? 'dfs-btn-active' : '' }}"
                           href="{{ route('reports.laba-rugi', [
                                'start' => now()->startOfMonth()->toDateString(),
                                'end' => now()->endOfMonth()->toDateString(),
                                'metric' => $metricSelected
                           ]) }}">
                            Bulan Ini
                        </a>

                        <a class="btn btn-outline-secondary btn-sm btn-report-nav btn-report-lastmonth {{ $isLastMonthMode ? 'dfs-btn-active' : '' }}"
                           href="{{ route('reports.laba-rugi', [
                                'start' => now()->subMonth()->startOfMonth()->toDateString(),
                                'end' => now()->subMonth()->endOfMonth()->toDateString(),
                                'metric' => $metricSelected
                           ]) }}">
                            Bulan Lalu
                        </a>

                        <a class="btn btn-outline-secondary btn-sm btn-report-nav btn-report-reset {{ $isTodayMode ? 'dfs-btn-active' : '' }}"
                           href="{{ route('reports.laba-rugi', [
                                'start' => $today,
                                'end' => $today,
                                'metric' => $metricSelected
                           ]) }}">
                            Reset
                        </a>

                        <a class="btn btn-outline-secondary btn-sm btn-report-nav btn-report-kas {{ $currentRoute === 'reports.daily_cash.index' ? 'dfs-btn-active' : '' }}"
                           href="{{ route('reports.daily_cash.index', [
                                'start' => $periodStart,
                                'end' => $periodEnd
                           ]) }}">
                            Kas Harian
                        </a>

                        <a class="btn btn-outline-secondary btn-sm btn-report-nav btn-report-fee {{ $currentRoute === 'reports.fee_dokter.index' ? 'dfs-btn-active' : '' }}"
                           href="{{ route('reports.fee_dokter.index', [
                                'start' => $periodStart,
                                'end' => $periodEnd
                           ]) }}">
                            Fee Dokter
                        </a>

                        <a class="btn btn-outline-primary btn-sm btn-report-nav btn-report-owner {{ str_starts_with($currentRoute ?? '', 'owner_finance.') ? 'dfs-btn-active' : '' }}"
                           href="{{ route('owner_finance.index') }}">
                            Owner Finance
                        </a>

                        <a class="btn btn-outline-dark btn-sm btn-report-nav btn-report-private {{ str_starts_with($currentRoute ?? '', 'owner_private.') ? 'dfs-btn-active' : '' }}"
                           href="{{ route('owner_private.index') }}">
                            Private Owner
                        </a>
                    </div>

                    <div class="report-filter-divider"></div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('reports.laba-rugi.export.pdf', $exportParams) }}"
                           class="btn btn-danger btn-sm">
                            Export PDF
                        </a>

                        <a href="{{ route('reports.laba-rugi.export.excel', $exportParams) }}"
                           class="btn btn-success btn-sm">
                            Export Excel
                        </a>

                        <form method="GET" action="{{ route('reports.laba-rugi') }}" class="d-flex align-items-end gap-2 flex-wrap ms-lg-auto">
                            <div>
                                <label class="form-label mb-1 small text-muted">Mulai</label>
                                <input type="date" name="start" value="{{ $periodStart }}" class="form-control form-control-sm">
                            </div>

                            <div>
                                <label class="form-label mb-1 small text-muted">Sampai</label>
                                <input type="date" name="end" value="{{ $periodEnd }}" class="form-control form-control-sm">
                            </div>

                            <div>
                                <label class="form-label mb-1 small text-muted">Grafik</label>
                                <select name="metric" class="form-select form-select-sm">
                                    <option value="qty" @selected($metricSelected === 'qty')>Trend Tindakan (Qty)</option>
                                    <option value="subtotal" @selected($metricSelected === 'subtotal')>Trend Omzet Tindakan</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-dark btn-sm">
                                Terapkan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-success">
                <div class="card-body">
                    <div class="text-muted small">Total Pendapatan Klinik</div>
                    <div class="fs-4 {{ $nominalClass($totalClinicIncome, 'fw-bold text-success') }}">
                        {{ $rupiah($totalClinicIncome) }}
                    </div>
                    <div class="small text-muted mt-1">
                        Operasional + owner finance diakui + private owner masuk
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-danger">
                <div class="card-body">
                    <div class="text-muted small">Total Pengeluaran Klinik</div>
                    <div class="fs-4 {{ $nominalClass(-1 * $totalExpense, 'fw-bold text-danger') }}">
                        {{ $rupiah($totalExpense) }}
                    </div>
                    <div class="small text-muted mt-1">
                        Pengeluaran operasional + private owner keluar
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-primary">
                <div class="card-body">
                    <div class="text-muted small">Net Arus Kas Klinik</div>
                    <div class="fs-4 {{ $nominalClass($netClinicCashflow, 'fw-bold text-primary') }}">
                        {{ $rupiah($netClinicCashflow) }}
                    </div>
                    <div class="small text-muted mt-1">
                        Total pendapatan klinik - total pengeluaran klinik
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Pendapatan Kotor Klinik</div>
                    <div class="fs-5 {{ $nominalClass($grossIncome ?? 0) }}">{{ $rupiah($grossIncome ?? 0) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Fee Dokter</div>
                    <div class="fs-5 {{ $nominalClass($doctorFee ?? 0) }}">{{ $rupiah($doctorFee ?? 0) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Pendapatan Bersih Klinik</div>
                    <div class="fs-5 {{ $nominalClass($netClinicIncome ?? 0) }}">{{ $rupiah($netClinicIncome ?? 0) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Laba Bersih Operasional Klinik</div>
                    <div class="fs-5 {{ $nominalClass($netProfit ?? 0) }}">{{ $rupiah($netProfit ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <div class="text-muted small">Laba Bersih Operasional Klinik</div>
                    <div class="fs-4 {{ $nominalClass($netProfit ?? 0) }}">{{ $rupiah($netProfit ?? 0) }}</div>
                </div>
                <div class="text-muted small">
                    Rumus: (Pendapatan Kotor Klinik - Fee Dokter) - Pengeluaran Operasional Klinik
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light fw-bold">
            Breakdown Pendapatan Klinik
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Pendapatan Reguler Non Owner Finance</div>
                        <div class="fs-5 {{ $nominalClass($grossIncomeRegular ?? 0) }}">{{ $rupiah($grossIncomeRegular ?? 0) }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Pendapatan Prosto / Retainer Diakui</div>
                        <div class="fs-5 {{ $nominalClass($recognizedProsthoRetainerIncome ?? 0) }}">{{ $rupiah($recognizedProsthoRetainerIncome ?? 0) }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Pendapatan Klinik Dental Laboratory</div>
                        <div class="fs-5 {{ $nominalClass($recognizedDentalLaboratoryIncome ?? 0, 'fw-bold text-success') }}">{{ $rupiah($recognizedDentalLaboratoryIncome ?? 0) }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Pendapatan Private Owner</div>
                        <div class="fs-5 {{ $nominalClass($privateOwnerIncome, 'fw-bold text-success') }}">{{ $rupiah($privateOwnerIncome) }}</div>
                    </div>
                </div>
            </div>

            <div class="text-muted small mt-3">
                Total pendapatan klinik pada periode ini:
                <span class="{{ $nominalClass($totalClinicIncome, 'fw-semibold') }}">
                    {{ $rupiah($totalClinicIncome) }}
                </span>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light fw-bold">
            Breakdown Pengeluaran Klinik
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Pengeluaran Operasional Klinik</div>
                        <div class="fs-5 {{ $nominalClass(-1 * $operationalExpense, 'fw-bold text-danger') }}">
                            {{ $rupiah($operationalExpense) }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Pengeluaran Private Owner</div>
                        <div class="fs-5 {{ $nominalClass(-1 * $privateOwnerExpense, 'fw-bold text-danger') }}">
                            {{ $rupiah($privateOwnerExpense) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-muted small mt-3">
                Total pengeluaran klinik pada periode ini:
                <span class="{{ $nominalClass(-1 * $totalExpense, 'fw-semibold text-danger') }}">
                    {{ $rupiah($totalExpense) }}
                </span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-success">
                <div class="card-body">
                    <div class="text-muted small">Mutasi Owner Masuk</div>
                    <div class="fs-5 {{ $nominalClass($ownerMutationIncome ?? 0, 'fw-bold text-success') }}">{{ $rupiah($ownerMutationIncome ?? 0) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-danger">
                <div class="card-body">
                    <div class="text-muted small">Mutasi Owner Keluar</div>
                    <div class="fs-5 {{ $nominalClass(-1 * (float) ($ownerMutationExpense ?? 0), 'fw-bold text-danger') }}">{{ $rupiah($ownerMutationExpense ?? 0) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-primary">
                <div class="card-body">
                    <div class="text-muted small">Arus Bersih Mutasi Owner</div>
                    <div class="fs-5 {{ $nominalClass($ownerNetCashflow ?? 0, 'fw-bold text-primary') }}">{{ $rupiah($ownerNetCashflow ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info mb-3">
        <div class="fw-semibold mb-1">Catatan Laporan</div>
        <div class="small">
            Nilai <b>Laba Bersih Operasional Klinik</b> dihitung dari transaksi klinik reguler ditambah <b>pendapatan klinik Prosto/Retainer</b> yang sudah diakui dan <b>pendapatan klinik Dental Laboratory</b> yang sudah diakui pada periode terpilih, lalu dikurangi <b>fee dokter</b> dan <b>pengeluaran operasional klinik</b>.
            <br>
            <b>Private Owner</b> tetap ditampilkan sebagai bagian dari <b>arus kas klinik</b>, sehingga owner bisa membaca total pendapatan dan total pengeluaran klinik secara utuh tanpa menghilangkan pemisahan sumber transaksi.
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="fw-semibold">Grafik Tren per Kategori Tindakan</div>
                <div class="text-muted small">Top 6 kategori + Lainnya</div>
            </div>

            @if(!($payload['enabled'] ?? false))
                <div class="alert alert-warning py-2 mb-3">
                    {{ $payload['note'] ?? 'Grafik belum tersedia.' }}
                </div>
            @endif

            <div style="height: 380px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const payload = @json($payload);

    const labels = payload.labels || [];
    const seriesObj = payload.series || {};
    const metric = payload.metric || 'qty';
    const enabled = !!payload.enabled;

    const canvas = document.getElementById('trendChart');
    if (!canvas) return;

    if (!enabled) {
        new Chart(canvas, {
            type: 'line',
            data: { labels: labels, datasets: [] },
            options: { responsive: true, maintainAspectRatio: false }
        });
        return;
    }

    const datasets = [];
    Object.keys(seriesObj).forEach(function(name){
        datasets.push({
            label: name,
            data: seriesObj[name] || [],
            tension: 0.25
        });
    });

    new Chart(canvas, {
        type: 'line',
        data: { labels: labels, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const v = (context.parsed && typeof context.parsed.y !== 'undefined') ? context.parsed.y : 0;
                            const rounded = Math.round(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                            if (metric === 'subtotal') {
                                return context.dataset.label + ': Rp ' + rounded;
                            }
                            return context.dataset.label + ': ' + rounded;
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
})();

window.addEventListener('scroll', function () {
    const bar = document.getElementById('reportFilterBar');
    if (!bar) return;

    if (window.scrollY > 120) {
        bar.classList.add('scrolled');
    } else {
        bar.classList.remove('scrolled');
    }
});
</script>
@endsection