@extends('layouts.app')

@section('content')
@php
    $trx = $incomeTransaction;
    $isEdit = ($mode ?? 'create') === 'edit';
    $installments = $installments ?? collect();
    $monthlyLedgers = $ownerFinanceCase->monthlyLedgers ?? collect();

    $rupiahOld = function ($value) {
        return number_format((float) $value, 0, ',', '.');
    };

    $formatTanggal = function ($value) {
        if (!$value) {
            return '-';
        }

        return \Carbon\Carbon::parse($value)->format('d-m-Y');
    };

    $formatDateTime = function ($value) {
        if (!$value) {
            return '-';
        }

        return \Carbon\Carbon::parse($value)->format('d-m-Y H:i');
    };

    $formatPeriode = function ($value) {
        if (!$value) {
            return '-';
        }

        return \Carbon\Carbon::parse($value)->translatedFormat('F Y');
    };

    $selectedCaseType = old('case_type', $ownerFinanceCase->case_type ?? 'prostodonti');
    $isOrthoCase = $selectedCaseType === 'ortho';
    $isProsthoCase = in_array($selectedCaseType, ['prostodonti', 'retainer', 'lab'], true);
    $isDentalLaboratoryCase = $selectedCaseType === 'lab';
    $nextInstallmentNo = ($installments->max('installment_no') ?? 0) + 1;

    $isOrthoDone = $isEdit
        && ($ownerFinanceCase->case_type ?? '') === 'ortho'
        && (bool) ($ownerFinanceCase->lab_paid ?? false)
        && (bool) ($ownerFinanceCase->installed ?? false)
        && (float) ($ownerFinanceCase->ortho_remaining_balance ?? 0) <= 0;

    $displayOwnerStatus = $isOrthoDone
        ? 'SELESAI'
        : strtoupper((string) ($ownerFinanceCase->owner_status_label ?? 'PENDING'));

    $displayPosisi = $isOrthoDone
        ? 'SELESAI'
        : (string) ($ownerFinanceCase->posisi_saat_ini ?? '-');

    $monthlyLedgerIncomeTotal = (float) $monthlyLedgers->sum('income_amount');
    $monthlyLedgerOpeningTotal = (float) $monthlyLedgers->sum('opening_balance');
    $monthlyLedgerInstallmentTotal = (float) $monthlyLedgers->sum('installment_paid');
    $monthlyLedgerExpenseTotal = (float) $monthlyLedgers->sum('expense_end_month');
    $monthlyLedgerClosingTotal = (float) $monthlyLedgers->sum('closing_balance');

    $trxPayTotal = (float) ($trx->pay_total ?? 0);
    $labBillAmount = (float) ($ownerFinanceCase->lab_bill_amount ?? 0);
    $clinicIncomeAmount = (float) ($ownerFinanceCase->clinic_income_amount ?? 0);

    $clinicIncomePreviewValue = ($isEdit && $isProsthoCase)
        ? 'Rp ' . number_format($clinicIncomeAmount, 0, ',', '.')
        : 'Akan dihitung otomatis saat disimpan';

    $revenueRecognizedPreviewValue = $formatDateTime($ownerFinanceCase->revenue_recognized_at);

    $specialCasePaymentLabel = $isDentalLaboratoryCase ? 'Pembayaran Dental Laboratory' : 'Biaya Dental Laboratory (Vendor)';
    $specialCaseIncomeLabel = 'Pendapatan Klinik';
    $specialCaseDetailLabel = $isDentalLaboratoryCase ? 'Detail Dental Laboratory' : 'Detail Kasus untuk Dental Laboratory';
@endphp

