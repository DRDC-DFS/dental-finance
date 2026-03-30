@extends('layouts.app')

@section('content')
@php
    $rupiah = function ($value) {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    };

    $currentTab = $tab ?? 'needs_setup';
    $search = $q ?? '';
    $currentRoute = request()->route()?->getName();
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
                    <a href="{{ route('owner_finance.index', ['tab' => 'needs_setup', 'case_type' => $caseType ?? '', 'q' => $search]) }}"
                       class="btn btn-sm {{ $currentTab === 'needs_setup' ? 'dfs-btn-active btn-outline-danger' : 'btn-outline-danger' }}">
                        Butuh Dilengkapi Owner
                    </a>

                    <a href="{{ route('owner_finance.index', ['tab' => 'monitoring', 'case_type' => $caseType ?? '', 'q' => $search]) }}"
                       class="btn btn-sm {{ $currentTab === 'monitoring' ? 'dfs-btn-active btn-outline-primary' : 'btn-outline-primary' }}">
                        Monitoring Tindak Lanjut
                    </a>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('owner_finance.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="{{ $currentTab }}">

            <div class="col-md-3">
                <label class="form-label mb-1 small text-muted">Tipe Kasus</label>
                <select name="case_type" class="form-select form-select-sm">
                    <option value="" @selected(($caseType ?? '') === '')>Semua</option>
                    <option value="prostodonti" @selected(($caseType ?? '') === 'prostodonti')>Prostodonti</option>
                    <option value="ortho" @selected(($caseType ?? '') === 'ortho')>Ortho</option>
                    <option value="retainer" @selected(($caseType ?? '') === 'retainer')>Retainer</option>
                    <option value="lab" @selected(($caseType ?? '') === 'lab')>Dental Laboratory</option>
                </select>
            </div>

            <div class="col-md-5">
                <label class="form-label mb-1 small text-muted">Cari Invoice / Nama Pasien / Dokter</label>
                <input type="text"
                       name="q"
                       class="form-control form-control-sm"
                       value="{{ $search }}"
                       placeholder="contoh: INV-20260309 atau Ristho">
            </div>

            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-dark btn-sm">Terapkan</button>
                <a href="{{ route('owner_finance.index', ['tab' => $currentTab]) }}"
                   class="btn btn-outline-secondary btn-sm">
                    Reset
                </a>
            </div>
        </form>
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
                            <th>Invoice</th>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Tipe Kasus</th>
                            <th>Status Owner</th>
                            <th>Status Dental Laboratory</th>
                            <th>Status Pemasangan</th>
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
                                $payTotal = (float) ($trx?->pay_total ?? 0);
                                $labBillAmount = (float) ($case->lab_bill_amount ?? 0);
                                $clinicIncomeAmount = (float) ($case->clinic_income_amount ?? 0);

                                $displayDanaAlokasi = '-';
                                $displayTotalDibayarOwner = '-';
                                $displayPosisiDanaSaatIni = '-';

                                if ($case->case_type === 'ortho') {
                                    $displayDanaAlokasi = $rupiah($case->ortho_allocation_amount ?? 0);
                                    $displayTotalDibayarOwner = $rupiah($case->ortho_paid_amount ?? 0);

                                    if ((float) ($case->ortho_remaining_balance ?? 0) <= 0 && $case->lab_paid && $case->installed) {
                                        $displayPosisiDanaSaatIni = 'Selesai';
                                    } elseif ((float) ($case->ortho_remaining_balance ?? 0) > 0) {
                                        $displayPosisiDanaSaatIni = $rupiah($case->ortho_remaining_balance ?? 0) . ' • Cicilan berjalan';
                                    } else {
                                        $displayPosisiDanaSaatIni = $case->posisi_saat_ini ?? '-';
                                    }
                                } elseif ($case->case_type === 'lab') {
                                    $displayDanaAlokasi = $rupiah($payTotal);
                                    $displayTotalDibayarOwner = $rupiah($labBillAmount);

                                    if ($case->lab_paid && $case->installed) {
                                        $displayPosisiDanaSaatIni = $rupiah($clinicIncomeAmount) . ' • Pendapatan klinik';
                                    } else {
                                        $displayPosisiDanaSaatIni = 'Tertahan di Owner Finance';
                                    }
                                } elseif (in_array((string) $case->case_type, ['prostodonti', 'retainer'], true)) {
                                    $displayDanaAlokasi = $rupiah($payTotal);
                                    $displayTotalDibayarOwner = '-';

                                    if ($case->lab_paid && $case->installed) {
                                        $displayPosisiDanaSaatIni = $rupiah($payTotal) . ' • Pendapatan klinik';
                                    } else {
                                        $displayPosisiDanaSaatIni = 'Tertahan • carry forward ke bulan berikutnya';
                                    }
                                }
                            @endphp
                            <tr>
                                <td>{{ optional($trx?->trx_date)->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $trx?->invoice_number ?? '-' }}</td>
                                <td>{{ $trx?->patient?->name ?? '-' }}</td>
                                <td>{{ $trx?->doctor?->name ?? '-' }}</td>

                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $case->case_type_label }}
                                    </span>
                                </td>

                                <td>
                                    @php
                                        $status = strtolower((string) ($case->owner_followup_status ?? ''));
                                    @endphp

                                    @if($status === 'done')
                                        <span class="badge bg-success">SELESAI</span>
                                    @elseif($status === 'in_progress')
                                        <span class="badge bg-primary">BERJALAN</span>
                                    @else
                                        <span class="badge bg-warning text-dark">BUTUH DILENGKAPI</span>
                                    @endif
                                </td>

                                <td>
                                    @if($case->lab_paid)
                                        <span class="badge bg-success">Sudah Dibayar</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Belum Dibayar</span>
                                    @endif
                                </td>

                                <td>
                                    @if($case->installed)
                                        <span class="badge bg-success">Sudah Terpasang</span>
                                    @else
                                        <span class="badge bg-secondary">Belum Terpasang</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    {{ $displayDanaAlokasi }}
                                </td>

                                <td class="text-end">
                                    {{ $displayTotalDibayarOwner }}
                                </td>

                                <td>
                                    {{ $displayPosisiDanaSaatIni }}
                                </td>

                                <td>{{ $case->owner_last_action_note ?? '-' }}</td>

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
                                <td colspan="13">Belum ada data monitoring tindak lanjut.</td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    <div class="text-muted small mt-3">
        * Modul Owner Finance adalah layer tambahan. Tidak mengubah transaksi lama, kas harian, laba rugi, atau fee dokter.
    </div>
</div>
@endsection