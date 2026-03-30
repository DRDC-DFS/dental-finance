@extends('layouts.app')

@section('content')
@php
    $today = now()->toDateString();

    $rupiah = function ($value) {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    };

    $angka = function ($value) {
        return number_format((float) $value, 0, ',', '.');
    };

    $formatTanggal = function ($value) {
        if (!$value) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d-m-Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $grandTotalQty   = 0;
    $grandTotalTrx   = 0;
    $grandTotalGross = 0;
    $grandTotalFee   = 0;
    $grandTotalNet   = 0;

    $chart = $doctorChart ?? [
        'labels' => [],
        'feeTotals' => [],
        'netTotals' => [],
        'enabled' => false,
        'note' => 'Belum ada data grafik fee dokter pada periode ini.',
    ];

    $periodStart = request('start', $start ?? $today);
    $periodEnd = request('end', $end ?? $today);
    $isSingleDayPeriod = $periodStart === $periodEnd;

    $currentRoute = request()->route()?->getName();

    $weeklyStart = now()->startOfWeek()->toDateString();
    $weeklyEnd = now()->endOfWeek()->toDateString();
    $monthlyStart = now()->startOfMonth()->toDateString();
    $monthlyEnd = now()->endOfMonth()->toDateString();

    $isWeeklyMode = ($periodStart === $weeklyStart && $periodEnd === $weeklyEnd);
    $isMonthlyMode = ($periodStart === $monthlyStart && $periodEnd === $monthlyEnd);

    $isTodayMode = !$isWeeklyMode && !$isMonthlyMode && $isSingleDayPeriod;

    $exportParams = [
        'start' => $periodStart,
        'end' => $periodEnd,
    ];

    $periodeLabel = $isSingleDayPeriod
        ? 'Periode: ' . $formatTanggal($periodStart)
        : 'Periode: ' . $formatTanggal($periodStart) . ' s/d ' . $formatTanggal($periodEnd);

    $doctorGroups = is_array($doctorGroups ?? null) ? $doctorGroups : [];
@endphp

<div class="container py-4">

    <div class="d-flex flex-column gap-3 mb-3">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div>
                <h4 class="mb-1">Laporan Fee Dokter per Tindakan</h4>
                <div class="text-success small fw-bold">
                    Default tampilan: data hari berjalan
                </div>
                <div class="text-danger small fw-bold">
                    ✅ OWNER Only • Hanya transaksi PAID • Filter berdasarkan trx_date
                </div>
                <div class="text-muted small">
                    {{ $periodeLabel }}
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('reports.fee_dokter.index', ['start' => $today, 'end' => $today]) }}"
                   class="btn btn-outline-secondary btn-sm {{ $isTodayMode ? 'dfs-btn-active' : '' }}">
                    Hari Ini
                </a>

                <a href="{{ route('reports.fee_dokter.index', ['start' => $weeklyStart, 'end' => $weeklyEnd]) }}"
                   class="btn btn-outline-secondary btn-sm {{ $isWeeklyMode ? 'dfs-btn-active' : '' }}">
                    Mingguan
                </a>

                <a href="{{ route('reports.fee_dokter.index', ['start' => $monthlyStart, 'end' => $monthlyEnd]) }}"
                   class="btn btn-outline-secondary btn-sm {{ $isMonthlyMode ? 'dfs-btn-active' : '' }}">
                    Bulanan
                </a>

                <a href="{{ route('reports.fee_dokter.index', ['start' => $today, 'end' => $today]) }}"
                   class="btn btn-outline-secondary btn-sm {{ $isTodayMode ? 'dfs-btn-active' : '' }}">
                    Reset
                </a>

                <a href="{{ route('reports.laba-rugi', ['start' => $periodStart, 'end' => $periodEnd]) }}"
                   class="btn btn-outline-secondary btn-sm {{ $currentRoute === 'reports.laba-rugi' ? 'dfs-btn-active' : '' }}">
                    Laba Rugi
                </a>

                <a href="{{ route('reports.daily_cash.index', ['start' => $periodStart, 'end' => $periodEnd]) }}"
                   class="btn btn-outline-secondary btn-sm {{ $currentRoute === 'reports.daily_cash.index' ? 'dfs-btn-active' : '' }}">
                    Kas Harian
                </a>

                <a href="{{ route('owner_finance.index') }}"
                   class="btn btn-outline-primary btn-sm {{ str_starts_with($currentRoute ?? '', 'owner_finance.') ? 'dfs-btn-active' : '' }}">
                    Owner Finance
                </a>

                <a href="{{ route('owner_private.index') }}"
                   class="btn btn-outline-dark btn-sm {{ str_starts_with($currentRoute ?? '', 'owner_private.') ? 'dfs-btn-active' : '' }}">
                    Private Owner
                </a>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('reports.fee_dokter.export.pdf', $exportParams) }}"
               class="btn btn-danger btn-sm">
                Export PDF
            </a>

            <a href="{{ route('reports.fee_dokter.export.excel', $exportParams) }}"
               class="btn btn-success btn-sm">
                Export Excel
            </a>
        </div>

        <form method="GET" action="{{ route('reports.fee_dokter.index') }}" class="d-flex align-items-end gap-2 flex-wrap">
            <div>
                <label class="form-label mb-1 small text-muted">Mulai</label>
                <input type="date" name="start" value="{{ $periodStart }}" class="form-control form-control-sm">
            </div>

            <div>
                <label class="form-label mb-1 small text-muted">Sampai</label>
                <input type="date" name="end" value="{{ $periodEnd }}" class="form-control form-control-sm">
            </div>

            <button type="submit" class="btn btn-dark btn-sm">
                Terapkan Rentang
            </button>
        </form>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="fw-semibold">Grafik Fee Dokter & Net Klinik per Dokter</div>
                <div class="text-muted small">Hanya tampil untuk OWNER</div>
            </div>

            @if(!($chart['enabled'] ?? false))
                <div class="alert alert-warning py-2 mb-3">
                    {{ $chart['note'] ?? 'Grafik belum tersedia.' }}
                </div>
            @endif

            <div style="height: 360px;">
                <canvas id="doctorFeeChart"></canvas>
            </div>
        </div>
    </div>

    @forelse($doctorGroups as $doctorIndex => $doctor)
        @php
            $doctorRows = is_array($doctor['rows'] ?? null) ? $doctor['rows'] : [];
            $doctorTotalQty = (float) ($doctor['total_qty'] ?? 0);
            $doctorTotalTrx = (int) ($doctor['total_trx'] ?? 0);
            $doctorTotalGross = (float) ($doctor['total_gross'] ?? 0);
            $doctorTotalFee = (float) ($doctor['total_fee'] ?? 0);
            $doctorTotalNet = (float) ($doctor['total_net'] ?? 0);

            $grandTotalQty += $doctorTotalQty;
            $grandTotalTrx += $doctorTotalTrx;
            $grandTotalGross += $doctorTotalGross;
            $grandTotalFee += $doctorTotalFee;
            $grandTotalNet += $doctorTotalNet;
        @endphp

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <div class="fw-bold">
                    {{ $doctor['doctor_name'] ?? '-' }}
                </div>
                <div class="small">
                    Tipe Dokter: {{ strtoupper((string) ($doctor['doctor_type'] ?? '-')) }}
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tindakan</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Jml Transaksi</th>
                                <th class="text-end">Gross</th>
                                <th class="text-end">Fee Dokter</th>
                                <th class="text-end">Net Klinik</th>
                                <th class="text-center">Sumber Data</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($doctorRows as $rowIndex => $r)
                                @php
                                    $qty   = (float)($r['qty_total'] ?? 0);
                                    $trx   = (int)($r['trx_count'] ?? 0);
                                    $gross = (float)($r['gross_total'] ?? 0);
                                    $fee   = (float)($r['fee_total'] ?? 0);
                                    $net   = (float)($r['net_klinik'] ?? 0);
                                    $sourceTransactions = is_array($r['source_transactions'] ?? null) ? $r['source_transactions'] : [];
                                    $collapseId = 'fee-source-' . $doctorIndex . '-' . $rowIndex;
                                @endphp
                                <tr>
                                    <td>{{ $r['treatment_name'] ?? '-' }}</td>
                                    <td class="text-end">{{ $angka($qty) }}</td>
                                    <td class="text-end">{{ $angka($trx) }}</td>
                                    <td class="text-end">{{ $rupiah($gross) }}</td>
                                    <td class="text-end">{{ $rupiah($fee) }}</td>
                                    <td class="text-end">{{ $rupiah($net) }}</td>
                                    <td class="text-center">
                                        @if(!empty($sourceTransactions))
                                            <button class="btn btn-sm btn-outline-primary"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#{{ $collapseId }}"
                                                    aria-expanded="false"
                                                    aria-controls="{{ $collapseId }}">
                                                Lihat Sumber Data
                                            </button>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>

                                @if(!empty($sourceTransactions))
                                    <tr class="collapse" id="{{ $collapseId }}">
                                        <td colspan="7" class="bg-light">
                                            <div class="fw-bold mb-2">Daftar sumber data transaksi</div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle mb-0">
                                                    <thead class="table-white">
                                                        <tr>
                                                            <th>Tanggal</th>
                                                            <th>Invoice</th>
                                                            <th>Pasien</th>
                                                            <th class="text-center">Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($sourceTransactions as $trxItem)
                                                            <tr>
                                                                <td>{{ $formatTanggal($trxItem['trx_date'] ?? '-') }}</td>
                                                                <td>{{ $trxItem['invoice_number'] ?? '-' }}</td>
                                                                <td>{{ $trxItem['patient_name'] ?? '-' }}</td>
                                                                <td class="text-center">
                                                                    @if(!empty($trxItem['transaction_id']))
                                                                        <a href="{{ route('income.edit', $trxItem['transaction_id']) }}"
                                                                           class="btn btn-sm btn-outline-secondary">
                                                                            Buka Transaksi
                                                                        </a>
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="7">Tidak ada data untuk dokter ini pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>

                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td>TOTAL {{ $doctor['doctor_name'] ?? '-' }}</td>
                                <td class="text-end">{{ $angka($doctorTotalQty) }}</td>
                                <td class="text-end">{{ $angka($doctorTotalTrx) }}</td>
                                <td class="text-end">{{ $rupiah($doctorTotalGross) }}</td>
                                <td class="text-end">{{ $rupiah($doctorTotalFee) }}</td>
                                <td class="text-end">{{ $rupiah($doctorTotalNet) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="card shadow-sm">
            <div class="card-body">
                Tidak ada data pada periode ini.
            </div>
        </div>
    @endforelse

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white fw-bold">
            Grand Total Semua Dokter
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-end">Total Qty</th>
                            <th class="text-end">Total Transaksi</th>
                            <th class="text-end">Total Gross</th>
                            <th class="text-end">Total Fee Dokter</th>
                            <th class="text-end">Total Net Klinik</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="fw-bold">
                            <td class="text-end">{{ $angka($grandTotalQty) }}</td>
                            <td class="text-end">{{ $angka($grandTotalTrx) }}</td>
                            <td class="text-end">{{ $rupiah($grandTotalGross) }}</td>
                            <td class="text-end">{{ $rupiah($grandTotalFee) }}</td>
                            <td class="text-end">{{ $rupiah($grandTotalNet) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const payload = @json($chart);
    const labels = payload.labels || [];
    const feeTotals = payload.feeTotals || [];
    const netTotals = payload.netTotals || [];
    const enabled = !!payload.enabled;

    const canvas = document.getElementById('doctorFeeChart');
    if (!canvas) return;

    const formatRupiah = function (value) {
        const rounded = Math.round(value || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return 'Rp ' + rounded;
    };

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: enabled ? [
                {
                    label: 'Fee Dokter',
                    data: feeTotals
                },
                {
                    label: 'Net Klinik',
                    data: netTotals
                }
            ] : []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatRupiah(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatRupiah(value);
                        }
                    }
                }
            }
        }
    });
})();
</script>
@endsection