<div class="container py-4">
    <div class="d-flex flex-column gap-3 mb-3">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div>
                <h4 class="mb-1">{{ $isEdit ? 'Edit Owner Finance Case' : 'Tambah Owner Finance Case' }}</h4>
                <div class="text-danger small fw-bold">
                    ✅ OWNER Only • Modul privat tidak mengubah transaksi admin
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('owner_finance.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
                @if($trx)
                    <a href="{{ route('income.edit', ['income' => $trx->id]) }}" class="btn btn-outline-secondary btn-sm">
                        Buka Transaksi
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($isOrthoDone)
        <div class="alert alert-success border-success shadow-sm">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <div class="fw-bold fs-5">SELESAI</div>
                    <div class="small">
                        Kasus ortho ini sudah selesai karena <b>Dental Laboratory sudah dibayar</b>, <b>sudah terpasang</b>, dan <b>sisa dana ortho = 0</b>.
                    </div>
                </div>
                <span class="badge bg-success fs-6 px-3 py-2">SELESAI</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="fw-bold mb-2">Ringkasan Transaksi</div>

            @if($trx)
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="small text-muted">Invoice</div>
                        <div class="fw-semibold">{{ $trx->invoice_number }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Tanggal</div>
                        <div class="fw-semibold">{{ $formatTanggal(optional($trx->trx_date)->format('Y-m-d')) }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Pasien</div>
                        <div class="fw-semibold">{{ $trx->patient?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Dokter</div>
                        <div class="fw-semibold">{{ $trx->doctor?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Status Transaksi</div>
                        <div class="fw-semibold">{{ strtoupper((string) $trx->status) }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Bill Total</div>
                        <div class="fw-semibold">Rp {{ number_format((float) $trx->bill_total, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Pay Total</div>
                        <div class="fw-semibold">Rp {{ number_format($trxPayTotal, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Visibility</div>
                        <div class="fw-semibold">{{ strtoupper((string) $trx->visibility) }}</div>
                    </div>

                    @if($isEdit)
                        <div class="col-md-3">
                            <div class="small text-muted">Status Owner Finance</div>
                            <div class="fw-semibold">
                                @if($isOrthoDone)
                                    <span class="badge bg-success">SELESAI</span>
                                @elseif(strtolower((string) ($ownerFinanceCase->owner_followup_status ?? '')) === 'in_progress')
                                    <span class="badge bg-primary">BERJALAN</span>
                                @elseif(strtolower((string) ($ownerFinanceCase->owner_followup_status ?? '')) === 'followed_up')
                                    <span class="badge bg-info text-dark">SUDAH DITINDAKLANJUTI</span>
                                @elseif(strtolower((string) ($ownerFinanceCase->owner_followup_status ?? '')) === 'needs_setup')
                                    <span class="badge bg-warning text-dark">BUTUH DILENGKAPI</span>
                                @else
                                    <span class="badge bg-secondary">{{ $displayOwnerStatus }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Posisi Saat Ini</div>
                            <div class="fw-semibold">
                                @if($isOrthoDone)
                                    <span class="text-success fw-bold">SELESAI</span>
                                @else
                                    {{ $displayPosisi }}
                                @endif
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Total Dibayar Owner</div>
                            <div class="fw-semibold">Rp {{ number_format((float) ($ownerFinanceCase->ortho_paid_amount ?? 0), 0, ',', '.') }}</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Sisa Dana Ortho</div>
                            <div class="fw-semibold {{ $isOrthoDone ? 'text-success' : '' }}">
                                Rp {{ number_format((float) ($ownerFinanceCase->ortho_remaining_balance ?? 0), 0, ',', '.') }}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Tanggal Pembayaran Lab</div>
                            <div class="fw-semibold">{{ $formatTanggal(optional($ownerFinanceCase->lab_paid_at)->format('Y-m-d')) }}</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Tanggal Pemasangan</div>
                            <div class="fw-semibold">{{ $formatTanggal(optional($ownerFinanceCase->installed_at)->format('Y-m-d')) }}</div>
                        </div>

                        @if($isProsthoCase)
                            <div class="col-md-3">
                                <div class="small text-muted">Jenis Kasus Khusus</div>
                                <div class="fw-semibold">{{ $ownerFinanceCase->prostho_case_type_label ?? '-' }}</div>
                            </div>
                            <div class="col-md-9">
                                <div class="small text-muted">{{ $specialCaseDetailLabel }}</div>
                                <div class="fw-semibold">{{ $ownerFinanceCase->prostho_case_detail ?: '-' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted">{{ $specialCasePaymentLabel }}</div>
                                <div class="fw-semibold">Rp {{ number_format($labBillAmount, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted">{{ $specialCaseIncomeLabel }}</div>
                                <div class="fw-semibold text-success">Rp {{ number_format($clinicIncomeAmount, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted">Revenue Recognized At</div>
                                <div class="fw-semibold">{{ $formatDateTime($ownerFinanceCase->revenue_recognized_at) }}</div>
                            </div>
                        @endif
                    @endif
                </div>
            @else
                <div class="text-muted">Belum ada transaksi dipilih.</div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="POST" action="{{ $isEdit ? route('owner_finance.update', $ownerFinanceCase->id) : route('owner_finance.store') }}">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                @if(!$isEdit)
                    <div class="mb-3">
                        <label class="form-label">Pilih Transaksi</label>
                        @if($trx)
                            <input type="hidden" name="income_transaction_id" value="{{ $trx->id }}">
                            <input type="text" class="form-control"
                                   value="{{ $trx->invoice_number }} - {{ $trx->patient?->name ?? '-' }} - Rp {{ number_format((float) $trx->pay_total, 0, ',', '.') }}"
                                   readonly>
                        @else
                            <select name="income_transaction_id" class="form-select" required>
                                <option value="">- pilih transaksi -</option>
                                @foreach($transactions as $t)
                                    <option value="{{ $t->id }}" @selected(old('income_transaction_id') == $t->id)>
                                        {{ $t->invoice_number }} | {{ optional($t->trx_date)->format('Y-m-d') }} | {{ $t->patient?->name ?? '-' }} | Rp {{ number_format((float) $t->pay_total, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tipe Kasus</label>
                        <select name="case_type" id="case_type" class="form-select" required>
                            <option value="prostodonti" @selected($selectedCaseType === 'prostodonti')>Prostodonti</option>
                            <option value="ortho" @selected($selectedCaseType === 'ortho')>Ortho</option>
                            <option value="retainer" @selected($selectedCaseType === 'retainer')>Retainer</option>
                            <option value="lab" @selected($selectedCaseType === 'lab')>Dental Laboratory</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Dental Laboratory Sudah Dibayar?</label>
                        <select name="lab_paid" id="lab_paid" class="form-select">
                            <option value="0" @selected((string) old('lab_paid', (int) ($ownerFinanceCase->lab_paid ?? false)) === '0')>Belum</option>
                            <option value="1" @selected((string) old('lab_paid', (int) ($ownerFinanceCase->lab_paid ?? false)) === '1')>Sudah</option>
                        </select>
                    </div>

                    <div class="col-md-4 all-case-date-block" id="lab_paid_date_block">
                        <label class="form-label">Tanggal Pembayaran Lab</label>
                        <input type="date"
                               name="lab_paid_at"
                               id="lab_paid_at"
                               class="form-control"
                               value="{{ old('lab_paid_at', optional($ownerFinanceCase->lab_paid_at)->format('Y-m-d')) }}">
                        <div class="form-text">
                            Isi tanggal real saat pembayaran Lab dilakukan. Boleh tanggal lampau.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Sudah Terpasang?</label>
                        <select name="installed" id="installed" class="form-select">
                            <option value="0" @selected((string) old('installed', (int) ($ownerFinanceCase->installed ?? false)) === '0')>Belum</option>
                            <option value="1" @selected((string) old('installed', (int) ($ownerFinanceCase->installed ?? false)) === '1')>Sudah</option>
                        </select>
                    </div>

                    <div class="col-md-4 all-case-date-block" id="installed_date_block">
                        <label class="form-label">Tanggal Pemasangan</label>
                        <input type="date"
                               name="installed_at"
                               id="installed_at"
                               class="form-control"
                               value="{{ old('installed_at', optional($ownerFinanceCase->installed_at)->format('Y-m-d')) }}">
                        <div class="form-text">
                            Isi tanggal real saat kasus selesai dipasang. Boleh tanggal lampau.
                        </div>
                    </div>

                    <div class="col-md-4 ortho-only">
                        <label class="form-label">Dana Alokasi Ortho</label>
                        <input type="text"
                               name="ortho_allocation_amount"
                               class="form-control rupiah-input"
                               value="{{ old('ortho_allocation_amount', $rupiahOld($ownerFinanceCase->ortho_allocation_amount ?? 0)) }}"
                               placeholder="contoh: 5.000.000">
                    </div>

                    <div class="col-md-4 ortho-only">
                        <label class="form-label">Mode Pembayaran Ortho</label>
                        <select name="ortho_payment_mode" class="form-select">
                            <option value="full" @selected(old('ortho_payment_mode', $ownerFinanceCase->ortho_payment_mode ?? 'installments') === 'full')>Sekali Bayar</option>
                            <option value="installments" @selected(old('ortho_payment_mode', $ownerFinanceCase->ortho_payment_mode ?? 'installments') === 'installments')>Cicilan</option>
                        </select>
                    </div>

                    <div class="col-md-4 ortho-only">
                        <label class="form-label">Jumlah Termin</label>
                        <input type="number"
                               name="ortho_installment_count"
                               class="form-control"
                               min="1"
                               max="36"
                               value="{{ old('ortho_installment_count', $ownerFinanceCase->ortho_installment_count ?? 3) }}">
                    </div>

                    <div class="col-md-4 ortho-only">
                        <label class="form-label">Total Cicilan Dibayar Owner</label>
                        <input type="text"
                               name="ortho_paid_amount"
                               class="form-control rupiah-input"
                               value="{{ old('ortho_paid_amount', $rupiahOld($ownerFinanceCase->ortho_paid_amount ?? 0)) }}"
                               placeholder="contoh: 2.000.000"
                               @if($installments->count() > 0) readonly @endif>
                        <div class="form-text">
                            @if($installments->count() > 0)
                                Nilai ini otomatis dihitung dari histori cicilan owner di bawah.
                            @else
                                Jika histori cicilan belum dibuat, Anda masih bisa isi total akumulasi manual sementara.
                            @endif
                        </div>
                    </div>

                    <div class="col-md-4 ortho-only">
                        <label class="form-label">Sisa Dana Ortho</label>
                        <input type="text"
                               class="form-control"
                               value="Rp {{ number_format((float) ($ownerFinanceCase->ortho_remaining_balance ?? 0), 0, ',', '.') }}"
                               readonly>
                    </div>

                    <div class="col-md-4 prostho-only">
                        <label class="form-label">Jenis Kasus Khusus</label>
                        <select name="prostho_case_type" class="form-select">
                            <option value="">- pilih jenis kasus -</option>
                            <option value="lepasan" @selected(old('prostho_case_type', $ownerFinanceCase->prostho_case_type ?? '') === 'lepasan')>Gigi Tiruan Lepasan</option>
                            <option value="bridge" @selected(old('prostho_case_type', $ownerFinanceCase->prostho_case_type ?? '') === 'bridge')>Bridge</option>
                            <option value="implant" @selected(old('prostho_case_type', $ownerFinanceCase->prostho_case_type ?? '') === 'implant')>Implan</option>
                            <option value="retainer" @selected(old('prostho_case_type', $ownerFinanceCase->prostho_case_type ?? '') === 'retainer')>Retainer</option>
                            <option value="lab" @selected(old('prostho_case_type', $ownerFinanceCase->prostho_case_type ?? '') === 'lab')>Dental Laboratory</option>
                        </select>
                    </div>

                    <div class="col-md-8 prostho-only">
                        <label class="form-label">{{ $specialCaseDetailLabel }}</label>
                        <textarea name="prostho_case_detail"
                                  class="form-control"
                                  rows="2"
                                  placeholder="Contoh: Bridge 3 unit zirconia shade A2 / Lepasan akrilik rahang atas / Retainer essix / pekerjaan Dental Laboratory khusus">{{ old('prostho_case_detail', $ownerFinanceCase->prostho_case_detail ?? '') }}</textarea>
                        <div class="form-text">
                            Isi manual sebagai keterangan kerja yang akan disampaikan ke Dental Laboratory.
                        </div>
                    </div>

                    <div class="col-md-4 prostho-only">
                        <label class="form-label">{{ $specialCasePaymentLabel }}</label>
                        <input type="text"
                               name="lab_bill_amount"
                               id="lab_bill_amount"
                               class="form-control rupiah-input"
                               value="{{ old('lab_bill_amount', $rupiahOld($ownerFinanceCase->lab_bill_amount ?? 0)) }}"
                               placeholder="contoh: 3.000.000">
                        <div class="form-text">
                            Isi nominal real yang dibayarkan / akan dibayarkan ke vendor Dental Laboratory.
                        </div>
                    </div>

                    <div class="col-md-4 prostho-only">
                        <label class="form-label">{{ $specialCaseIncomeLabel }}</label>
                        <input type="text"
                               id="clinic_income_preview"
                               class="form-control"
                               value="{{ $clinicIncomePreviewValue }}"
                               readonly>
                        <div class="form-text">
                            Nilai final hanya mengikuti <b>clinic_income_amount</b> yang disimpan oleh sistem saat proses simpan/sync, bukan hasil hitung di tampilan ini.
                        </div>
                    </div>

                    <div class="col-md-4 prostho-only">
                        <label class="form-label">Revenue Recognized At</label>
                        <input type="text"
                               id="revenue_recognized_preview"
                               class="form-control"
                               value="{{ $revenueRecognizedPreviewValue }}"
                               readonly>
                        <div class="form-text">
                            Akan otomatis mengikuti tanggal real saat syarat lengkap terpenuhi, bukan tanggal hari ini.
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Catatan Owner (Private)</label>
                        <textarea name="owner_private_notes" class="form-control" rows="4" placeholder="Catatan owner private">{{ old('owner_private_notes', $ownerFinanceCase->owner_private_notes ?? '') }}</textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status Aktif</label>
                        <select name="is_active" class="form-select">
                            <option value="1" @selected((string) old('is_active', (int) ($ownerFinanceCase->is_active ?? true)) === '1')>Aktif</option>
                            <option value="0" @selected((string) old('is_active', (int) ($ownerFinanceCase->is_active ?? true)) === '0')>Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="alert alert-info mt-3 mb-3">
                    <div class="fw-semibold mb-1">Logika Owner Finance</div>
                    <ul class="mb-0">
                        <li><b>Invoice</b> dan <b>nama pasien</b> menjadi identitas utama kasus agar mudah dicari.</li>
                        <li><b>Histori cicilan owner</b> hanya dipakai untuk <b>kasus ortho</b>.</li>
                        <li><b>Ledger bulanan</b> dibentuk dari bulan transaksi riil, sehingga input data mundur tetap aman.</li>
                        <li><b>Mutasi akun owner</b> otomatis disinkronkan ke laporan owner berdasarkan ledger bulanan.</li>
                        <li>Kasus <b>Ortho</b> dinyatakan <b>SELESAI</b> hanya jika <b>Dental Laboratory sudah dibayar</b>, <b>sudah terpasang</b>, dan <b>sisa dana = 0</b>.</li>
                        <li>Kasus <b>Prostodonti / Retainer / Dental Laboratory</b> dapat menyimpan <b>jenis kasus</b>, <b>detail kerja untuk Dental Laboratory</b>, dan <b>pembayaran Dental Laboratory vendor</b>.</li>
                        <li>Untuk semua kasus Owner Finance, <b>tanggal pembayaran Lab</b> dan <b>tanggal pemasangan</b> dapat diisi manual agar data yang terlambat diinput tetap mengikuti tanggal real.</li>
                        <li>Untuk kasus khusus berbasis Dental Laboratory, <b>pendapatan klinik</b> baru diakui jika <b>Dental Laboratory sudah dibayar</b> dan <b>sudah terpasang</b>.</li>
                    </ul>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" name="action_type" value="save_case" class="btn btn-primary">
                        {{ $isEdit ? 'Simpan Perubahan Case' : 'Simpan Owner Finance Case' }}
                    </button>
                    <a href="{{ route('owner_finance.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>

                @if($isEdit && $isOrthoCase)
                    <hr class="my-4">

                    <div class="card border-primary mt-3">
                        <div class="card-header bg-primary text-white fw-bold">
                            Input Histori Cicilan Owner
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Invoice</label>
                                    <input type="text" class="form-control" value="{{ $trx?->invoice_number ?? '-' }}" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Nama Pasien</label>
                                    <input type="text" class="form-control" value="{{ $trx?->patient?->name ?? '-' }}" readonly>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Cicilan Ke-</label>
                                    <input type="number"
                                           name="new_installment_no"
                                           class="form-control"
                                           min="0"
                                           max="120"
                                           value="{{ old('new_installment_no', $nextInstallmentNo) }}">
                                    <div class="form-text">Isi 0 atau kosongkan untuk nomor otomatis berikutnya.</div>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Tanggal Cicilan</label>
                                    <input type="date"
                                           name="new_installment_date"
                                           class="form-control"
                                           value="{{ old('new_installment_date', now()->toDateString()) }}">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Nominal</label>
                                    <input type="text"
                                           name="new_installment_amount"
                                           class="form-control rupiah-input"
                                           value="{{ old('new_installment_amount') }}"
                                           placeholder="contoh: 2.000.000">
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Catatan Cicilan</label>
                                    <textarea name="new_installment_notes" class="form-control" rows="2" placeholder="contoh: Cicilan owner bulan pertama ke Dental Laboratory">{{ old('new_installment_notes') }}</textarea>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" name="action_type" value="add_installment" class="btn btn-success">
                                    + Simpan Histori Cicilan Owner
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if($isEdit && $isOrthoCase)
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-dark text-white fw-bold">
                Histori Cicilan Owner per Kasus
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Pasien</th>
                                <th>Cicilan Ke-</th>
                                <th>Tanggal</th>
                                <th class="text-end">Nominal</th>
                                <th>Catatan</th>
                                <th style="width: 220px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($installments as $item)
                                <tr>
                                    <td>{{ $trx?->invoice_number ?? '-' }}</td>
                                    <td>{{ $trx?->patient?->name ?? '-' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('owner_finance.installments.update', [$ownerFinanceCase->id, $item->id]) }}" class="d-flex align-items-center gap-2 flex-wrap">
                                            @csrf
                                            @method('PUT')
                                            <input type="number"
                                                   name="installment_no"
                                                   value="{{ $item->installment_no }}"
                                                   min="1"
                                                   max="120"
                                                   class="form-control form-control-sm"
                                                   style="width: 80px;">
                                    </td>
                                    <td>
                                            <input type="date"
                                                   name="installment_date"
                                                   value="{{ optional($item->installment_date)->format('Y-m-d') }}"
                                                   class="form-control form-control-sm"
                                                   style="width: 150px;">
                                    </td>
                                    <td class="text-end">
                                            <input type="text"
                                                   name="amount"
                                                   value="{{ number_format((float) $item->amount, 0, ',', '.') }}"
                                                   class="form-control form-control-sm rupiah-input"
                                                   style="width: 140px;">
                                    </td>
                                    <td>
                                            <input type="text"
                                                   name="notes"
                                                   value="{{ $item->notes ?? '' }}"
                                                   class="form-control form-control-sm">
                                    </td>
                                    <td>
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Edit</button>
                                        </form>

                                        <form method="POST" action="{{ route('owner_finance.installments.destroy', [$ownerFinanceCase->id, $item->id]) }}" onsubmit="return confirm('Yakin ingin menghapus histori cicilan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                            </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-muted">
                                        Belum ada histori cicilan owner untuk kasus ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($installments->count() > 0)
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">Total Dibayar Owner</th>
                                    <th class="text-end">Rp {{ number_format((float) ($ownerFinanceCase->ortho_paid_amount ?? 0), 0, ',', '.') }}</th>
                                    <th colspan="2"></th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Sisa Dana Ortho</th>
                                    <th class="text-end {{ $isOrthoDone ? 'text-success fw-bold' : '' }}">
                                        Rp {{ number_format((float) ($ownerFinanceCase->ortho_remaining_balance ?? 0), 0, ',', '.') }}
                                    </th>
                                    <th colspan="2">
                                        @if($isOrthoDone)
                                            <span class="badge bg-success">SELESAI</span>
                                        @endif
                                    </th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white fw-bold">
                Arus Bulanan Owner Finance Ortho
            </div>
            <div class="card-body">
                <div class="small text-muted mb-3">
                    Monitoring ini dibentuk dari <b>bulan transaksi riil</b>, bukan dari bulan saat data diinput.
                    Jadi input data mundur tetap aman dan arus kas owner finance tetap terbaca per bulan.
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 160px;">Periode Bulan</th>
                                <th class="text-end">Pemasukan Owner Finance</th>
                                <th class="text-end">Saldo Awal</th>
                                <th class="text-end">Cicilan Owner</th>
                                <th class="text-end">Pengeluaran Akhir Bulan</th>
                                <th class="text-end">Saldo Akhir / Carry Forward</th>
                                <th style="width: 160px;">Status</th>
                                <th>Catatan Sistem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monthlyLedgers as $ledger)
                                <tr>
                                    <td>{{ $formatPeriode(optional($ledger->ledger_month)->format('Y-m-d')) }}</td>
                                    <td class="text-end">Rp {{ number_format((float) $ledger->income_amount, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format((float) $ledger->opening_balance, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format((float) $ledger->installment_paid, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format((float) $ledger->expense_end_month, 0, ',', '.') }}</td>
                                    <td class="text-end {{ (float) $ledger->closing_balance <= 0 ? 'text-success fw-bold' : '' }}">
                                        Rp {{ number_format((float) $ledger->closing_balance, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        @if($ledger->is_closed)
                                            <span class="badge bg-success">SELESAI</span>
                                        @elseif((float) $ledger->installment_paid > 0)
                                            <span class="badge bg-primary">BERJALAN</span>
                                        @else
                                            <span class="badge bg-warning text-dark">DIBAWA KE BULAN BERIKUT</span>
                                        @endif
                                    </td>
                                    <td>{{ $ledger->notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-muted">
                                        Arus bulanan belum terbentuk. Isi Dana Alokasi Ortho terlebih dahulu agar sistem bisa membangun arus bulanan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($monthlyLedgers->count() > 0)
                            <tfoot>
                                <tr class="table-light">
                                    <th class="text-end">Total</th>
                                    <th class="text-end">
                                        Rp {{ number_format($monthlyLedgerIncomeTotal, 0, ',', '.') }}
                                    </th>
                                    <th class="text-end">
                                        Rp {{ number_format($monthlyLedgerOpeningTotal, 0, ',', '.') }}
                                    </th>
                                    <th class="text-end">
                                        Rp {{ number_format($monthlyLedgerInstallmentTotal, 0, ',', '.') }}
                                    </th>
                                    <th class="text-end">
                                        Rp {{ number_format($monthlyLedgerExpenseTotal, 0, ',', '.') }}
                                    </th>
                                    <th class="text-end text-success fw-bold">
                                        Rp {{ number_format($monthlyLedgerClosingTotal, 0, ',', '.') }}
                                    </th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
(function () {
    function toDigits(str) {
        return (str || '').toString().replace(/[^\d]/g, '');
    }

    function formatId(strOrNumber) {
        const digits = toDigits(strOrNumber);
        if (!digits) return '';
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function attachRupiahFormatting(el) {
        if (!el) return;
        el.value = formatId(el.value);

        el.addEventListener('input', function () {
            el.value = formatId(el.value);
            try { el.setSelectionRange(el.value.length, el.value.length); } catch (e) {}
        });

        el.addEventListener('blur', function () {
            el.value = formatId(el.value);
        });
    }

    document.querySelectorAll('.rupiah-input').forEach(attachRupiahFormatting);

    const caseTypeEl = document.getElementById('case_type');
    const labPaidEl = document.getElementById('lab_paid');
    const installedEl = document.getElementById('installed');
    const orthoBlocks = document.querySelectorAll('.ortho-only');
    const prosthoBlocks = document.querySelectorAll('.prostho-only');
    const allCaseDateBlocks = document.querySelectorAll('.all-case-date-block');
    const revenueRecognizedPreviewEl = document.getElementById('revenue_recognized_preview');
    const labPaidDateBlock = document.getElementById('lab_paid_date_block');
    const installedDateBlock = document.getElementById('installed_date_block');
    const labPaidAtEl = document.getElementById('lab_paid_at');
    const installedAtEl = document.getElementById('installed_at');

    function updateRevenuePreviewText() {
        if (!revenueRecognizedPreviewEl) return;

        const labPaid = labPaidEl ? labPaidEl.value === '1' : false;
        const installed = installedEl ? installedEl.value === '1' : false;
        const labPaidAt = labPaidAtEl ? labPaidAtEl.value : '';
        const installedAt = installedAtEl ? installedAtEl.value : '';

        if (labPaid && installed && (labPaidAt || installedAt)) {
            revenueRecognizedPreviewEl.value = 'Akan mengikuti tanggal real terakhir saat disimpan';
            return;
        }

        if (labPaid && installed) {
            revenueRecognizedPreviewEl.value = 'Akan diisi sistem saat disimpan';
            return;
        }

        if (labPaid && labPaidAt) {
            revenueRecognizedPreviewEl.value = 'Menunggu tanggal pemasangan';
            return;
        }

        if (installed && installedAt) {
            revenueRecognizedPreviewEl.value = 'Menunggu tanggal pembayaran lab';
            return;
        }

        revenueRecognizedPreviewEl.value = '-';
    }

    function toggleDateFields() {
        const labPaid = labPaidEl ? labPaidEl.value === '1' : false;
        const installed = installedEl ? installedEl.value === '1' : false;

        if (labPaidDateBlock) {
            labPaidDateBlock.style.display = labPaid ? '' : 'none';
            if (!labPaid && labPaidAtEl) {
                labPaidAtEl.value = '';
            }
        }

        if (installedDateBlock) {
            installedDateBlock.style.display = installed ? '' : 'none';
            if (!installed && installedAtEl) {
                installedAtEl.value = '';
            }
        }
    }

    function syncCaseTypeUI() {
        const caseType = caseTypeEl ? caseTypeEl.value : '';
        const isOrtho = caseType === 'ortho';
        const isProstho = caseType === 'prostodonti' || caseType === 'retainer' || caseType === 'lab';

        orthoBlocks.forEach(function (el) {
            el.style.display = isOrtho ? '' : 'none';
        });

        prosthoBlocks.forEach(function (el) {
            el.style.display = isProstho ? '' : 'none';
        });

        allCaseDateBlocks.forEach(function (el) {
            el.style.display = '';
        });

        toggleDateFields();
        updateRevenuePreviewText();
    }

    if (caseTypeEl) {
        caseTypeEl.addEventListener('change', syncCaseTypeUI);
    }

    if (labPaidEl) {
        labPaidEl.addEventListener('change', function () {
            toggleDateFields();
            updateRevenuePreviewText();
        });
    }

    if (installedEl) {
        installedEl.addEventListener('change', function () {
            toggleDateFields();
            updateRevenuePreviewText();
        });
    }

    if (labPaidAtEl) {
        labPaidAtEl.addEventListener('change', updateRevenuePreviewText);
    }

    if (installedAtEl) {
        installedAtEl.addEventListener('change', updateRevenuePreviewText);
    }

    syncCaseTypeUI();
    toggleDateFields();
    updateRevenuePreviewText();
})();
</script>
@endsection