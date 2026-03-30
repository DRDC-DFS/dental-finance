@extends('layouts.app')

@section('content')
@php
    $role = strtolower((string) (auth()->user()->role ?? ''));
    $isOwner = $role === 'owner';

    $rows = is_array($rows ?? null) ? $rows : [];
    $paymentDetails = is_array($paymentDetails ?? null) ? $paymentDetails : [];
    $recognizedIncomeDetails = is_array($recognizedIncomeDetails ?? null) ? $recognizedIncomeDetails : [];
    $ownerMutationDetails = is_array($ownerMutationDetails ?? null) ? $ownerMutationDetails : [];
    $dailyTraceDetails = is_array($dailyTraceDetails ?? null) ? $dailyTraceDetails : [];
    $privateOwnerDetails = is_array($privateOwnerDetails ?? null) ? $privateOwnerDetails : [];
    $otherIncomeDetails = is_array($otherIncomeDetails ?? null) ? $otherIncomeDetails : [];
    $privateOwnerSummary = is_array($privateOwnerSummary ?? null) ? $privateOwnerSummary : [
        'income_total' => 0,
        'expense_total' => 0,
        'net_total' => 0,
    ];
    $debugNotes = is_array($debugNotes ?? null) ? $debugNotes : [];

    $today = now()->toDateString();
    $periodStart = request('start', $start ?? $today);
    $periodEnd = request('end', $end ?? $today);
    $singleDate = request('date', $start ?? $today);

    $isSingleDayPeriod = $periodStart === $periodEnd;

    $currentRoute = request()->route()?->getName();
    $isTodayMode = request()->filled('date') || ($periodStart === $today && $periodEnd === $today);
    $isWeeklyMode = $isOwner && ($periodStart === now()->startOfWeek()->toDateString() && $periodEnd === now()->endOfWeek()->toDateString());
    $isMonthlyMode = $isOwner && ($periodStart === now()->startOfMonth()->toDateString() && $periodEnd === now()->endOfMonth()->toDateString());

    $exportParams = $isOwner
        ? ['start' => $periodStart, 'end' => $periodEnd]
        : ['date' => $singleDate];

    $totalsAdmin = [
        'tunai' => 0,
        'bca_transfer' => 0,
        'bca_edc' => 0,
        'bca_qris' => 0,
        'bni_transfer' => 0,
        'bni_edc' => 0,
        'bni_qris' => 0,
        'bri_transfer' => 0,
        'bri_edc' => 0,
        'bri_qris' => 0,
        'lainnya' => 0,
        'other_income_report_total' => 0,
        'other_income_cashflow_total' => 0,
        'total_pembayaran_operasional' => 0,
        'masuk_klinik_reguler' => 0,
        'masuk_kasus_khusus' => 0,
        'keluar_tunai' => 0,
        'keluar_non_tunai' => 0,
        'net_tunai_disetor' => 0,
    ];

    $totalsOwner = [
        'pendapatan_diakui_prostho_retainer' => 0,
        'pendapatan_diakui_dental_lab' => 0,
        'pendapatan_diakui_total' => 0,
        'masuk_klinik_owner_view' => 0,
        'owner_mutation_income' => 0,
        'owner_mutation_expense' => 0,
        'private_owner_income' => 0,
        'private_owner_expense' => 0,
        'private_owner_net' => 0,
        'masuk_total_owner' => 0,
        'keluar_total_owner' => 0,
        'net_total_owner' => 0,
        'masuk_total_klinik' => 0,
        'keluar_total_klinik' => 0,
        'net_kas_klinik' => 0,
    ];

    foreach ($rows as $r) {
        if (!is_array($r)) {
            continue;
        }

        foreach (array_keys($totalsAdmin) as $key) {
            $totalsAdmin[$key] += (float) ($r[$key] ?? 0);
        }

        foreach (array_keys($totalsOwner) as $key) {
            $totalsOwner[$key] += (float) ($r[$key] ?? 0);
        }
    }

    $totalPaymentDetailsAmount = 0;
    foreach ($paymentDetails as $item) {
        $totalPaymentDetailsAmount += (float) ($item['amount'] ?? 0);
    }

    $totalRecognizedLabBill = 0;
    $totalRecognizedClinicIncome = 0;
    foreach ($recognizedIncomeDetails as $item) {
        $totalRecognizedLabBill += (float) ($item['lab_bill_amount'] ?? 0);
        $totalRecognizedClinicIncome += (float) ($item['clinic_income_amount'] ?? 0);
    }

    $totalOwnerMutationAmount = 0;
    foreach ($ownerMutationDetails as $item) {
        $totalOwnerMutationAmount += (float) ($item['amount'] ?? 0);
    }

    $totalOtherIncomeAmount = 0;
    foreach ($otherIncomeDetails as $item) {
        $totalOtherIncomeAmount += (float) ($item['amount'] ?? 0);
    }

    $grandTotalIncome = (float) ($grandTotalIncome ?? 0);
    $grandTotalExpense = (float) ($grandTotalExpense ?? 0);
    $netClinicCashflow = (float) ($netClinicCashflow ?? 0);

    $formatNominal = function ($value) {
        return number_format((float) $value, 0, ',', '.');
    };

    $nominalClass = function ($value) {
        return (float) $value < 0 ? 'text-danger fw-bold' : '';
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

    $periodeLabel = $isSingleDayPeriod
        ? 'Periode: ' . $formatTanggal($periodStart)
        : 'Periode: ' . $formatTanggal($periodStart) . ' s.d. ' . $formatTanggal($periodEnd);

    $ringkasanTotalClinicColspan = $isOwner ? 9 : 7;
@endphp

<div class="container py-4">

    <div class="d-flex flex-column gap-3 mb-3">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div>
                <h4 class="mb-1">Kas Harian</h4>
                <div class="text-success small fw-bold">
                    Default tampilan: data hari berjalan
                </div>
                <div class="text-muted small">
                    {{ $periodeLabel }}
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('reports.daily_cash.index', ['date' => $today]) }}"
                   class="btn btn-outline-secondary btn-sm {{ $isTodayMode ? 'dfs-btn-active' : '' }}">
                    Hari Ini
                </a>

                @if($isOwner)
                    <a href="{{ route('reports.daily_cash.index', ['start' => now()->startOfWeek()->toDateString(), 'end' => now()->endOfWeek()->toDateString()]) }}"
                       class="btn btn-outline-secondary btn-sm {{ $isWeeklyMode ? 'dfs-btn-active' : '' }}">
                        Mingguan
                    </a>

                    <a href="{{ route('reports.daily_cash.index', ['start' => now()->startOfMonth()->toDateString(), 'end' => now()->endOfMonth()->toDateString()]) }}"
                       class="btn btn-outline-secondary btn-sm {{ $isMonthlyMode ? 'dfs-btn-active' : '' }}">
                        Bulanan
                    </a>
                @endif

                <a href="{{ route('reports.daily_cash.index', ['date' => $today]) }}"
                   class="btn btn-outline-secondary btn-sm {{ (!$isWeeklyMode && !$isMonthlyMode && $isTodayMode) ? 'dfs-btn-active' : '' }}">
                    Reset
                </a>

                @if($isOwner)
                    <a href="{{ route('reports.laba-rugi', ['start' => $periodStart, 'end' => $periodEnd]) }}"
                       class="btn btn-outline-secondary btn-sm {{ $currentRoute === 'reports.laba-rugi' ? 'dfs-btn-active' : '' }}">
                        Laba Rugi
                    </a>

                    <a href="{{ route('reports.fee_dokter.index', ['start' => $periodStart, 'end' => $periodEnd]) }}"
                       class="btn btn-outline-secondary btn-sm {{ $currentRoute === 'reports.fee_dokter.index' ? 'dfs-btn-active' : '' }}">
                        Fee Dokter
                    </a>

                    <a href="{{ route('owner_finance.index') }}"
                       class="btn btn-outline-primary btn-sm {{ str_starts_with($currentRoute ?? '', 'owner_finance.') ? 'dfs-btn-active' : '' }}">
                        Owner Finance
                    </a>

                    <a href="{{ route('owner_private.index') }}"
                       class="btn btn-outline-dark btn-sm {{ str_starts_with($currentRoute ?? '', 'owner_private.') ? 'dfs-btn-active' : '' }}">
                        Private Owner
                    </a>
                @endif
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('reports.daily_cash.export.pdf', $exportParams) }}"
               class="btn btn-danger btn-sm">
                Export PDF
            </a>

            <a href="{{ route('reports.daily_cash.export.excel', $exportParams) }}"
               class="btn btn-success btn-sm">
                Export Excel
            </a>
        </div>

        @if($isOwner)
            <form method="GET" action="{{ route('reports.daily_cash.index') }}" class="d-flex align-items-end gap-2 flex-wrap">
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
        @else
            <form method="GET" action="{{ route('reports.daily_cash.index') }}" class="d-flex align-items-end gap-2 flex-wrap">
                <div>
                    <label class="form-label mb-1 small text-muted">Tanggal</label>
                    <input type="date" name="date" value="{{ $singleDate }}" class="form-control form-control-sm">
                </div>

                <button type="submit" class="btn btn-dark btn-sm">
                    Tampilkan
                </button>
            </form>
        @endif
    </div>

    @if($isOwner)
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm h-100 border-success">
                    <div class="card-body">
                        <div class="text-muted small">Total Pendapatan Klinik</div>
                        <div class="fs-4 fw-bold text-success">{{ $formatNominal($grandTotalIncome) }}</div>
                        <div class="small text-muted mt-1">
                            Operasional + kasus khusus diakui + pemasukan lain-lain + private owner masuk
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm h-100 border-danger">
                    <div class="card-body">
                        <div class="text-muted small">Total Pengeluaran Klinik</div>
                        <div class="fs-4 fw-bold text-danger">{{ $formatNominal($grandTotalExpense) }}</div>
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
                        <div class="fs-4 fw-bold {{ $nominalClass($netClinicCashflow) ?: 'text-primary' }}">
                            {{ $formatNominal($netClinicCashflow) }}
                        </div>
                        <div class="small text-muted mt-1">
                            Total pendapatan klinik - total pengeluaran klinik
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="fw-bold mb-1">
                {{ $isOwner ? 'Ringkasan Arus Kas Nyata Harian' : 'Ringkasan Kas Harian' }}
            </div>
            <div class="text-muted small mb-3">
                Tabel ini menampilkan arus kas nyata pada periode terpilih. Tepat di bawah tabel ringkasan akan muncul
                rincian pasien, dokter, kasus/tindakan, pengeluaran, dan pemasukan lain-lain agar tidak perlu mencari sumber data manual.
            </div>

            <div class="alert alert-light border small mb-3">
                <strong>Catatan pembacaan tabel:</strong><br>
                - <strong>Total Penerimaan (Tunai + Bank)</strong> adalah seluruh uang yang benar-benar diterima pada hari tersebut.<br>
                - <strong>Pendapatan Klinik (Reguler)</strong> hanya mencakup transaksi reguler yang langsung menjadi pendapatan klinik.<br>
                - <strong>Pembayaran Kasus Khusus</strong> mencakup pembayaran ortho, retainer, LAB, dan kasus khusus lain yang alurnya dipisahkan dari pendapatan klinik reguler.
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th class="text-end">Total Penerimaan (Tunai + Bank)</th>
                            <th class="text-end">Pendapatan Klinik (Reguler)</th>
                            <th class="text-end">Pemasukan Non-Pasien</th>
                            <th class="text-end">Pembayaran Kasus Khusus</th>
                            <th class="text-end">Pengeluaran Tunai Klinik</th>
                            <th class="text-end">Pengeluaran Non Tunai Klinik</th>
                            <th class="text-end">Net Setoran Kas Harian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                            <tr>
                                <td>{{ $formatTanggal($r['date'] ?? '-') }}</td>
                                <td>{{ $r['payer_label'] ?? '-' }}</td>
                                <td class="text-end {{ $nominalClass($r['total_pembayaran_operasional'] ?? 0) }}">{{ $formatNominal($r['total_pembayaran_operasional'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['masuk_klinik_reguler'] ?? 0) }}">{{ $formatNominal($r['masuk_klinik_reguler'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['other_income_report_total'] ?? 0) }}">{{ $formatNominal($r['other_income_report_total'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['masuk_kasus_khusus'] ?? 0) }}">{{ $formatNominal($r['masuk_kasus_khusus'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['keluar_tunai'] ?? 0) }}">{{ $formatNominal($r['keluar_tunai'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['keluar_non_tunai'] ?? 0) }}">{{ $formatNominal($r['keluar_non_tunai'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['net_tunai_disetor'] ?? 0) }}">{{ $formatNominal($r['net_tunai_disetor'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">Tidak ada data pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(!empty($rows))
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2">TOTAL</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['total_pembayaran_operasional']) }}">{{ $formatNominal($totalsAdmin['total_pembayaran_operasional']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['masuk_klinik_reguler']) }}">{{ $formatNominal($totalsAdmin['masuk_klinik_reguler']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['other_income_report_total']) }}">{{ $formatNominal($totalsAdmin['other_income_report_total']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['masuk_kasus_khusus']) }}">{{ $formatNominal($totalsAdmin['masuk_kasus_khusus']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['keluar_tunai']) }}">{{ $formatNominal($totalsAdmin['keluar_tunai']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['keluar_non_tunai']) }}">{{ $formatNominal($totalsAdmin['keluar_non_tunai']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['net_tunai_disetor']) }}">{{ $formatNominal($totalsAdmin['net_tunai_disetor']) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            @forelse($rows as $r)
                @php
                    $dayKey = (string) ($r['date'] ?? '');
                    $trace = $dailyTraceDetails[$dayKey] ?? ['payments' => [], 'expenses' => [], 'other_incomes' => []];
                    $tracePayments = is_array($trace['payments'] ?? null) ? $trace['payments'] : [];
                    $traceExpenses = is_array($trace['expenses'] ?? null) ? $trace['expenses'] : [];
                    $traceOtherIncomes = is_array($trace['other_incomes'] ?? null) ? $trace['other_incomes'] : [];

                    $tracePaymentTotal = 0;
                    foreach ($tracePayments as $item) {
                        $tracePaymentTotal += (float) ($item['amount'] ?? 0);
                    }

                    $traceExpenseTotal = 0;
                    foreach ($traceExpenses as $item) {
                        $traceExpenseTotal += (float) ($item['amount'] ?? 0);
                    }

                    $traceOtherIncomeTotal = 0;
                    foreach ($traceOtherIncomes as $item) {
                        $traceOtherIncomeTotal += (float) ($item['amount'] ?? 0);
                    }
                @endphp

                @if(!empty($tracePayments) || !empty($traceExpenses) || !empty($traceOtherIncomes))
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="small fw-bold mb-3">
                            Rincian sumber data tanggal {{ $formatTanggal($r['date'] ?? '-') }}
                        </div>

                        @if(!empty($tracePayments))
                            <div class="mb-3">
                                <div class="fw-semibold text-success mb-2">Detail Transaksi Hari Ini</div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 bg-white">
                                        <thead class="table-white">
                                            <tr>
                                                <th>Invoice</th>
                                                <th>Pasien</th>
                                                <th>Dokter</th>
                                                <th>Kasus / Tindakan</th>
                                                <th>Metode</th>
                                                <th>Channel</th>
                                                <th class="text-end">Jumlah</th>
                                                <th class="text-center">Sumber Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($tracePayments as $item)
                                                <tr>
                                                    <td>{{ $item['invoice_number'] ?? '-' }}</td>
                                                    <td>{{ $item['patient_name'] ?? '-' }}</td>
                                                    <td>{{ $item['doctor_name'] ?? '-' }}</td>
                                                    <td>{{ $item['case_or_treatment'] ?? '-' }}</td>
                                                    <td>{{ $item['payment_method_name'] ?? '-' }}</td>
                                                    <td>{{ $item['channel'] ?: '-' }}</td>
                                                    <td class="text-end {{ $nominalClass($item['amount'] ?? 0) }}">{{ $formatNominal($item['amount'] ?? 0) }}</td>
                                                    <td class="text-center">
                                                        @if(!empty($item['transaction_id']))
                                                            <a href="{{ route('income.edit', $item['transaction_id']) }}"
                                                               class="btn btn-sm btn-outline-primary">
                                                                Sumber Data
                                                            </a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light fw-bold">
                                            <tr>
                                                <td colspan="6">TOTAL Transaksi Hari Ini</td>
                                                <td class="text-end {{ $nominalClass($tracePaymentTotal) }}">{{ $formatNominal($tracePaymentTotal) }}</td>
                                                <td class="text-center">-</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif

                        @if(!empty($traceOtherIncomes))
                            <div class="mb-3">
                                <div class="fw-semibold text-primary mb-2">Pemasukan Non-Pasien</div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 bg-white">
                                        <thead class="table-white">
                                            <tr>
                                                <th>Judul</th>
                                                <th>Jenis / Sumber</th>
                                                <th>Metode</th>
                                                <th>Channel</th>
                                                <th>Keterangan Kas Harian</th>
                                                <th class="text-end">Jumlah</th>
                                                <th class="text-center">Sumber Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($traceOtherIncomes as $item)
                                                @php
                                                    $metode = strtolower((string) ($item['payment_method'] ?? 'cash')) === 'bank' ? 'BANK' : 'TUNAI';
                                                    $channel = trim((string) ($item['payment_channel'] ?? ''));
                                                    $includeReport = (bool) ($item['include_in_report'] ?? false);
                                                    $includeCashflow = (bool) ($item['include_in_cashflow'] ?? false);

                                                    $kasInfo = [];
                                                    if ($includeReport) {
                                                        $kasInfo[] = 'Masuk Laporan';
                                                    }
                                                    if ($includeCashflow) {
                                                        $kasInfo[] = 'Masuk Net Setor';
                                                    }
                                                    $kasInfoText = !empty($kasInfo) ? implode(' + ', $kasInfo) : 'Tidak Masuk Kas Harian';
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold">{{ $item['title'] ?? '-' }}</div>
                                                        @if(!empty($item['notes']))
                                                            <div class="small text-muted">{{ $item['notes'] }}</div>
                                                        @endif
                                                    </td>
                                                    <td>{{ $item['source_type'] ?? '-' }}</td>
                                                    <td>{{ $metode }}</td>
                                                    <td>{{ $channel !== '' ? strtoupper($channel) : '-' }}</td>
                                                    <td>{{ $kasInfoText }}</td>
                                                    <td class="text-end {{ $nominalClass($item['amount'] ?? 0) }}">{{ $formatNominal($item['amount'] ?? 0) }}</td>
                                                    <td class="text-center">
                                                        @if(!empty($item['id']))
                                                            <a href="{{ route('other_income.edit', $item['id']) }}"
                                                               class="btn btn-sm btn-outline-primary">
                                                                Sumber Data
                                                            </a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light fw-bold">
                                            <tr>
                                                <td colspan="5">TOTAL Pemasukan Non-Pasien</td>
                                                <td class="text-end {{ $nominalClass($traceOtherIncomeTotal) }}">{{ $formatNominal($traceOtherIncomeTotal) }}</td>
                                                <td class="text-center">-</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif

                        @if(!empty($traceExpenses))
                            <div>
                                <div class="fw-semibold text-danger mb-2">Pengeluaran Klinik</div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 bg-white">
                                        <thead class="table-white">
                                            <tr>
                                                <th>Nama Pengeluaran</th>
                                                <th>Metode Bayar</th>
                                                <th class="text-end">Jumlah</th>
                                                <th class="text-center">Sumber Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($traceExpenses as $item)
                                                <tr>
                                                    <td>{{ $item['name'] ?? '-' }}</td>
                                                    <td>{{ $item['pay_method'] ?? '-' }}</td>
                                                    <td class="text-end {{ $nominalClass(-1 * (float) ($item['amount'] ?? 0)) }}">{{ $formatNominal($item['amount'] ?? 0) }}</td>
                                                    <td class="text-center">
                                                        @if(!empty($item['expense_id']))
                                                            <a href="{{ route('expenses.edit', $item['expense_id']) }}"
                                                               class="btn btn-sm btn-outline-danger">
                                                                Sumber Data
                                                            </a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light fw-bold">
                                            <tr>
                                                <td colspan="2">TOTAL Pengeluaran Klinik</td>
                                                <td class="text-end text-danger fw-bold">{{ $formatNominal($traceExpenseTotal) }}</td>
                                                <td class="text-center">-</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            @empty
            @endforelse
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-body">
            <div class="fw-bold mb-1 text-primary">Ringkasan Total Klinik</div>
            <div class="text-muted small mb-3">
                Ringkasan ini menyatukan arus kas operasional klinik, pemasukan lain-lain, dan transaksi private owner yang memakai uang klinik.
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th class="text-end">Pendapatan Klinik</th>
                            <th class="text-end">Pemasukan Non-Pasien</th>
                            @if($isOwner)
                                <th class="text-end">Masuk Private Owner</th>
                            @endif
                            <th class="text-end">Total Pendapatan Klinik</th>
                            <th class="text-end">Keluar Operasional Klinik</th>
                            @if($isOwner)
                                <th class="text-end">Keluar Private Owner</th>
                            @endif
                            <th class="text-end">Total Pengeluaran Klinik</th>
                            <th class="text-end">Net Pendapatan Klinik</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                            <tr>
                                <td>{{ $formatTanggal($r['date'] ?? '-') }}</td>
                                <td class="text-end">{{ $formatNominal(($r['masuk_klinik_owner_view'] ?? 0) - ($r['other_income_report_total'] ?? 0)) }}</td>
                                <td class="text-end">{{ $formatNominal($r['other_income_report_total'] ?? 0) }}</td>
                                @if($isOwner)
                                    <td class="text-end">{{ $formatNominal($r['private_owner_income'] ?? 0) }}</td>
                                @endif
                                <td class="text-end fw-bold">{{ $formatNominal($r['masuk_total_klinik'] ?? 0) }}</td>
                                <td class="text-end">{{ $formatNominal($r['keluar_klinik'] ?? 0) }}</td>
                                @if($isOwner)
                                    <td class="text-end">{{ $formatNominal($r['private_owner_expense'] ?? 0) }}</td>
                                @endif
                                <td class="text-end fw-bold">{{ $formatNominal($r['keluar_total_klinik'] ?? 0) }}</td>
                                <td class="text-end fw-bold {{ $nominalClass($r['net_kas_klinik'] ?? 0) }}">
                                    {{ $formatNominal($r['net_kas_klinik'] ?? 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $ringkasanTotalClinicColspan }}">Tidak ada data total klinik pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(!empty($rows))
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td>TOTAL</td>
                                <td class="text-end">{{ $formatNominal($totalsOwner['masuk_klinik_owner_view'] - $totalsAdmin['other_income_report_total']) }}</td>
                                <td class="text-end">{{ $formatNominal($totalsAdmin['other_income_report_total']) }}</td>
                                @if($isOwner)
                                    <td class="text-end">{{ $formatNominal($totalsOwner['private_owner_income']) }}</td>
                                @endif
                                <td class="text-end">{{ $formatNominal($grandTotalIncome) }}</td>
                                <td class="text-end">{{ $formatNominal($totalsOwner['keluar_total_owner'] - $totalsOwner['owner_mutation_expense'] - $totalsOwner['private_owner_expense']) }}</td>
                                @if($isOwner)
                                    <td class="text-end">{{ $formatNominal($totalsOwner['private_owner_expense']) }}</td>
                                @endif
                                <td class="text-end">{{ $formatNominal($grandTotalExpense) }}</td>
                                <td class="text-end {{ $nominalClass($netClinicCashflow) }}">{{ $formatNominal($netClinicCashflow) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="fw-bold mb-1">Data Pembayaran Masuk Aktual per Metode Pembayaran</div>
            <div class="text-muted small mb-3">
                Tabel ini membaca seluruh pembayaran yang benar-benar masuk pada periode terpilih, berdasarkan metode pembayaran.
                Nilainya dapat mencakup transaksi reguler, pembayaran kasus khusus, dan pemasukan lain-lain yang ikut cashflow harian.
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th class="text-end">Tunai</th>
                            <th class="text-end">BCA Transfer</th>
                            <th class="text-end">BCA EDC</th>
                            <th class="text-end">BCA QRIS</th>
                            <th class="text-end">BNI Transfer</th>
                            <th class="text-end">BNI EDC</th>
                            <th class="text-end">BNI QRIS</th>
                            <th class="text-end">BRI Transfer</th>
                            <th class="text-end">BRI EDC</th>
                            <th class="text-end">BRI QRIS</th>
                            <th class="text-end">Lainnya</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                            <tr>
                                <td>{{ $formatTanggal($r['date'] ?? '-') }}</td>
                                <td>{{ $r['payer_label'] ?? '-' }}</td>
                                <td class="text-end {{ $nominalClass($r['tunai'] ?? 0) }}">{{ $formatNominal($r['tunai'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['bca_transfer'] ?? 0) }}">{{ $formatNominal($r['bca_transfer'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['bca_edc'] ?? 0) }}">{{ $formatNominal($r['bca_edc'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['bca_qris'] ?? 0) }}">{{ $formatNominal($r['bca_qris'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['bni_transfer'] ?? 0) }}">{{ $formatNominal($r['bni_transfer'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['bni_edc'] ?? 0) }}">{{ $formatNominal($r['bni_edc'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['bni_qris'] ?? 0) }}">{{ $formatNominal($r['bni_qris'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['bri_transfer'] ?? 0) }}">{{ $formatNominal($r['bri_transfer'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['bri_edc'] ?? 0) }}">{{ $formatNominal($r['bri_edc'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['bri_qris'] ?? 0) }}">{{ $formatNominal($r['bri_qris'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($r['lainnya'] ?? 0) }}">{{ $formatNominal($r['lainnya'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13">Tidak ada data pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(!empty($rows))
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2">TOTAL</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['tunai']) }}">{{ $formatNominal($totalsAdmin['tunai']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['bca_transfer']) }}">{{ $formatNominal($totalsAdmin['bca_transfer']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['bca_edc']) }}">{{ $formatNominal($totalsAdmin['bca_edc']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['bca_qris']) }}">{{ $formatNominal($totalsAdmin['bca_qris']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['bni_transfer']) }}">{{ $formatNominal($totalsAdmin['bni_transfer']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['bni_edc']) }}">{{ $formatNominal($totalsAdmin['bni_edc']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['bni_qris']) }}">{{ $formatNominal($totalsAdmin['bni_qris']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['bri_transfer']) }}">{{ $formatNominal($totalsAdmin['bri_transfer']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['bri_edc']) }}">{{ $formatNominal($totalsAdmin['bri_edc']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['bri_qris']) }}">{{ $formatNominal($totalsAdmin['bri_qris']) }}</td>
                                <td class="text-end {{ $nominalClass($totalsAdmin['lainnya']) }}">{{ $formatNominal($totalsAdmin['lainnya']) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="fw-bold mb-1">Detail Transaksi Masuk Klinik Reguler / Admin</div>
            <div class="text-muted small mb-3">
                Tabel ini hanya menampilkan transaksi reguler / non Owner Finance agar sinkron dengan Laba Rugi dan Fee Dokter.
                Pembayaran kasus khusus tidak ditampilkan di tabel detail ini.
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Invoice</th>
                            <th>Pasien</th>
                            <th>Kategori Pasien</th>
                            <th>Metode</th>
                            <th>Channel</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-center">Sumber Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $currentInvoice = null;
                            $invoiceSubtotal = 0;
                        @endphp

                        @forelse($paymentDetails as $index => $d)
                            @php
                                $invoiceNumber = $d['invoice_number'] ?? '-';
                                $amount = (float) ($d['amount'] ?? 0);

                                if ($currentInvoice === null) {
                                    $currentInvoice = $invoiceNumber;
                                }

                                if ($invoiceNumber !== $currentInvoice) {
                                    $previousTransactionId = $paymentDetails[$index - 1]['transaction_id'] ?? null;
                            @endphp
                                    <tr class="table-light fw-bold">
                                        <td colspan="6" class="text-end">Subtotal Invoice {{ $currentInvoice }}</td>
                                        <td class="text-end {{ $nominalClass($invoiceSubtotal) }}">{{ $formatNominal($invoiceSubtotal) }}</td>
                                        <td class="text-center">
                                            @if(!empty($previousTransactionId))
                                                <a href="{{ route('income.edit', $previousTransactionId) }}"
                                                   class="btn btn-sm btn-outline-secondary">
                                                    Sumber Data
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                            @php
                                    $currentInvoice = $invoiceNumber;
                                    $invoiceSubtotal = 0;
                                }

                                $invoiceSubtotal += $amount;
                            @endphp

                            <tr>
                                <td>{{ $formatTanggal($d['date'] ?? '-') }}</td>
                                <td>{{ $invoiceNumber }}</td>
                                <td>{{ $d['patient_name'] ?? '-' }}</td>
                                <td>{{ $d['payer_label'] ?? '-' }}</td>
                                <td>{{ $d['payment_method_name'] ?? '-' }}</td>
                                <td>{{ $d['channel'] ?? '-' }}</td>
                                <td class="text-end {{ $nominalClass($amount) }}">{{ $formatNominal($amount) }}</td>
                                <td class="text-center">
                                    @if(!empty($d['transaction_id']))
                                        <a href="{{ route('income.edit', $d['transaction_id']) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            Sumber Data
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>

                            @if($loop->last)
                                <tr class="table-light fw-bold">
                                    <td colspan="6" class="text-end">Subtotal Invoice {{ $currentInvoice }}</td>
                                    <td class="text-end {{ $nominalClass($invoiceSubtotal) }}">{{ $formatNominal($invoiceSubtotal) }}</td>
                                    <td class="text-center">
                                        @if(!empty($d['transaction_id']))
                                            <a href="{{ route('income.edit', $d['transaction_id']) }}"
                                               class="btn btn-sm btn-outline-secondary">
                                                Sumber Data
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="8">Tidak ada transaksi masuk klinik reguler/admin pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="6">TOTAL SELURUH TRANSAKSI REGULER / ADMIN</td>
                            <td class="text-end {{ $nominalClass($totalPaymentDetailsAmount) }}">{{ $formatNominal($totalPaymentDetailsAmount) }}</td>
                            <td class="text-center">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-info">
        <div class="card-body">
            <div class="fw-bold mb-1 text-info-emphasis">Detail Pemasukan Lain-lain</div>
            <div class="text-muted small mb-3">
                Tabel ini menampilkan pemasukan non-pasien yang dicatat admin. Data tetap terpisah dari invoice pasien, tetapi ikut laporan harian sesuai flag yang dipilih saat input.
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Judul</th>
                            <th>Jenis / Sumber</th>
                            <th>Metode</th>
                            <th>Channel</th>
                            <th>Keterangan Kas Harian</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-center">Sumber Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($otherIncomeDetails as $item)
                            @php
                                $metode = strtolower((string) ($item['payment_method'] ?? 'cash')) === 'bank' ? 'BANK' : 'TUNAI';
                                $channel = trim((string) ($item['payment_channel'] ?? ''));
                                $includeReport = (bool) ($item['include_in_report'] ?? false);
                                $includeCashflow = (bool) ($item['include_in_cashflow'] ?? false);

                                $kasInfo = [];
                                if ($includeReport) {
                                    $kasInfo[] = 'Masuk Laporan';
                                }
                                if ($includeCashflow) {
                                    $kasInfo[] = 'Masuk Net Setor';
                                }
                                $kasInfoText = !empty($kasInfo) ? implode(' + ', $kasInfo) : 'Tidak Masuk Kas Harian';
                            @endphp
                            <tr>
                                <td>{{ $formatTanggal($item['trx_date'] ?? '-') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $item['title'] ?? '-' }}</div>
                                    @if(!empty($item['notes']))
                                        <div class="small text-muted">{{ $item['notes'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $item['source_type'] ?? '-' }}</td>
                                <td>{{ $metode }}</td>
                                <td>{{ $channel !== '' ? strtoupper($channel) : '-' }}</td>
                                <td>{{ $kasInfoText }}</td>
                                <td class="text-end {{ $nominalClass($item['amount'] ?? 0) }}">{{ $formatNominal($item['amount'] ?? 0) }}</td>
                                <td class="text-center">
                                    @if(!empty($item['id']))
                                        <a href="{{ route('other_income.edit', $item['id']) }}"
                                           class="btn btn-sm btn-outline-info">
                                            Sumber Data
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">Belum ada pemasukan lain-lain pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="6">TOTAL PEMASUKAN LAIN-LAIN</td>
                            <td class="text-end {{ $nominalClass($totalOtherIncomeAmount) }}">{{ $formatNominal($totalOtherIncomeAmount) }}</td>
                            <td class="text-center">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @if($isOwner)
        <div class="card shadow-sm mb-4 border-primary">
            <div class="card-body">
                <div class="fw-bold mb-1 text-primary">Data Private Owner</div>
                <div class="text-muted small mb-3">
                    Tabel ini memakai <strong>pendapatan klinik yang diakui</strong>, bukan selalu tanggal pembayaran pasien.
                    Karena itu, tanggal pengakuan pendapatan bisa berbeda dari tanggal uang dibayar. Pendapatan kasus khusus
                    hanya muncul di sini jika rule Owner Finance sudah terpenuhi.
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th class="text-end">Pendapatan Diakui Prosto / Retainer</th>
                                <th class="text-end">Pendapatan Klinik Dental Laboratory</th>
                                <th class="text-end">Total Pendapatan Klinik Diakui</th>
                                <th class="text-end">Masuk Klinik (Owner View)</th>
                                <th class="text-end">Masuk Mutasi Owner</th>
                                <th class="text-end">Keluar Mutasi Owner</th>
                                <th class="text-end">Pemasukan Private Owner</th>
                                <th class="text-end">Pengeluaran Private Owner</th>
                                <th class="text-end">Net Private Owner</th>
                                <th class="text-end">Masuk Total Owner</th>
                                <th class="text-end">Keluar Total Owner</th>
                                <th class="text-end">Net Total Owner</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $r)
                                <tr>
                                    <td>{{ $formatTanggal($r['date'] ?? '-') }}</td>
                                    <td class="text-end {{ $nominalClass($r['pendapatan_diakui_prostho_retainer'] ?? 0) }}">{{ $formatNominal($r['pendapatan_diakui_prostho_retainer'] ?? 0) }}</td>
                                    <td class="text-end {{ $nominalClass($r['pendapatan_diakui_dental_lab'] ?? 0) }}">{{ $formatNominal($r['pendapatan_diakui_dental_lab'] ?? 0) }}</td>
                                    <td class="text-end {{ $nominalClass($r['pendapatan_diakui_total'] ?? 0) }}">{{ $formatNominal($r['pendapatan_diakui_total'] ?? 0) }}</td>
                                    <td class="text-end {{ $nominalClass($r['masuk_klinik_owner_view'] ?? 0) }}">{{ $formatNominal($r['masuk_klinik_owner_view'] ?? 0) }}</td>
                                    <td class="text-end {{ $nominalClass($r['owner_mutation_income'] ?? 0) }}">{{ $formatNominal($r['owner_mutation_income'] ?? 0) }}</td>
                                    <td class="text-end {{ $nominalClass($r['owner_mutation_expense'] ?? 0) }}">{{ $formatNominal($r['owner_mutation_expense'] ?? 0) }}</td>
                                    <td class="text-end {{ $nominalClass($r['private_owner_income'] ?? 0) }}">{{ $formatNominal($r['private_owner_income'] ?? 0) }}</td>
                                    <td class="text-end {{ $nominalClass($r['private_owner_expense'] ?? 0) }}">{{ $formatNominal($r['private_owner_expense'] ?? 0) }}</td>
                                    <td class="text-end {{ $nominalClass($r['private_owner_net'] ?? 0) }}">{{ $formatNominal($r['private_owner_net'] ?? 0) }}</td>
                                    <td class="text-end {{ $nominalClass($r['masuk_total_owner'] ?? 0) }}">{{ $formatNominal($r['masuk_total_owner'] ?? 0) }}</td>
                                    <td class="text-end {{ $nominalClass($r['keluar_total_owner'] ?? 0) }}">{{ $formatNominal($r['keluar_total_owner'] ?? 0) }}</td>
                                    <td class="text-end {{ $nominalClass($r['net_total_owner'] ?? 0) }}">{{ $formatNominal($r['net_total_owner'] ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13">Tidak ada data private owner pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(!empty($rows))
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td>TOTAL</td>
                                    <td class="text-end {{ $nominalClass($totalsOwner['pendapatan_diakui_prostho_retainer']) }}">{{ $formatNominal($totalsOwner['pendapatan_diakui_prostho_retainer']) }}</td>
                                    <td class="text-end {{ $nominalClass($totalsOwner['pendapatan_diakui_dental_lab']) }}">{{ $formatNominal($totalsOwner['pendapatan_diakui_dental_lab']) }}</td>
                                    <td class="text-end {{ $nominalClass($totalsOwner['pendapatan_diakui_total']) }}">{{ $formatNominal($totalsOwner['pendapatan_diakui_total']) }}</td>
                                    <td class="text-end {{ $nominalClass($totalsOwner['masuk_klinik_owner_view']) }}">{{ $formatNominal($totalsOwner['masuk_klinik_owner_view']) }}</td>
                                    <td class="text-end {{ $nominalClass($totalsOwner['owner_mutation_income']) }}">{{ $formatNominal($totalsOwner['owner_mutation_income']) }}</td>
                                    <td class="text-end {{ $nominalClass($totalsOwner['owner_mutation_expense']) }}">{{ $formatNominal($totalsOwner['owner_mutation_expense']) }}</td>
                                    <td class="text-end {{ $nominalClass($totalsOwner['private_owner_income']) }}">{{ $formatNominal($totalsOwner['private_owner_income']) }}</td>
                                    <td class="text-end {{ $nominalClass($totalsOwner['private_owner_expense']) }}">{{ $formatNominal($totalsOwner['private_owner_expense']) }}</td>
                                    <td class="text-end {{ $nominalClass($totalsOwner['private_owner_net']) }}">{{ $formatNominal($totalsOwner['private_owner_net']) }}</td>
                                    <td class="text-end {{ $nominalClass($totalsOwner['masuk_total_owner']) }}">{{ $formatNominal($totalsOwner['masuk_total_owner']) }}</td>
                                    <td class="text-end {{ $nominalClass($totalsOwner['keluar_total_owner']) }}">{{ $formatNominal($totalsOwner['keluar_total_owner']) }}</td>
                                    <td class="text-end {{ $nominalClass($totalsOwner['net_total_owner']) }}">{{ $formatNominal($totalsOwner['net_total_owner']) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-warning">
            <div class="card-body">
                <div class="fw-bold mb-1 text-warning-emphasis">Ringkasan Private Owner</div>
                <div class="text-muted small mb-3">
                    Blok ini khusus transaksi private owner. Tabel operasional yang sudah berjalan tetap dipertahankan dan tidak diubah.
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-end">Total Pemasukan Private</th>
                                <th class="text-end">Total Pengeluaran Private</th>
                                <th class="text-end">Net Private Owner</th>
                            </tr>
                        </thead>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td class="text-end">{{ $formatNominal($privateOwnerSummary['income_total'] ?? 0) }}</td>
                                <td class="text-end">{{ $formatNominal($privateOwnerSummary['expense_total'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($privateOwnerSummary['net_total'] ?? 0) }}">{{ $formatNominal($privateOwnerSummary['net_total'] ?? 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="fw-bold mb-3">Detail Transaksi Private Owner</div>
                <div class="text-muted small mb-3">
                    Detail ini terpisah dari operasional klinik agar owner mudah membedakan transaksi admin dan transaksi private.
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Tipe</th>
                                <th>Keterangan</th>
                                <th>Metode</th>
                                <th class="text-end">Masuk</th>
                                <th class="text-end">Keluar</th>
                                <th>Catatan</th>
                                <th class="text-center">Sumber Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($privateOwnerDetails as $item)
                                @php
                                    $isIncome = ($item['type'] ?? '') === 'income';
                                    $masuk = $isIncome ? (float) ($item['amount'] ?? 0) : 0;
                                    $keluar = !$isIncome ? (float) ($item['amount'] ?? 0) : 0;
                                @endphp
                                <tr>
                                    <td>{{ $formatTanggal($item['trx_date'] ?? '-') }}</td>
                                    <td>
                                        @if($isIncome)
                                            <span class="badge text-bg-success">Pemasukan Private</span>
                                        @else
                                            <span class="badge text-bg-danger">Pengeluaran Private</span>
                                        @endif
                                    </td>
                                    <td>{{ $item['description'] ?? '-' }}</td>
                                    <td>{{ $item['payment_method'] ?? '-' }}</td>
                                    <td class="text-end">{{ $masuk > 0 ? $formatNominal($masuk) : '-' }}</td>
                                    <td class="text-end">{{ $keluar > 0 ? $formatNominal($keluar) : '-' }}</td>
                                    <td>{{ ($item['notes'] ?? '') !== '' ? $item['notes'] : '-' }}</td>
                                    <td class="text-center">
                                        @if(!empty($item['id']))
                                            <a href="{{ route('owner_private.edit', $item['id']) }}"
                                               class="btn btn-sm btn-outline-warning">
                                                Sumber Data
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">Belum ada transaksi private owner pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4">TOTAL PRIVATE OWNER</td>
                                <td class="text-end">{{ $formatNominal($privateOwnerSummary['income_total'] ?? 0) }}</td>
                                <td class="text-end">{{ $formatNominal($privateOwnerSummary['expense_total'] ?? 0) }}</td>
                                <td class="text-end {{ $nominalClass($privateOwnerSummary['net_total'] ?? 0) }}">
                                    Net: {{ $formatNominal($privateOwnerSummary['net_total'] ?? 0) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="fw-bold mb-3">Detail Pendapatan Klinik Diakui (Private Owner)</div>
                <div class="text-muted small mb-3">
                    Detail ini mengikuti tanggal <strong>pengakuan pendapatan</strong>, bukan selalu tanggal pembayaran pasien.
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal Diakui</th>
                                <th>Invoice</th>
                                <th>Pasien</th>
                                <th>Jenis Kasus</th>
                                <th class="text-end">Biaya Vendor / LAB</th>
                                <th class="text-end">Pendapatan Klinik Diakui</th>
                                <th class="text-center">Sumber Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recognizedIncomeDetails as $item)
                                <tr>
                                    <td>{{ $formatTanggal($item['recognized_date'] ?? '-') }}</td>
                                    <td>{{ $item['invoice_number'] ?? '-' }}</td>
                                    <td>{{ $item['patient_name'] ?? '-' }}</td>
                                    <td>{{ $item['case_type_label'] ?? '-' }}</td>
                                    <td class="text-end {{ $nominalClass($item['lab_bill_amount'] ?? 0) }}">{{ $formatNominal($item['lab_bill_amount'] ?? 0) }}</td>
                                    <td class="text-end {{ $nominalClass($item['clinic_income_amount'] ?? 0) }}">{{ $formatNominal($item['clinic_income_amount'] ?? 0) }}</td>
                                    <td class="text-center">
                                        @if(!empty($item['transaction_id']))
                                            <a href="{{ route('income.edit', $item['transaction_id']) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                Sumber Data
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">Belum ada pendapatan klinik diakui pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4">TOTAL PENDAPATAN KLINIK DIAKUI</td>
                                <td class="text-end {{ $nominalClass($totalRecognizedLabBill) }}">{{ $formatNominal($totalRecognizedLabBill) }}</td>
                                <td class="text-end {{ $nominalClass($totalRecognizedClinicIncome) }}">{{ $formatNominal($totalRecognizedClinicIncome) }}</td>
                                <td class="text-center">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="fw-bold mb-3">Detail Mutasi Akun Owner</div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis Mutasi</th>
                                <th>Deskripsi</th>
                                <th>Referensi Bulan</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ownerMutationDetails as $item)
                                <tr>
                                    <td>{{ $formatTanggal($item['date'] ?? '-') }}</td>
                                    <td>{{ $item['mutation_type_label'] ?? '-' }}</td>
                                    <td>{{ $item['description'] ?? '-' }}</td>
                                    <td>{{ $item['reference_month'] ?? '-' }}</td>
                                    <td class="text-end {{ $nominalClass($item['amount'] ?? 0) }}">{{ $formatNominal($item['amount'] ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">Belum ada mutasi akun owner pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4">TOTAL MUTASI AKUN OWNER</td>
                                <td class="text-end {{ $nominalClass($totalOwnerMutationAmount) }}">{{ $formatNominal($totalOwnerMutationAmount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection