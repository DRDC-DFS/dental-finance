@extends('layouts.app')

@section('content')

@php
    $setting = class_exists(\App\Models\Setting::class)
        ? \App\Models\Setting::query()->first()
        : null;

    $logoUrl = $setting?->logo_url;
@endphp

<div class="container py-4">

    <div class="text-center mb-4">
        <div style="max-width:600px;margin:0 auto;">
            @if($logoUrl)
                <img
                    src="{{ $logoUrl }}"
                    style="width:220px;height:auto;object-fit:contain;"
                    alt="Logo Klinik">
            @endif

            <div style="margin-top:16px;font-size:34px;font-weight:900;color:#ff4fb5;letter-spacing:1px;">
                GORONTALO
            </div>

            <div class="text-muted mt-2">
                Dashboard Klinik
            </div>
        </div>
    </div>

    @if($isOwner)
        {{-- ========================= OWNER DASHBOARD ========================= --}}

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Transaksi Hari Ini</div>
                        <div class="fs-2 fw-bold">{{ number_format($todayIncomeCount, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Pemasukan Hari Ini</div>
                        <div class="fs-5 fw-bold">{{ format_rupiah($todayIncomeTotal) }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Pengeluaran Hari Ini</div>
                        <div class="fs-5 fw-bold">{{ format_rupiah($todayExpenseTotal) }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Kas Hari Ini</div>
                        <div class="fs-5 fw-bold">{{ format_rupiah($todayCashBalance) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ALERT STOK OWNER --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #f59e0b !important;">
                    <div class="card-body">
                        <div class="text-muted small">Alert Stok Minimum</div>
                        <div class="fs-2 fw-bold text-warning">{{ number_format($stockAlertCount, 0, ',', '.') }}</div>
                        <div class="text-muted small mt-2">Item mencapai / di bawah batas minimum</div>
                        <div class="mt-3">
                            <a href="{{ route('inventory.panel', ['tab' => 'stock']) }}" class="btn btn-sm btn-outline-warning">
                                Buka Inventory Alert
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #dc3545 !important;">
                    <div class="card-body">
                        <div class="text-muted small">Butuh Dilengkapi Owner</div>
                        <div class="fs-2 fw-bold text-danger">{{ number_format($ownerNeedsSetupCount, 0, ',', '.') }}</div>
                        <div class="mt-2">
                            <a href="{{ route('owner_finance.index', ['tab' => 'needs_setup']) }}" class="btn btn-sm btn-outline-danger">
                                Buka Kasus
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #0d6efd !important;">
                    <div class="card-body">
                        <div class="text-muted small">Kasus Berjalan</div>
                        <div class="fs-2 fw-bold text-primary">{{ number_format($ownerInProgressCount, 0, ',', '.') }}</div>
                        <div class="mt-2">
                            <a href="{{ route('owner_finance.index', ['tab' => 'monitoring']) }}" class="btn btn-sm btn-outline-primary">
                                Monitoring
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #198754 !important;">
                    <div class="card-body">
                        <div class="text-muted small">Kasus Selesai</div>
                        <div class="fs-2 fw-bold text-success">{{ number_format($ownerDoneCount, 0, ',', '.') }}</div>
                        <div class="mt-2 text-muted small">
                            Progress owner finance DFS
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #6f42c1 !important;">
                    <div class="card-body">
                        <div class="text-muted small">Dana Ortho Berjalan</div>
                        <div class="fs-5 fw-bold" style="color:#6f42c1;">{{ format_rupiah($ownerOrthoRunningFunds) }}</div>
                        <div class="mt-2 text-muted small">
                            Total sisa dana kasus ortho aktif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================= OWNER CONTROL TOWER ========================= --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #dc3545 !important;">
                    <div class="card-body">
                        <div class="text-muted small">Dana Tertahan Owner Finance</div>
                        <div class="fs-5 fw-bold text-danger">{{ format_rupiah($ownerTotalHoldingFunds ?? 0) }}</div>
                        <div class="mt-2 text-muted small">
                            Kasus belum lengkap / belum siap diakui
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #0d6efd !important;">
                    <div class="card-body">
                        <div class="text-muted small">Dana Berjalan Owner Finance</div>
                        <div class="fs-5 fw-bold text-primary">{{ format_rupiah($ownerTotalRunningFunds ?? 0) }}</div>
                        <div class="mt-2 text-muted small">
                            Kasus sedang follow-up / proses owner
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #198754 !important;">
                    <div class="card-body">
                        <div class="text-muted small">Income Owner Finance Diakui</div>
                        <div class="fs-5 fw-bold text-success">{{ format_rupiah($ownerTotalRecognizedIncome ?? 0) }}</div>
                        <div class="mt-2 text-muted small">
                            Sudah punya revenue_recognized_at
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #f59e0b !important;">
                    <div class="card-body">
                        <div class="text-muted small">Potensi Income Belum Jadi</div>
                        <div class="fs-5 fw-bold text-warning">{{ format_rupiah($ownerTotalPotentialIncome ?? 0) }}</div>
                        <div class="mt-2 text-muted small">
                            Belum punya pengakuan pendapatan
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4" style="border-left:6px solid #0d6efd !important;">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span>🎯 Top 5 Kasus Prioritas Owner</span>
                <a href="{{ route('owner_finance.index', ['tab' => 'needs_setup']) }}" class="btn btn-sm btn-outline-primary">
                    Buka Monitoring
                </a>
            </div>
            <div class="card-body">
                @php
                    $priorityCases = ($ownerPriorityCases ?? collect());

                    if ($priorityCases->count() === 0 && ($ownerFinanceAlerts ?? collect())->count() > 0) {
                        $priorityCases = $ownerFinanceAlerts->take(5);
                    }
                @endphp

                @if($priorityCases->count() > 0)
                    <div class="mb-2 text-muted small">
                        Kasus ditampilkan berdasarkan prioritas tindakan owner, bukan sekadar urutan terbaru.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Invoice</th>
                                    <th>Pasien</th>
                                    <th>Dokter</th>
                                    <th>Tipe Kasus</th>
                                    <th>Status</th>
                                    <th>Posisi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($priorityCases as $case)
                                    @php
                                        $caseTypeLabel = match ((string) ($case->case_type ?? '')) {
                                            'prostodonti' => 'Prostodonti',
                                            'ortho' => 'Ortho',
                                            'retainer' => 'Retainer',
                                            'lab' => 'Dental Laboratory',
                                            default => ucfirst((string) ($case->case_type ?? '-')),
                                        };

                                        if ((bool) ($case->needs_setup ?? false) || (($case->owner_followup_status ?? null) === 'needs_setup')) {
                                            $statusLabel = 'BUTUH DILENGKAPI';
                                            $statusClass = 'bg-danger';
                                        } elseif (in_array((string) ($case->owner_followup_status ?? ''), ['followed_up', 'in_progress'], true)) {
                                            $statusLabel = 'BERJALAN';
                                            $statusClass = 'bg-primary';
                                        } else {
                                            $statusLabel = 'SELESAI';
                                            $statusClass = 'bg-success';
                                        }

                                        if (!(bool) ($case->lab_paid ?? false) && !(bool) ($case->installed ?? false)) {
                                            $positionLabel = 'LAB & pemasangan belum';
                                            $positionClass = 'bg-warning text-dark';
                                        } elseif (!(bool) ($case->lab_paid ?? false)) {
                                            $positionLabel = 'LAB belum dibayar';
                                            $positionClass = 'bg-warning text-dark';
                                        } elseif (!(bool) ($case->installed ?? false)) {
                                            $positionLabel = 'Belum terpasang';
                                            $positionClass = 'bg-secondary';
                                        } else {
                                            $positionLabel = 'Siap / lengkap';
                                            $positionClass = 'bg-success';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $case->trx_date ? \Carbon\Carbon::parse($case->trx_date)->format('d-m-Y') : '-' }}</td>
                                        <td class="fw-semibold">{{ $case->invoice_number ?? '-' }}</td>
                                        <td>{{ $case->patient_name ?? '-' }}</td>
                                        <td>{{ $case->doctor_name ?? '-' }}</td>
                                        <td><span class="badge bg-secondary">{{ $caseTypeLabel }}</span></td>
                                        <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                        <td><span class="badge {{ $positionClass }}">{{ $positionLabel }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-success fw-semibold">
                        Tidak ada kasus prioritas owner saat ini. Semua relatif aman.
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4" style="border-left:6px solid #f59e0b !important;">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span>⚠ Alert Stok Minimum</span>
                <a href="{{ route('inventory.panel', ['tab' => 'stock']) }}" class="btn btn-sm btn-outline-warning">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                @if($stockAlertCount > 0)
                    <div class="mb-2 text-muted small">
                        Ada <b>{{ $stockAlertCount }}</b> item yang sudah mencapai atau di bawah minimum stok.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Satuan</th>
                                    <th class="text-end">Stok Saat Ini</th>
                                    <th class="text-end">Minimum</th>
                                    <th class="text-start">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockAlertItems as $item)
                                    @php
                                        $isBelow = (float) $item->current_stock < (float) $item->minimum_stock;
                                        $statusText = $isBelow ? 'DI BAWAH MINIMUM' : 'MINIMUM';
                                        $badgeClass = $isBelow ? 'bg-danger' : 'bg-warning text-dark';
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $item->name }}</td>
                                        <td>{{ $item->unit }}</td>
                                        <td class="text-end">{{ number_format((float) $item->current_stock, 2, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format((float) $item->minimum_stock, 2, ',', '.') }}</td>
                                        <td><span class="badge {{ $badgeClass }}">{{ $statusText }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-success fw-semibold">
                        Semua stok masih aman. Tidak ada item yang menyentuh batas minimum.
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4" style="border-left:6px solid #dc3545 !important;">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span>⚠ Alert Owner Finance</span>
                <a href="{{ route('owner_finance.index', ['tab' => 'needs_setup']) }}" class="btn btn-sm btn-outline-danger">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                @if($ownerNeedsSetupCount > 0)
                    <div class="mb-2 text-muted small">
                        Ada <b>{{ $ownerNeedsSetupCount }}</b> kasus yang masih menunggu tindak lanjut owner.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Invoice</th>
                                    <th>Pasien</th>
                                    <th>Dokter</th>
                                    <th>Tipe Kasus</th>
                                    <th>Posisi Saat Ini</th>
                                    <th>Tindak Lanjut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ownerFinanceAlerts as $alert)
                                    @php
                                        $caseTypeLabel = match ((string) ($alert->case_type ?? '')) {
                                            'prostodonti' => 'Prostodonti',
                                            'ortho' => 'Ortho',
                                            'retainer' => 'Retainer',
                                            default => ucfirst((string) ($alert->case_type ?? '-')),
                                        };

                                        $progressLabel = match ((string) ($alert->case_progress_status ?? '')) {
                                            'waiting_owner_setup' => 'Menunggu data owner',
                                            'waiting_setup' => 'Menunggu setup alokasi dana',
                                            'waiting_lab_payment' => 'Menunggu pembayaran LAB',
                                            'lab_paid_not_installed' => 'LAB sudah dibayar, belum terpasang',
                                            'installed_lab_not_paid' => 'Sudah terpasang, LAB belum dibayar',
                                            'installment_running' => 'Cicilan berjalan',
                                            'remaining_balance' => 'Sisa dana masih ada',
                                            'done' => 'Selesai',
                                            default => '-',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $alert->trx_date ? \Carbon\Carbon::parse($alert->trx_date)->format('d-m-Y') : '-' }}</td>
                                        <td class="fw-semibold">{{ $alert->invoice_number ?? '-' }}</td>
                                        <td>{{ $alert->patient_name ?? '-' }}</td>
                                        <td>{{ $alert->doctor_name ?? '-' }}</td>
                                        <td><span class="badge bg-secondary">{{ $caseTypeLabel }}</span></td>
                                        <td><span class="badge bg-warning text-dark">{{ $progressLabel }}</span></td>
                                        <td>{{ $alert->owner_last_action_note ?? 'Menunggu data owner' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-success fw-semibold">
                        Tidak ada kasus owner finance yang menunggu tindakan. Semua aman.
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('dashboard') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3 col-lg-2">
                            <label class="form-label fw-bold">Rentang Grafik</label>
                            <select name="range" class="form-select" onchange="this.form.submit()">
                                <option value="weekly" {{ $range === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                                <option value="monthly" {{ $range === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                                <option value="yearly" {{ $range === 'yearly' ? 'selected' : '' }}>Tahunan</option>
                            </select>
                        </div>

                        <div class="col-md-3 col-lg-2">
                            <label class="form-label fw-bold">Tahun</label>
                            <select name="year" class="form-select" {{ $range !== 'yearly' ? 'disabled' : '' }} onchange="this.form.submit()">
                                @forelse($availableYears as $yearOption)
                                    <option value="{{ $yearOption }}" {{ (int) $selectedYear === (int) $yearOption ? 'selected' : '' }}>
                                        {{ $yearOption }}
                                    </option>
                                @empty
                                    <option value="{{ $currentYear }}" selected>{{ $currentYear }}</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <label class="form-label fw-bold">Trend Tindakan</label>
                            <select name="treatment_id" class="form-select" onchange="this.form.submit()">
                                @forelse($treatmentOptions as $treatment)
                                    <option value="{{ $treatment->id }}" {{ (int) $selectedTreatmentId === (int) $treatment->id ? 'selected' : '' }}>
                                        {{ $treatment->name }}
                                    </option>
                                @empty
                                    <option value="0" selected>Belum ada tindakan</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="col-md-12 col-lg-4">
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">Tampilkan</button>
                                <a href="{{ route('dashboard', ['range' => 'weekly', 'treatment_id' => $selectedTreatmentId]) }}" class="btn btn-outline-primary">Mingguan</a>
                                <a href="{{ route('dashboard', ['range' => 'monthly', 'treatment_id' => $selectedTreatmentId]) }}" class="btn btn-outline-success">Bulanan</a>
                                <a href="{{ route('dashboard', ['range' => 'yearly', 'year' => $selectedYear, 'treatment_id' => $selectedTreatmentId]) }}" class="btn btn-outline-dark">Tahunan</a>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-muted small">
                        Grafik aktif: <strong>{{ $rangeTitle }}</strong>
                        @if($selectedTreatmentId > 0)
                            | Tindakan dipilih: <strong>{{ $selectedTreatmentName }}</strong>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #0d6efd !important;">
                    <div class="card-body">
                        <div class="text-muted small">Omzet {{ $rangeTitle }}</div>
                        <div class="fs-5 fw-bold text-primary">{{ format_rupiah($periodGrossIncome) }}</div>
                        <div class="text-muted small mt-2">Total bill transaksi periode aktif</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #198754 !important;">
                    <div class="card-body">
                        <div class="text-muted small">Pemasukan Riil {{ $rangeTitle }}</div>
                        <div class="fs-5 fw-bold text-success">{{ format_rupiah($periodPaidIncome) }}</div>
                        <div class="text-muted small mt-2">Akumulasi pay_total status PAID</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #dc3545 !important;">
                    <div class="card-body">
                        <div class="text-muted small">Pengeluaran {{ $rangeTitle }}</div>
                        <div class="fs-5 fw-bold text-danger">{{ format_rupiah($periodExpenseTotal) }}</div>
                        <div class="text-muted small mt-2">Total expense periode aktif</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100" style="border-left:6px solid #6f42c1 !important;">
                    <div class="card-body">
                        <div class="text-muted small">Laba Klinik {{ $rangeTitle }}</div>
                        <div class="fs-5 fw-bold {{ $periodProfit < 0 ? 'text-danger' : 'text-success' }}">
                            {{ format_rupiah($periodProfit) }}
                        </div>
                        <div class="text-muted small mt-2">Pemasukan riil − pengeluaran</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Jumlah Tindakan {{ $rangeTitle }}</div>
                        <div class="fs-2 fw-bold">{{ number_format($periodActionCount, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Grafik Omzet — {{ $rangeTitle }}</div>
                    <div class="card-body">
                        <canvas id="incomeChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Grafik Laba Klinik — {{ $rangeTitle }}</div>
                    <div class="card-body">
                        <canvas id="profitChart" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Grafik Tindakan Total — {{ $rangeTitle }}</div>
                    <div class="card-body">
                        <canvas id="actionChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Grafik Dokter — {{ $rangeTitle }}</div>
                    <div class="card-body">
                        <canvas id="doctorChart" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Grafik Trend per Kategori Tindakan — {{ $rangeTitle }}</div>
                    <div class="card-body">
                        <canvas id="categoryChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                        <span>Trend Line Tindakan</span>
                        <span class="text-muted small">{{ $selectedTreatmentName }}</span>
                    </div>
                    <div class="card-body">
                        <canvas id="treatmentTrendChart" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Top Tindakan — {{ $rangeTitle }}</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <canvas id="topActionChart" height="120"></canvas>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Tindakan</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Omzet</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topActionsRows as $index => $row)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td class="fw-semibold">{{ $row->treatment_name }}</td>
                                            <td class="text-end">{{ number_format((int) $row->total_qty, 0, ',', '.') }}</td>
                                            <td class="text-end fw-semibold">{{ format_rupiah((float) $row->total_amount) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Belum ada data tindakan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Top Dokter — {{ $rangeTitle }}</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Dokter</th>
                                        <th class="text-end">Omzet</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topDoctorsRows as $index => $row)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td class="fw-semibold">{{ $row->doctor_name }}</td>
                                            <td class="text-end fw-semibold">{{ format_rupiah((float) $row->total_amount) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Belum ada data dokter.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- ========================= ADMIN DASHBOARD ========================= --}}

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('dashboard') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4 col-lg-3">
                            <label class="form-label fw-bold">Pilih Tanggal</label>
                            <input
                                type="date"
                                name="admin_date"
                                class="form-control"
                                value="{{ $adminDate }}">
                        </div>

                        <div class="col-md-8 col-lg-9">
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">Tampilkan</button>

                                <a href="{{ route('dashboard', ['admin_date' => now()->toDateString()]) }}" class="btn btn-outline-primary">
                                    Hari Ini
                                </a>

                                <a href="{{ route('dashboard', ['admin_date' => now()->subDay()->toDateString()]) }}" class="btn btn-outline-secondary">
                                    Kemarin
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-muted small">
                        Dashboard admin aktif untuk tanggal:
                        <strong>{{ \Carbon\Carbon::parse($adminDate)->format('d-m-Y') }}</strong>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Input Pemasukan</div>
                        <div class="fs-5 fw-bold">{{ format_rupiah($adminIncomeTotal) }}</div>
                        <div class="text-muted small mt-2">Basis sama dengan modul Pemasukan</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Input Pengeluaran</div>
                        <div class="fs-5 fw-bold">{{ format_rupiah($adminExpenseTotal) }}</div>
                        <div class="text-muted small mt-2">Tanggal {{ \Carbon\Carbon::parse($adminDate)->format('d-m-Y') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Jumlah Pasien</div>
                        <div class="fs-2 fw-bold">{{ number_format($adminPatientCount, 0, ',', '.') }}</div>
                        <div class="text-muted small mt-2">Tanggal {{ \Carbon\Carbon::parse($adminDate)->format('d-m-Y') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">Jumlah Tindakan</div>
                        <div class="fs-2 fw-bold">{{ number_format($adminActionCount, 0, ',', '.') }}</div>
                        <div class="text-muted small mt-2">
                            {{ number_format($adminTransactionCount, 0, ',', '.') }} transaksi pada tanggal ini
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ALERT STOK ADMIN --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0" style="border-left:6px solid #f59e0b !important;">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                        <span>⚠ Alert Stok Minimum</span>
                        <a href="{{ route('inventory.panel', ['tab' => 'stock']) }}" class="btn btn-sm btn-outline-warning">Lihat Stok</a>
                    </div>
                    <div class="card-body">
                        @if($stockAlertCount > 0)
                            <div class="mb-2 text-muted small">
                                Ada <b>{{ $stockAlertCount }}</b> item yang sudah mencapai atau di bawah minimum stok.
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Satuan</th>
                                            <th class="text-end">Stok Saat Ini</th>
                                            <th class="text-end">Minimum</th>
                                            <th class="text-start">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stockAlertItems as $item)
                                            @php
                                                $isBelow = (float) $item->current_stock < (float) $item->minimum_stock;
                                                $statusText = $isBelow ? 'DI BAWAH MINIMUM' : 'MINIMUM';
                                                $badgeClass = $isBelow ? 'bg-danger' : 'bg-warning text-dark';
                                            @endphp
                                            <tr>
                                                <td class="fw-semibold">{{ $item->name }}</td>
                                                <td>{{ $item->unit }}</td>
                                                <td class="text-end">{{ number_format((float) $item->current_stock, 2, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format((float) $item->minimum_stock, 2, ',', '.') }}</td>
                                                <td><span class="badge {{ $badgeClass }}">{{ $statusText }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-success fw-semibold">
                                Semua stok masih aman. Tidak ada item yang menyentuh batas minimum.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">
                        Grafik Pemasukan per Jam
                    </div>
                    <div class="card-body">
                        <canvas id="adminIncomeChart" height="120"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">
                        Grafik Tindakan per Jam
                    </div>
                    <div class="card-body">
                        <canvas id="adminActionChart" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">
            {{ $isAdmin ? 'Semua Transaksi pada Tanggal Terpilih' : 'Transaksi Hari Ini' }}
        </div>
        <div class="card-body">
            @if($isAdmin)
                <div class="mb-2 text-muted small">
                    Total data tampil: <strong>{{ number_format($recentTransactions->count(), 0, ',', '.') }}</strong>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice</th>
                            <th>Tanggal</th>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $trx)
                            @php
                                $status = strtolower((string) ($trx->status ?? 'draft'));
                                $badgeClass = 'bg-secondary';

                                if ($status === 'paid') {
                                    $badgeClass = 'bg-success';
                                } elseif ($status === 'draft') {
                                    $badgeClass = 'bg-warning text-dark';
                                } elseif ($status === 'cancelled' || $status === 'void') {
                                    $badgeClass = 'bg-danger';
                                }
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $trx->invoice_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($trx->trx_date)->format('d-m-Y') }}</td>
                                <td>{{ $trx->patient_name ?? '-' }}</td>
                                <td>{{ $trx->doctor_name ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $badgeClass }}">
                                        {{ strtoupper($trx->status ?? 'draft') }}
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">{{ format_rupiah($trx->bill_total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    {{ $isAdmin ? 'Belum ada transaksi pada tanggal ini.' : 'Belum ada transaksi pada hari ini.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    @if($isOwner)
        const periodLabels = @json($periodLabels);

        const incomeData = @json($incomeSeries);
        const paidIncomeData = @json($paidIncomeSeries);
        const expenseData = @json($expenseSeries);
        const profitData = @json($profitSeries);

        const actionData = @json($actionSeries);

        const doctorLabels = @json($doctorLabels);
        const doctorData = @json($doctorSeries);

        const categoryLabels = @json($categoryLabels);
        const categoryData = @json($categorySeries);

        const topActionLabels = @json($topActionLabels);
        const topActionData = @json($topActionSeries);

        const treatmentTrendQtyData = @json($treatmentTrendQtySeries);
        const treatmentTrendAmountData = @json($treatmentTrendAmountSeries);

        new Chart(document.getElementById('incomeChart'), {
            type: 'line',
            data: {
                labels: periodLabels,
                datasets: [{
                    label: 'Omzet',
                    data: incomeData,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                    tension: 0.35,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true } }
            }
        });

        new Chart(document.getElementById('profitChart'), {
            type: 'line',
            data: {
                labels: periodLabels,
                datasets: [
                    {
                        label: 'Pemasukan Riil',
                        data: paidIncomeData,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.10)',
                        tension: 0.35,
                        fill: false
                    },
                    {
                        label: 'Pengeluaran',
                        data: expenseData,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.10)',
                        tension: 0.35,
                        fill: false
                    },
                    {
                        label: 'Laba Klinik',
                        data: profitData,
                        borderColor: '#6f42c1',
                        backgroundColor: 'rgba(111, 66, 193, 0.10)',
                        tension: 0.35,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true } }
            }
        });

        new Chart(document.getElementById('actionChart'), {
            type: 'bar',
            data: {
                labels: periodLabels,
                datasets: [{
                    label: 'Jumlah Tindakan',
                    data: actionData,
                    backgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true } }
            }
        });

        new Chart(document.getElementById('doctorChart'), {
            type: 'bar',
            data: {
                labels: doctorLabels,
                datasets: [{
                    label: 'Omzet per Dokter',
                    data: doctorData,
                    backgroundColor: '#8b5cf6'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true } }
            }
        });

        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Omzet per Kategori',
                    data: categoryData,
                    backgroundColor: '#f59e0b'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: true } }
            }
        });

        new Chart(document.getElementById('treatmentTrendChart'), {
            type: 'line',
            data: {
                labels: periodLabels,
                datasets: [
                    {
                        label: 'Qty Tindakan',
                        data: treatmentTrendQtyData,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.10)',
                        tension: 0.35,
                        fill: false,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Omzet Tindakan',
                        data: treatmentTrendAmountData,
                        borderColor: '#fd7e14',
                        backgroundColor: 'rgba(253, 126, 20, 0.10)',
                        tension: 0.35,
                        fill: false,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                stacked: false,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left'
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('topActionChart'), {
            type: 'bar',
            data: {
                labels: topActionLabels,
                datasets: [{
                    label: 'Omzet Tindakan',
                    data: topActionData,
                    backgroundColor: '#20c997'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: true } }
            }
        });
    @else
        const adminHourlyLabels = @json($adminHourlyLabels);
        const adminIncomeHourlyData = @json($adminIncomeHourlySeries);
        const adminActionHourlyData = @json($adminActionHourlySeries);

        new Chart(document.getElementById('adminIncomeChart'), {
            type: 'bar',
            data: {
                labels: adminHourlyLabels,
                datasets: [{
                    label: 'Pemasukan',
                    data: adminIncomeHourlyData,
                    backgroundColor: '#2563eb'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true } }
            }
        });

        new Chart(document.getElementById('adminActionChart'), {
            type: 'bar',
            data: {
                labels: adminHourlyLabels,
                datasets: [{
                    label: 'Tindakan',
                    data: adminActionHourlyData,
                    backgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true } }
            }
        });
    @endif
</script>

@endsection