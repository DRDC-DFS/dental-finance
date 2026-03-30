@extends('layouts.app')

@section('title', 'Tambah Pemasukan Lain-lain')

@section('content')
<div class="container-fluid py-2">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0">Tambah Pemasukan Lain-lain</h4>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('other_income.index') }}" class="btn btn-outline-secondary">
                Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <div class="alert alert-info py-2">
                <strong>Catatan:</strong>
                Form ini dipakai untuk mencatat pemasukan <strong>non-pasien</strong>, sehingga tidak masuk ke alur invoice, QR, tindakan, maupun pembayaran pasien.
            </div>

            <form method="POST" action="{{ route('other_income.store') }}">
                @csrf

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date"
                               name="trx_date"
                               class="form-control"
                               value="{{ old('trx_date', now()->toDateString()) }}"
                               required>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Nama / Judul Pemasukan</label>
                        <input type="text"
                               name="title"
                               class="form-control"
                               value="{{ old('title') }}"
                               placeholder="contoh: Penjualan produk, jasa lain-lain"
                               maxlength="150"
                               required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Jenis / Sumber Pemasukan</label>
                        <input type="text"
                               name="source_type"
                               class="form-control"
                               value="{{ old('source_type') }}"
                               placeholder="contoh: Penjualan Produk / Sewa / Jasa Non-Pasien"
                               maxlength="100"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Nominal (Rp)</label>
                        <input type="text"
                               name="amount"
                               id="amount"
                               class="form-control"
                               value="{{ old('amount') }}"
                               placeholder="contoh: 250.000"
                               required>
                        <div class="form-text">Masukkan nominal pemasukan non-pasien.</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Metode Pembayaran</label>
                        <select name="payment_method" id="payment_method" class="form-select" required>
                            <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Tunai</option>
                            <option value="bank" @selected(old('payment_method') === 'bank')>Bank</option>
                        </select>
                    </div>

                    <div class="col-md-3" id="bank_name_wrapper" style="{{ old('payment_method') === 'bank' ? '' : 'display:none;' }}">
                        <label class="form-label">Bank</label>
                        <select name="bank_name" id="bank_name" class="form-select">
                            <option value="">- pilih bank -</option>
                            <option value="BCA" @selected(old('bank_name') === 'BCA')>BCA</option>
                            <option value="BNI" @selected(old('bank_name') === 'BNI')>BNI</option>
                            <option value="BRI" @selected(old('bank_name') === 'BRI')>BRI</option>
                        </select>
                    </div>

                    <div class="col-md-3" id="payment_channel_wrapper" style="{{ old('payment_method') === 'bank' ? '' : 'display:none;' }}">
                        <label class="form-label">Channel Bank</label>
                        <select name="payment_channel" id="payment_channel" class="form-select">
                            <option value="">- pilih channel -</option>
                            <option value="transfer" @selected(old('payment_channel') === 'transfer')>TRANSFER</option>
                            <option value="qris" @selected(old('payment_channel') === 'qris')>QRIS</option>
                            <option value="edc" @selected(old('payment_channel') === 'edc')>EDC</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Visibility</label>
                        <select name="visibility" class="form-select" required>
                            <option value="public" @selected(old('visibility', 'public') === 'public')>Public</option>
                            <option value="private" @selected(old('visibility') === 'private')>Private (Owner saja)</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Masuk Laporan Harian?</label>
                        <select name="include_in_report" class="form-select" required>
                            <option value="1" @selected((string) old('include_in_report', '1') === '1')>Ya</option>
                            <option value="0" @selected((string) old('include_in_report') === '0')>Tidak</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Masuk Net Setoran?</label>
                        <select name="include_in_cashflow" class="form-select" required>
                            <option value="1" @selected((string) old('include_in_cashflow', '1') === '1')>Ya</option>
                            <option value="0" @selected((string) old('include_in_cashflow') === '0')>Tidak</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-warning py-2 mb-0">
                            <strong>Catatan kas harian:</strong>
                            <br>- <strong>Masuk Laporan Harian = Ya</strong> → data akan tampil sebagai pemasukan lain-lain di laporan harian.
                            <br>- <strong>Masuk Net Setoran = Ya</strong> → nominal ini ikut dihitung ke total uang admin / net setoran harian.
                            <br>- Jika metode <strong>Tunai</strong> → masuk ke kas tunai admin.
                            <br>- Jika metode <strong>Bank</strong> → masuk ke kelompok bank sesuai <strong>Bank + Channel</strong>.
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="notes"
                                  class="form-control"
                                  rows="4"
                                  placeholder="catatan tambahan untuk pemasukan ini">{{ old('notes') }}</textarea>
                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan Pemasukan Lain-lain
                    </button>

                    <a href="{{ route('other_income.index') }}" class="btn btn-outline-secondary">
                        Batal
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<script>
    (function () {
        const amountEl = document.getElementById('amount');
        const paymentMethodEl = document.getElementById('payment_method');
        const bankNameWrapperEl = document.getElementById('bank_name_wrapper');
        const bankNameEl = document.getElementById('bank_name');
        const paymentChannelWrapperEl = document.getElementById('payment_channel_wrapper');
        const paymentChannelEl = document.getElementById('payment_channel');

        function toDigits(str) {
            return (str || '').toString().replace(/[^\d]/g, '');
        }

        function formatIdr(value) {
            const digits = toDigits(value);
            if (!digits) return '';
            return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function syncPaymentFields() {
            if (!paymentMethodEl) return;

            const method = (paymentMethodEl.value || 'cash').toLowerCase();

            if (method === 'bank') {
                if (bankNameWrapperEl) bankNameWrapperEl.style.display = '';
                if (paymentChannelWrapperEl) paymentChannelWrapperEl.style.display = '';
            } else {
                if (bankNameWrapperEl) bankNameWrapperEl.style.display = 'none';
                if (paymentChannelWrapperEl) paymentChannelWrapperEl.style.display = 'none';
                if (bankNameEl) bankNameEl.value = '';
                if (paymentChannelEl) paymentChannelEl.value = '';
            }
        }

        if (amountEl) {
            amountEl.addEventListener('input', function () {
                amountEl.value = formatIdr(amountEl.value);
                try {
                    amountEl.setSelectionRange(amountEl.value.length, amountEl.value.length);
                } catch (e) {}
            });

            amountEl.value = formatIdr(amountEl.value);
        }

        if (paymentMethodEl) {
            paymentMethodEl.addEventListener('change', syncPaymentFields);
        }

        syncPaymentFields();
    })();
</script>
@endsection