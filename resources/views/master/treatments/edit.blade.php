@extends('layouts.app')

@section('title', 'Edit Treatment')

@section('content')
<div class="container-fluid py-2">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Edit Treatment</h4>

        <a href="{{ route('master.treatments.index') }}" class="btn btn-outline-secondary">
            Kembali
        </a>
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

    <div class="card">
        <div class="card-body">

            <form method="POST" action="{{ route('master.treatments.update', $treatment->id) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" class="form-select" required>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    @selected(old('category_id', $treatment->category_id) == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select" required>
                            <option value="1" @selected((int) old('is_active', $treatment->is_active) === 1)>Aktif</option>
                            <option value="0" @selected((int) old('is_active', $treatment->is_active) === 0)>Nonaktif</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nama Treatment</label>
                        <input type="text"
                               name="name"
                               class="form-control"
                               value="{{ old('name', $treatment->name) }}"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Unit</label>
                        <input type="text"
                               name="unit"
                               class="form-control"
                               value="{{ old('unit', $treatment->unit ?? '1x') }}"
                               placeholder="1x">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Mode Harga</label>
                        <select name="price_mode" id="price_mode" class="form-select" required>
                            <option value="fixed" @selected(old('price_mode', $price_mode ?? 'fixed') === 'fixed')>Harga Tetap</option>
                            <option value="manual" @selected(old('price_mode', $price_mode ?? 'fixed') === 'manual')>Harga Manual</option>
                        </select>
                        <div class="form-text">
                            Harga Tetap = pakai harga master. Harga Manual = harga final diisi saat transaksi.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Harga</label>
                        <input type="text"
                               name="price"
                               id="price"
                               class="form-control text-end uang-format"
                               value="{{ old('price', $price_display) }}"
                               placeholder="1.500.000">
                        <div class="form-text" id="price_help_text">Gunakan titik. Contoh: 1.500.000</div>
                    </div>

                    <div class="col-md-9">
                        <label class="form-label">Petunjuk Input (opsional)</label>
                        <textarea name="notes_hint"
                                  id="notes_hint"
                                  class="form-control"
                                  rows="2"
                                  maxlength="1000"
                                  placeholder="Contoh: Isi harga final manual sesuai jumlah unit, bahan, dan detail kasus pasien">{{ old('notes_hint', $notes_hint ?? '') }}</textarea>
                        <div class="form-text">
                            Khusus berguna untuk treatment dengan harga manual seperti Bridge, Implan, Gigi Tiruan Lepasan, atau Retainer.
                        </div>
                    </div>

                    <div class="col-md-12">
                        <input type="hidden" name="is_free" value="0">

                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="is_free"
                                   id="is_free"
                                   value="1"
                                   {{ old('is_free', $treatment->is_free ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_free">
                                Treatment gratis / tindakan lanjutan tanpa bayar
                            </label>
                        </div>

                        <div class="form-text" id="is_free_help">
                            Aktifkan untuk tindakan seperti lepas benang, cetak kembali, kontrol lanjutan, atau tindakan lain yang memang harus tetap tercatat tetapi tanpa tagihan.
                        </div>
                    </div>

                    <div class="col-md-12">
                        <input type="hidden" name="allow_zero_price" value="0">

                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="allow_zero_price"
                                   id="allow_zero_price"
                                   value="1"
                                   {{ old('allow_zero_price', $treatment->allow_zero_price ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="allow_zero_price">
                                Boleh harga 0 / treatment gratis
                            </label>
                        </div>

                        <div class="form-text" id="allow_zero_price_help">
                            Aktifkan untuk treatment seperti cetak kembali, cabut benang, kontrol tertentu, atau tindakan lain yang memang boleh gratis tanpa harus mengubah pasien UMUM menjadi KHUSUS.
                        </div>
                    </div>

                </div>

                <div class="alert alert-info mt-3 mb-3" id="manual_price_alert" style="display: none;">
                    <div class="fw-semibold mb-1">Mode Harga Manual Aktif</div>
                    <div class="small">
                        Treatment ini boleh disimpan dengan harga <b>0</b> atau harga referensi.
                        <b>Harga final</b> akan diinput manual saat transaksi pemasukan sesuai kasus pasien.
                    </div>
                </div>

                <div class="alert alert-warning mt-3 mb-3" id="zero_price_alert" style="display: none;">
                    <div class="fw-semibold mb-1">Izin Harga 0 Aktif</div>
                    <div class="small">
                        Treatment ini diizinkan bernilai <b>Rp 0</b> untuk pasien <b>UMUM</b>.
                        Nanti transaksi tetap bisa diproses tanpa perlu mengubah kategori pasien menjadi KHUSUS.
                    </div>
                </div>

                <div class="alert alert-success mt-3 mb-3" id="free_treatment_alert" style="display: none;">
                    <div class="fw-semibold mb-1">Treatment Gratis Aktif</div>
                    <div class="small">
                        Treatment ini akan disimpan sebagai <b>tindakan gratis</b>.
                        Harga master akan dianggap <b>Rp 0</b> dan saat dipakai di transaksi pasien umum, tindakan tetap bisa dicatat dan transaksi tetap dapat diselesaikan tanpa pembayaran.
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="mb-3">Fee Dokter per Tindakan</h5>
                <div class="text-muted small mb-3">
                    Setiap dokter aktif memiliki pengaturan fee sendiri agar laporan fee dokter tetap jelas per dokter individual.
                </div>

                @php
                    $feeTypeOptions = [
                        'percent' => 'Persen (%)',
                        'fixed' => 'Nominal (Rp)',
                        'manual' => 'Manual',
                    ];

                    $typeLabels = [
                        'owner' => 'Owner',
                        'mitra' => 'Mitra',
                        'tamu' => 'Tamu',
                    ];

                    $feeFormByDoctor = $feeFormByDoctor ?? [];
                @endphp

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 14%;">Tipe Dokter</th>
                                <th style="width: 26%;">Nama Dokter</th>
                                <th style="width: 20%;">Tipe Fee</th>
                                <th style="width: 22%;">Nilai Fee</th>
                                <th style="width: 18%;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($doctors as $doctor)
                                @php
                                    $doctorId = (int) $doctor->id;
                                    $doctorType = strtolower((string) ($doctor->type ?? ''));
                                    $typeLabel = $typeLabels[$doctorType] ?? ucfirst($doctorType);
                                    $defaultFeeType = old("doctor_fees.$doctorId.fee_type", $feeFormByDoctor[$doctorId]['fee_type'] ?? 'manual');
                                    $defaultFeeValue = old("doctor_fees.$doctorId.fee_value", $feeFormByDoctor[$doctorId]['fee_value'] ?? '0');
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $typeLabel }}</span>
                                    </td>
                                    <td class="fw-semibold">
                                        {{ $doctor->name }}
                                    </td>
                                    <td>
                                        <select name="doctor_fees[{{ $doctorId }}][fee_type]"
                                                class="form-select fee-type-select"
                                                data-target="doctor_fee_value_{{ $doctorId }}"
                                                required>
                                            @foreach($feeTypeOptions as $value => $label)
                                                <option value="{{ $value }}" {{ $defaultFeeType === $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="doctor_fees[{{ $doctorId }}][fee_value]"
                                               id="doctor_fee_value_{{ $doctorId }}"
                                               value="{{ $defaultFeeValue }}"
                                               class="form-control text-end fee-value-input uang-format"
                                               placeholder="0">
                                    </td>
                                    <td>
                                        <div class="small text-muted">
                                            Persen: isi angka tanpa tanda %. Nominal: isi rupiah. Manual: otomatis 0.
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        Belum ada dokter aktif bertipe Owner, Mitra, atau Tamu.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Update
                    </button>

                    <a href="{{ route('master.treatments.index') }}" class="btn btn-outline-secondary">
                        Batal
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<script>
(function () {
    function formatRupiahInput(el) {
        if (!el) return;

        let value = (el.value || '').replace(/\D/g, '');

        if (value === '') {
            el.value = '';
            return;
        }

        el.value = new Intl.NumberFormat('id-ID').format(value);
    }

    document.querySelectorAll('.uang-format').forEach(function (el) {
        if (!el.hasAttribute('readonly')) {
            formatRupiahInput(el);
        }

        el.addEventListener('input', function () {
            if (this.hasAttribute('readonly')) return;
            formatRupiahInput(this);
        });
    });

    function toggleFeeInput(selectEl) {
        const targetId = selectEl.getAttribute('data-target');
        const inputEl = document.getElementById(targetId);
        if (!inputEl) return;

        if (selectEl.value === 'manual') {
            inputEl.value = '0';
            inputEl.setAttribute('readonly', 'readonly');
            inputEl.classList.add('bg-light');
        } else {
            inputEl.removeAttribute('readonly');
            inputEl.classList.remove('bg-light');
            formatRupiahInput(inputEl);
        }
    }

    document.querySelectorAll('.fee-type-select').forEach(function (selectEl) {
        toggleFeeInput(selectEl);

        selectEl.addEventListener('change', function () {
            toggleFeeInput(this);
        });
    });

    const priceModeEl = document.getElementById('price_mode');
    const priceEl = document.getElementById('price');
    const priceHelpTextEl = document.getElementById('price_help_text');
    const notesHintEl = document.getElementById('notes_hint');
    const manualAlertEl = document.getElementById('manual_price_alert');
    const allowZeroPriceEl = document.getElementById('allow_zero_price');
    const allowZeroPriceHelpEl = document.getElementById('allow_zero_price_help');
    const zeroPriceAlertEl = document.getElementById('zero_price_alert');
    const isFreeEl = document.getElementById('is_free');
    const isFreeHelpEl = document.getElementById('is_free_help');
    const freeTreatmentAlertEl = document.getElementById('free_treatment_alert');

    function toggleZeroPriceUI() {
        const allowZero = !!(allowZeroPriceEl && allowZeroPriceEl.checked);

        if (zeroPriceAlertEl) {
            zeroPriceAlertEl.style.display = allowZero ? '' : 'none';
        }

        if (allowZeroPriceHelpEl) {
            allowZeroPriceHelpEl.textContent = allowZero
                ? 'Aktif. Treatment ini boleh bernilai Rp 0 untuk pasien UMUM dan tetap bisa diproses sampai selesai.'
                : 'Aktifkan untuk treatment seperti cetak kembali, cabut benang, kontrol tertentu, atau tindakan lain yang memang boleh gratis tanpa harus mengubah pasien UMUM menjadi KHUSUS.';
        }
    }

    function toggleFreeTreatmentUI() {
        const isFree = !!(isFreeEl && isFreeEl.checked);

        if (freeTreatmentAlertEl) {
            freeTreatmentAlertEl.style.display = isFree ? '' : 'none';
        }

        if (isFreeHelpEl) {
            isFreeHelpEl.textContent = isFree
                ? 'Aktif. Treatment ini akan otomatis memakai harga 0 dan diperlakukan sebagai tindakan gratis / lanjutan tanpa bayar.'
                : 'Aktifkan untuk tindakan seperti lepas benang, cetak kembali, kontrol lanjutan, atau tindakan lain yang memang harus tetap tercatat tetapi tanpa tagihan.';
        }

        if (!priceEl || !allowZeroPriceEl) {
            return;
        }

        if (isFree) {
            priceEl.value = '0';
            priceEl.setAttribute('readonly', 'readonly');
            priceEl.classList.add('bg-light');

            allowZeroPriceEl.checked = true;
            allowZeroPriceEl.setAttribute('disabled', 'disabled');

            if (priceHelpTextEl) {
                priceHelpTextEl.textContent = 'Treatment gratis aktif. Harga otomatis 0.';
            }
        } else {
            priceEl.removeAttribute('readonly');
            priceEl.classList.remove('bg-light');

            allowZeroPriceEl.removeAttribute('disabled');

            togglePriceModeUI();
        }

        toggleZeroPriceUI();
    }

    function togglePriceModeUI() {
        if (!priceModeEl || !priceEl) return;

        const isManual = priceModeEl.value === 'manual';
        const isFree = !!(isFreeEl && isFreeEl.checked);

        if (isFree) {
            priceEl.value = '0';
            priceEl.removeAttribute('required');
            priceEl.setAttribute('readonly', 'readonly');
            priceEl.classList.add('bg-light');

            if (priceHelpTextEl) {
                priceHelpTextEl.textContent = 'Treatment gratis aktif. Harga otomatis 0.';
            }

            if (manualAlertEl) {
                manualAlertEl.style.display = isManual ? '' : 'none';
            }

            return;
        }

        if (isManual) {
            priceEl.removeAttribute('required');
            priceEl.removeAttribute('readonly');
            priceEl.classList.remove('bg-light');
            priceEl.placeholder = '0 atau harga referensi';
            if (priceHelpTextEl) {
                priceHelpTextEl.textContent = 'Untuk mode manual, boleh isi 0 atau harga referensi. Harga final akan diinput saat transaksi.';
            }
            if (manualAlertEl) {
                manualAlertEl.style.display = '';
            }
            if (notesHintEl && !notesHintEl.value.trim()) {
                notesHintEl.placeholder = 'Contoh: Isi harga final manual sesuai jumlah unit, bahan, dan detail kasus pasien';
            }
        } else {
            priceEl.setAttribute('required', 'required');
            priceEl.removeAttribute('readonly');
            priceEl.classList.remove('bg-light');
            priceEl.placeholder = '1.500.000';
            if (priceHelpTextEl) {
                priceHelpTextEl.textContent = 'Gunakan titik. Contoh: 1.500.000';
            }
            if (manualAlertEl) {
                manualAlertEl.style.display = 'none';
            }
        }
    }

    if (priceModeEl) {
        priceModeEl.addEventListener('change', function () {
            togglePriceModeUI();
            toggleFreeTreatmentUI();
        });
    }

    if (allowZeroPriceEl) {
        allowZeroPriceEl.addEventListener('change', toggleZeroPriceUI);
    }

    if (isFreeEl) {
        isFreeEl.addEventListener('change', function () {
            toggleFreeTreatmentUI();
            togglePriceModeUI();
        });
    }

    togglePriceModeUI();
    toggleZeroPriceUI();
    toggleFreeTreatmentUI();
})();
</script>
@endsection