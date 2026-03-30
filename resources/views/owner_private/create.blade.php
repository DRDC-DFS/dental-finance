@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <h4 class="mb-1">Tambah Transaksi Private Owner</h4>
        <div class="text-muted small">Input pemasukan atau pengeluaran khusus owner</div>
        <div class="text-danger small fw-bold mt-1">
            Data private owner terpisah dari operasional klinik
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <div class="fw-bold mb-1">Terjadi kesalahan:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('owner_private.store') }}" id="privateOwnerCreateForm">
                @csrf

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tanggal</label>
                        <input
                            type="date"
                            name="trx_date"
                            value="{{ old('trx_date', now()->toDateString()) }}"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tipe Transaksi</label>
                        <select name="type" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <option value="income" {{ old('type') === 'income' ? 'selected' : '' }}>Masuk</option>
                            <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>Keluar</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <select name="category" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach(($categoryOptions ?? []) as $value => $label)
                                <option value="{{ $value }}" {{ old('category') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Metode Pembayaran</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="TUNAI" {{ old('payment_method', 'TUNAI') === 'TUNAI' ? 'selected' : '' }}>TUNAI</option>
                            <option value="BCA" {{ old('payment_method') === 'BCA' ? 'selected' : '' }}>BCA</option>
                            <option value="BNI" {{ old('payment_method') === 'BNI' ? 'selected' : '' }}>BNI</option>
                            <option value="BRI" {{ old('payment_method') === 'BRI' ? 'selected' : '' }}>BRI</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Sumber</label>
                        <input
                            type="text"
                            name="source"
                            value="{{ old('source') }}"
                            class="form-control"
                            maxlength="255"
                            placeholder="Contoh: Vendor A, Bank BCA, Refund marketplace, Transfer owner"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Nominal</label>
                        <input
                            type="text"
                            id="amount_display_create"
                            value="{{ old('amount') ? number_format((float) preg_replace('/[^\d]/', '', old('amount')), 0, ',', '.') : '' }}"
                            class="form-control"
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="Contoh: 3.000.000"
                            required
                        >
                        <input
                            type="hidden"
                            id="amount_create"
                            name="amount"
                            value="{{ old('amount') ? preg_replace('/[^\d]/', '', old('amount')) : '' }}"
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Keterangan</label>
                        <input
                            type="text"
                            name="description"
                            value="{{ old('description') }}"
                            class="form-control"
                            maxlength="255"
                            placeholder="Contoh: Cashback kartu kredit bulan ini"
                            required
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Catatan</label>
                        <textarea
                            name="notes"
                            rows="3"
                            class="form-control"
                            placeholder="Catatan tambahan jika diperlukan"
                        >{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        Simpan
                    </button>

                    <a href="{{ route('owner_private.index') }}" class="btn btn-secondary btn-sm">
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const displayInput = document.getElementById('amount_display_create');
    const hiddenInput = document.getElementById('amount_create');
    const form = document.getElementById('privateOwnerCreateForm');

    if (!displayInput || !hiddenInput || !form) {
        return;
    }

    function formatRibuan(value) {
        const numbers = String(value || '').replace(/\D/g, '');
        if (numbers === '') {
            return '';
        }
        return new Intl.NumberFormat('id-ID').format(Number(numbers));
    }

    function syncAmount() {
        const raw = displayInput.value.replace(/\D/g, '');
        hiddenInput.value = raw;
        displayInput.value = formatRibuan(raw);
    }

    displayInput.addEventListener('input', syncAmount);
    displayInput.addEventListener('blur', syncAmount);

    form.addEventListener('submit', function () {
        hiddenInput.value = displayInput.value.replace(/\D/g, '');
    });
});
</script>
@endsection