@extends('layouts.app')

@section('content')
@php
    $rupiah = function ($value) {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    };

    $formatDate = function ($value, $fallback = '-') {
        if (empty($value)) {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $fallback;
        }
    };

    $diffDays = function ($value) {
        if (empty($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->startOfDay()->diffInDays(now()->startOfDay());
        } catch (\Throwable $e) {
            return null;
        }
    };

    $currentTab = $tab ?? 'needs_setup';
    $search = $q ?? '';
    $currentRoute = request()->route()?->getName();
    $dateFrom = $dateFrom ?? '';
    $dateTo = $dateTo ?? '';
    $summary = is_array($summary ?? null) ? $summary : [];

    $today = now()->format('Y-m-d');
    $weekStart = now()->startOfWeek()->format('Y-m-d');
    $weekEnd = now()->endOfWeek()->format('Y-m-d');
    $monthStart = now()->startOfMonth()->format('Y-m-d');
    $monthEnd = now()->endOfMonth()->format('Y-m-d');

    $isTodayRange = $dateFrom === $today && $dateTo === $today;
    $isWeekRange = $dateFrom === $weekStart && $dateTo === $weekEnd;
    $isMonthRange = $dateFrom === $monthStart && $dateTo === $monthEnd;
@endphp

<div class="container py-4">
    <div class="d-flex flex-column gap-3 mb-3">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div>
                <h4 class="mb-1">Owner Finance Control</h4>
                <div class="text-danger small fw-bold">
                    ✅ OWNER Only • Modul privat untuk Prostodonti / Ortho / Retainer / Dental Laboratory
                </div>
                <div class="text-muted small mt-1">
                    Transaksi Private Owner dicatat terpisah dan tidak masuk ke operasional klinik.
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('owner_finance.create') }}" class="btn btn-primary btn-sm">
                    + Tambah Case
                </a>

                <a href="{{ route('owner_private.index') }}"
                   class="btn btn-outline-dark btn-sm {{ str_starts_with($currentRoute ?? '', 'owner_private.') ? 'dfs-btn-active' : '' }}">
                    Private Owner
                </a>

                <a href="{{ route('reports.daily_cash.index') }}"
                   class="btn btn-outline-secondary btn-sm {{ $currentRoute === 'reports.daily_cash.index' ? 'dfs-btn-active' : '' }}">
                    Kas Harian
                </a>
                <a href="{{ route('reports.laba-rugi') }}"
                   class="btn btn-outline-secondary btn-sm {{ $currentRoute === 'reports.laba-rugi' ? 'dfs-btn-active' : '' }}">
                    Laba Rugi
                </a>
                <a href="{{ route('reports.fee_dokter.index') }}"
                   class="btn btn-outline-secondary btn-sm {{ $currentRoute === 'reports.fee_dokter.index' ? 'dfs-btn-active' : '' }}">
                    Fee Dokter
                </a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body py-2">
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('owner_finance.index', ['tab' => 'needs_setup', 'case_type' => $caseType ?? '', 'q' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                       class="btn btn-sm {{ $currentTab === 'needs_setup' ? 'dfs-btn-active btn-outline-danger' : 'btn-outline-danger' }}">
                        Butuh Dilengkapi Owner
                    </a>

                    <a href="{{ route('owner_finance.index', ['tab' => 'monitoring', 'case_type' => $caseType ?? '', 'q' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                       class="btn btn-sm {{ $currentTab === 'monitoring' ? 'dfs-btn-active btn-outline-primary' : 'btn-outline-primary' }}">
                        Monitoring Tindak Lanjut
                    </a>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('owner_finance.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="{{ $currentTab }}">

            <div class="col-md-2">
                <label class="form-label mb-1 small text-muted">Tipe Kasus</label>
                <select name="case_type" class="form-select form-select-sm">
                    <option value="" @selected(($caseType ?? '') === '')>Semua</option>
                    <option value="prostodonti" @selected(($caseType ?? '') === 'prostodonti')>Prostodonti</option>
                    <option value="ortho" @selected(($caseType ?? '') === 'ortho')>Ortho</option>
                    <option value="retainer" @selected(($caseType ?? '') === 'retainer')>Retainer</option>
                    <option value="lab" @selected(($caseType ?? '') === 'lab')>Dental Laboratory</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label mb-1 small text-muted">Cari Invoice / Nama Pasien / Dokter</label>
                <input type="text"
                       name="q"
                       class="form-control form-control-sm"
                       value="{{ $search }}"
                       placeholder="contoh: INV-20260309 atau Ristho">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1 small text-muted">Dari Tanggal</label>
                <input type="date"
                       name="date_from"
                       class="form-control form-control-sm"
                       value="{{ $dateFrom }}">
            </div>

            <div class="col-md-2">
                <label class="form-label mb-1 small text-muted">Sampai Tanggal</label>
                <input type="date"
                       name="date_to"
                       class="form-control form-control-sm"
                       value="{{ $dateTo }}">
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark btn-sm">Terapkan</button>
                <a href="{{ route('owner_finance.index', ['tab' => $currentTab]) }}"
                   class="btn btn-outline-secondary btn-sm">
                    Reset
                </a>
            </div>

            <div class="col-12">
                <div class="d-flex flex-wrap gap-2 mt-1">
                    <a href="{{ route('owner_finance.index', ['tab' => $currentTab, 'case_type' => $caseType ?? '', 'q' => $search, 'date_from' => $today, 'date_to' => $today]) }}"
                       class="btn btn-sm {{ $isTodayRange ? 'dfs-btn-active btn-outline-dark' : 'btn-outline-dark' }}">
                        Hari Ini
                    </a>

                    <a href="{{ route('owner_finance.index', ['tab' => $currentTab, 'case_type' => $caseType ?? '', 'q' => $search, 'date_from' => $weekStart, 'date_to' => $weekEnd]) }}"
                       class="btn btn-sm {{ $isWeekRange ? 'dfs-btn-active btn-outline-primary' : 'btn-outline-primary' }}">
                        Minggu Ini
                    </a>

                    <a href="{{ route('owner_finance.index', ['tab' => $currentTab, 'case_type' => $caseType ?? '', 'q' => $search, 'date_from' => $monthStart, 'date_to' => $monthEnd]) }}"
                       class="btn btn-sm {{ $isMonthRange ? 'dfs-btn-active btn-outline-success' : 'btn-outline-success' }}">
                        Bulan Ini
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-2 col-sm-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Kasus</div>
                    <div class="fs-4 fw-bold">{{ number_format((int) ($summary['total_cases'] ?? 0), 0, ',', '.') }}</div>
                    <div class="small text-muted">Sesuai filter aktif</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6">
            <div class="card shadow-sm border-0 h-100 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="text-muted small">Butuh Perhatian</div>
                    <div class="fs-4 fw-bold text-danger">{{ number_format((int) ($summary['needs_attention'] ?? 0), 0, ',', '.') }}</div>
                    <div class="small text-muted">Belum lengkap / setup</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6">
            <div class="card shadow-sm border-0 h-100 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="text-muted small">Berjalan</div>
                    <div class="fs-4 fw-bold text-primary">{{ number_format((int) ($summary['in_progress'] ?? 0), 0, ',', '.') }}</div>
                    <div class="small text-muted">Follow-up aktif</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6">
            <div class="card shadow-sm border-0 h-100 border-start border-4 border-success">
                <div class="card-body">
                    <div class="text-muted small">Selesai</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format((int) ($summary['done'] ?? 0), 0, ',', '.') }}</div>
                    <div class="small text-muted">Kasus final</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6">
            <div class="card shadow-sm border-0 h-100 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="text-muted small">Kasus Tertahan</div>
                    <div class="fs-4 fw-bold text-warning">{{ number_format((int) ($summary['held_cases'] ?? 0), 0, ',', '.') }}</div>
                    <div class="small text-muted">Belum jadi income penuh</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Dana Ortho Berjalan</div>
                    <div class="fw-bold">{{ $rupiah($summary['ortho_running_balance'] ?? 0) }}</div>
                    <div class="small text-muted">Sisa alokasi ortho</div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <div class="text-muted small">Potensi Pendapatan Klinik</div>
                        <div class="fs-5 fw-bold text-success">
                            {{ $rupiah($summary['potential_clinic_income'] ?? 0) }}
                        </div>
                    </div>
                    <div class="small text-muted">
                        Ringkasan ini mengikuti filter tab, tipe kasus, pencarian, dan tanggal yang sedang aktif.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white fw-bold">
            Tabel Monitoring Tindak Lanjut
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Umur Kasus</th>
                            <th>Update Terakhir</th>
                            <th>Invoice</th>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Tipe Kasus</th>
                            <th>Status Owner</th>
                            <th>Status Monitoring</th>
                            <th>Status Dental Laboratory</th>
                            <th>Status Pemasangan</th>
                            <th>Tgl Bayar Lab</th>
                            <th>Tgl Pasang</th>
                            <th>Tgl Jadi Pendapatan</th>
                            <th class="text-end">Dana Alokasi</th>
                            <th class="text-end">Total Dibayar Owner</th>
                            <th>Posisi Dana Saat Ini</th>
                            <th>Tindak Lanjut Terakhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse(($cases ?? []) as $case)
                            @php
                                $trx = $case->incomeTransaction;
                                $labBillAmount = (float) ($case->lab_bill_amount ?? 0);
                                $clinicIncomeAmount = (float) ($case->clinic_income_amount ?? 0);
                                $grossCaseAmount = (float) ($labBillAmount + $clinicIncomeAmount);

                                $status = strtolower((string) ($case->owner_followup_status ?? ''));
                                $labPaid = (bool) ($case->lab_paid ?? false);
                                $installed = (bool) ($case->installed ?? false);
                                $remainingBalance = (float) ($case->ortho_remaining_balance ?? 0);

                                $trxDateRaw = $trx?->trx_date ?? null;
                                $lastActivityRaw = $case->owner_last_action_at ?? $case->updated_at ?? null;

                                $caseAgeDays = $diffDays($trxDateRaw);
                                $lastUpdateDays = $diffDays($lastActivityRaw);

                                $caseAgeBadgeClass = 'bg-success';
                                $caseAgeText = '-';
                                if ($caseAgeDays !== null) {
                                    if ($caseAgeDays > 14) {
                                        $caseAgeBadgeClass = 'bg-danger';
                                    } elseif ($caseAgeDays >= 7) {
                                        $caseAgeBadgeClass = 'bg-warning text-dark';
                                    } else {
                                        $caseAgeBadgeClass = 'bg-success';
                                    }
                                    $caseAgeText = $caseAgeDays . ' hari';
                                }

                                $lastUpdateBadgeClass = 'bg-success';
                                $lastUpdateText = '-';
                                if ($lastUpdateDays !== null) {
                                    if ($lastUpdateDays > 14) {
                                        $lastUpdateBadgeClass = 'bg-danger';
                                    } elseif ($lastUpdateDays >= 7) {
                                        $lastUpdateBadgeClass = 'bg-warning text-dark';
                                    } else {
                                        $lastUpdateBadgeClass = 'bg-success';
                                    }
                                    $lastUpdateText = $lastUpdateDays . ' hari lalu';
                                }

                                $displayDanaAlokasi = '-';
                                $displayTotalDibayarOwner = '-';
                                $displayPosisiDanaSaatIni = '-';
                                $monitoringBadgeClass = 'bg-warning text-dark';
                                $monitoringBadgeText = 'TERTAHAN';
                                $rowClass = 'table-warning';
                                $positionBadgeClass = 'bg-warning text-dark';
                                $positionBadgeText = 'Tertahan';

                                if ($case->case_type === 'ortho') {
                                    $displayDanaAlokasi = $rupiah($case->ortho_allocation_amount ?? 0);
                                    $displayTotalDibayarOwner = $rupiah($case->ortho_paid_amount ?? 0);

                                    if ($remainingBalance <= 0 && $labPaid && $installed) {
                                        $displayPosisiDanaSaatIni = 'Selesai';
                                        $monitoringBadgeClass = 'bg-success';
                                        $monitoringBadgeText = 'SELESAI';
                                        $rowClass = 'table-success';
                                        $positionBadgeClass = 'bg-success';
                                        $positionBadgeText = 'Selesai';
                                    } elseif ($remainingBalance > 0) {
                                        $displayPosisiDanaSaatIni = $rupiah($remainingBalance) . ' • Cicilan berjalan';
                                        $monitoringBadgeClass = 'bg-primary';
                                        $monitoringBadgeText = 'AKTIF';
                                        $rowClass = 'table-primary';
                                        $positionBadgeClass = 'bg-primary';
                                        $positionBadgeText = 'Cicilan Berjalan';
                                    } else {
                                        $displayPosisiDanaSaatIni = $case->posisi_saat_ini ?? '-';
                                    }
                                } elseif ($case->case_type === 'lab') {
                                    $displayDanaAlokasi = $rupiah($grossCaseAmount);
                                    $displayTotalDibayarOwner = $rupiah($labBillAmount);

                                    if ($labPaid && $installed) {
                                        $displayPosisiDanaSaatIni = $rupiah($clinicIncomeAmount) . ' • Pendapatan klinik';
                                        $monitoringBadgeClass = 'bg-success';
                                        $monitoringBadgeText = 'SELESAI';
                                        $rowClass = 'table-success';
                                        $positionBadgeClass = 'bg-success';
                                        $positionBadgeText = 'Sudah Jadi Pendapatan';
                                    } else {
                                        $displayPosisiDanaSaatIni = 'Tertahan di Owner Finance';
                                        $monitoringBadgeClass = 'bg-warning text-dark';
                                        $monitoringBadgeText = 'TERTAHAN';
                                        $rowClass = 'table-warning';
                                        $positionBadgeClass = 'bg-warning text-dark';
                                        $positionBadgeText = 'Tertahan';
                                    }
                                } elseif (in_array((string) $case->case_type, ['prostodonti', 'retainer'], true)) {
                                    $displayDanaAlokasi = $rupiah($grossCaseAmount);
                                    $displayTotalDibayarOwner = '-';

                                    if ($labPaid && $installed) {
                                        $displayPosisiDanaSaatIni = $rupiah($clinicIncomeAmount) . ' • Pendapatan klinik';
                                        $monitoringBadgeClass = 'bg-success';
                                        $monitoringBadgeText = 'SELESAI';
                                        $rowClass = 'table-success';
                                        $positionBadgeClass = 'bg-success';
                                        $positionBadgeText = 'Sudah Jadi Pendapatan';
                                    } else {
                                        $displayPosisiDanaSaatIni = 'Tertahan • carry forward ke bulan berikutnya';
                                        $monitoringBadgeClass = 'bg-warning text-dark';
                                        $monitoringBadgeText = 'TERTAHAN';
                                        $rowClass = 'table-warning';
                                        $positionBadgeClass = 'bg-warning text-dark';
                                        $positionBadgeText = 'Carry Forward';
                                    }
                                }

                                if ($status === 'done') {
                                    $ownerStatusBadgeClass = 'bg-success';
                                    $ownerStatusText = 'SELESAI';
                                } elseif ($status === 'in_progress') {
                                    $ownerStatusBadgeClass = 'bg-primary';
                                    $ownerStatusText = 'BERJALAN';
                                } else {
                                    $ownerStatusBadgeClass = 'bg-danger';
                                    $ownerStatusText = 'BUTUH DILENGKAPI';
                                }

                                if ($status === 'done' && $monitoringBadgeText !== 'SELESAI') {
                                    $rowClass = 'table-primary';
                                }

                                if ($monitoringBadgeText !== 'SELESAI' && $caseAgeDays !== null) {
                                    if ($caseAgeDays > 14) {
                                        $rowClass = 'table-danger';
                                    } elseif ($caseAgeDays >= 7 && $rowClass !== 'table-danger') {
                                        $rowClass = 'table-warning';
                                    }
                                }

                                $labPaidAt = $formatDate($case->lab_paid_at ?? null);
                                $installedAt = $formatDate($case->installed_at ?? null);
                                $recognizedAt = $formatDate($case->revenue_recognized_at ?? null);
                                $trxDateDisplay = $formatDate($trxDateRaw);
                                $lastActivityDisplay = $formatDate($lastActivityRaw);
                            @endphp

                            <tr class="{{ $rowClass }}">
                                <td>{{ $trxDateDisplay }}</td>

                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span class="badge {{ $caseAgeBadgeClass }} align-self-start">
                                            {{ $caseAgeText }}
                                        </span>
                                        @if($caseAgeDays !== null && $caseAgeDays > 14)
                                            <span class="small text-danger fw-semibold">Perlu perhatian</span>
                                        @elseif($caseAgeDays !== null && $caseAgeDays >= 7)
                                            <span class="small text-warning fw-semibold">Perlu dipantau</span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span class="badge {{ $lastUpdateBadgeClass }} align-self-start">
                                            {{ $lastUpdateText }}
                                        </span>
                                        <span class="small text-muted">{{ $lastActivityDisplay }}</span>
                                    </div>
                                </td>

                                <td>{{ $trx?->invoice_number ?? '-' }}</td>
                                <td>{{ $trx?->patient?->name ?? '-' }}</td>
                                <td>{{ $trx?->doctor?->name ?? '-' }}</td>

                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $case->case_type_label }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge {{ $ownerStatusBadgeClass }}">
                                        {{ $ownerStatusText }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge {{ $monitoringBadgeClass }}">
                                        {{ $monitoringBadgeText }}
                                    </span>
                                </td>

                                <td>
                                    @if($labPaid)
                                        <span class="badge bg-success">Sudah Dibayar</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Belum Dibayar</span>
                                    @endif
                                </td>

                                <td>
                                    @if($installed)
                                        <span class="badge bg-success">Sudah Terpasang</span>
                                    @else
                                        <span class="badge bg-secondary">Belum Terpasang</span>
                                    @endif
                                </td>

                                <td>{{ $labPaidAt }}</td>
                                <td>{{ $installedAt }}</td>
                                <td>{{ $recognizedAt }}</td>

                                <td class="text-end">
                                    {{ $displayDanaAlokasi }}
                                </td>

                                <td class="text-end">
                                    {{ $displayTotalDibayarOwner }}
                                </td>

                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span class="badge {{ $positionBadgeClass }} align-self-start">
                                            {{ $positionBadgeText }}
                                        </span>
                                        <span>{{ $displayPosisiDanaSaatIni }}</span>
                                    </div>
                                </td>

                                <td>
                                    <div class="small">
                                        <div>{{ $case->owner_last_action_note ?? '-' }}</div>

                                        @if($labPaidAt !== '-' || $installedAt !== '-' || $recognizedAt !== '-')
                                            <div class="text-muted mt-1">
                                                Timeline:
                                                @if($labPaidAt !== '-')
                                                    <span class="me-2">Lab {{ $labPaidAt }}</span>
                                                @endif
                                                @if($installedAt !== '-')
                                                    <span class="me-2">Pasang {{ $installedAt }}</span>
                                                @endif
                                                @if($recognizedAt !== '-')
                                                    <span>Income {{ $recognizedAt }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="{{ route('owner_finance.edit', $case->id) }}"
                                           class="btn btn-outline-primary btn-sm">
                                            Edit
                                        </a>

                                        @if(!empty($trx?->id))
                                            <a href="{{ route('income.edit', $trx->id) }}"
                                               class="btn btn-outline-secondary btn-sm">
                                                Sumber Data
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="19">Belum ada data monitoring tindak lanjut.</td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

            @if(method_exists($cases, 'links'))
                <div class="mt-3">
                    {{ $cases->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="text-muted small mt-3">
        * Modul Owner Finance adalah layer tambahan. Tidak mengubah transaksi lama, kas harian, laba rugi, atau fee dokter.
    </div>
</div>
@endsection