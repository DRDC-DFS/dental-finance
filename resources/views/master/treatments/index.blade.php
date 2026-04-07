@extends('layouts.app')

@section('title', 'Master Treatments')

@section('content')
<div class="container-fluid py-2">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Master Treatments</h4>

        <div class="d-flex gap-2">
            <a href="{{ route('master.treatments.create') }}" class="btn btn-primary">
                + Tambah Treatment
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
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

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Manajemen Kategori Treatment</h5>
                <span class="text-muted small">Tambah, edit, dan hapus kategori langsung dari halaman ini</span>
            </div>

            <form action="{{ route('master.treatment_categories.store') }}" method="POST" class="row g-2 align-items-end mb-3">
                @csrf
                <input type="hidden" name="from_treatments" value="1">

                <div class="col-md-5">
                    <label class="form-label">Nama Kategori</label>
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="{{ old('name') }}"
                        placeholder="Contoh: KONSERVASI"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label d-block">Status</label>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                        <label class="form-check-label" for="is_active">
                            Aktif
                        </label>
                    </div>
                </div>

                <div class="col-md-4 text-md-end">
                    <button type="submit" class="btn btn-success">
                        + Tambah Kategori
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="70">No</th>
                            <th>Nama Kategori</th>
                            <th width="120">Status</th>
                            <th width="180" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $i => $category)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="fw-semibold">{{ $category->name }}</td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('master.treatment_categories.edit', $category->id) }}" class="btn btn-sm btn-outline-secondary">
                                        Edit
                                    </a>

                                    <form action="{{ route('master.treatment_categories.destroy', $category->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Hapus kategori ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="from_treatments" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    Belum ada kategori treatment
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            @php
                $typeLabels = [
                    'owner' => 'Owner',
                    'mitra' => 'Mitra',
                    'tamu' => 'Tamu',
                ];

                $renderFee = function ($fee) {
                    $type = strtolower((string) ($fee['fee_type'] ?? '-'));
                    $value = (float) ($fee['fee_value'] ?? 0);

                    if ($type === 'manual') {
                        return 'Manual';
                    }

                    if ($type === 'percent') {
                        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',') . '%';
                    }

                    if ($type === 'fixed') {
                        return 'Rp ' . number_format($value, 0, ',', '.');
                    }

                    return '-';
                };
            @endphp

            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-6 col-lg-5">
                    <label for="treatmentSearchInput" class="form-label fw-semibold mb-1">Cari Treatment</label>
                    <input
                        type="text"
                        id="treatmentSearchInput"
                        class="form-control"
                        placeholder="Cari nama treatment, kategori, unit, nama dokter, manual, zero, gratis, ortho, prosto, atau belum diatur...">
                    <div class="form-text">
                        Gunakan untuk cepat menemukan treatment yang ingin diedit atau dicek fee dokternya.
                    </div>
                </div>

                <div class="col-md-3 col-lg-3">
                    <label class="form-label fw-semibold mb-1">Info</label>
                    <div id="treatmentSearchInfo" class="form-control bg-light">
                        Menampilkan semua data
                    </div>
                </div>

                <div class="col-md-3 col-lg-2">
                    <button type="button" id="treatmentSearchReset" class="btn btn-outline-secondary w-100">
                        Reset Pencarian
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th width="70">No</th>
                            <th>Nama Treatment</th>
                            <th width="180">Kategori</th>
                            <th width="120">Unit</th>
                            <th width="180" class="text-end">Harga</th>
                            <th>Fee Dokter</th>
                            <th width="120">Status</th>
                            <th width="160" class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="treatmentTableBody">
                        @forelse($treatments as $i => $treatment)
                        @php
                            $feeRows = $feesByTreatmentDoctor[$treatment->id] ?? [];
                            $allowZero = (bool) ($treatment->allow_zero_price ?? false);
                            $isFree = (bool) ($treatment->is_free ?? false);
                            $isOrthoRelated = (bool) ($treatment->is_ortho_related ?? false);
                            $isProstoRelated = (bool) ($treatment->is_prosto_related ?? false);

                            $searchParts = [
                                strtolower((string) $treatment->name),
                                strtolower((string) (optional($treatment->category)->name ?? '')),
                                strtolower((string) ($treatment->unit ?? '')),
                                strtolower((string) ($treatment->price_mode ?? 'fixed')),
                                $allowZero ? 'allow zero zero gratis harga 0 boleh 0' : 'tanpa zero',
                                $isFree ? 'gratis free lanjutan tanpa bayar' : 'berbayar',
                                $isOrthoRelated ? 'ortho terkait ortho' : 'bukan ortho',
                                $isProstoRelated ? 'prosto terkait prosto' : 'bukan prosto',
                                $treatment->is_active ? 'aktif' : 'nonaktif',
                            ];

                            if (!empty($feeRows)) {
                                foreach ($feeRows as $fee) {
                                    $doctorType = strtolower((string) ($fee['doctor_type'] ?? ''));
                                    $typeLabel = strtolower((string) ($typeLabels[$doctorType] ?? ucfirst($doctorType)));

                                    $searchParts[] = strtolower((string) ($fee['doctor_name'] ?? ''));
                                    $searchParts[] = $typeLabel;
                                    $searchParts[] = strtolower((string) $renderFee($fee));
                                }
                            } else {
                                $searchParts[] = 'belum diatur';
                            }

                            $searchText = implode(' ', $searchParts);
                        @endphp
                        <tr class="treatment-row" data-search="{{ $searchText }}">
                            <td>{{ $i + 1 }}</td>

                            <td class="fw-semibold">
                                {{ $treatment->name }}
                                <div class="mt-1 d-flex flex-wrap gap-1">
                                    <span class="badge bg-light text-dark border">
                                        {{ strtolower((string) ($treatment->price_mode ?? 'fixed')) === 'manual' ? 'MANUAL' : 'FIXED' }}
                                    </span>

                                    @if($allowZero)
                                        <span class="badge bg-warning text-dark border">
                                            BOLEH HARGA 0
                                        </span>
                                    @endif

                                    @if($isFree)
                                        <span class="badge bg-success">
                                            GRATIS / LANJUTAN
                                        </span>
                                    @endif

                                    @if($isOrthoRelated)
                                        <span class="badge bg-info text-dark border">
                                            TERKAIT ORTHO
                                        </span>
                                    @endif

                                    @if($isProstoRelated)
                                        <span class="badge bg-primary">
                                            TERKAIT PROSTO
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                {{ optional($treatment->category)->name ?? '-' }}
                            </td>

                            <td>
                                {{ $treatment->unit ?? '-' }}
                            </td>

                            <td class="text-end">
                                {{ number_format((float) $treatment->price, 0, ',', '.') }}
                                @if($allowZero)
                                    <div class="small text-muted mt-1">
                                        Harga 0 diizinkan
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if(!empty($feeRows))
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($feeRows as $fee)
                                            @php
                                                $doctorType = strtolower((string) ($fee['doctor_type'] ?? ''));
                                                $typeLabel = $typeLabels[$doctorType] ?? ucfirst($doctorType);
                                            @endphp
                                            <div class="border rounded px-2 py-1 small">
                                                <div class="fw-semibold">{{ $fee['doctor_name'] ?? '-' }}</div>
                                                <div class="text-muted">
                                                    {{ $typeLabel }} • {{ $renderFee($fee) }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">Belum diatur</span>
                                @endif
                            </td>

                            <td>
                                @if($treatment->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <a href="{{ route('master.treatments.edit', $treatment->id) }}" class="btn btn-sm btn-outline-secondary">
                                    Edit
                                </a>

                                <form action="{{ route('master.treatments.destroy', $treatment->id) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Hapus treatment ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Hapus
                                    </button>
                                </form>
                            </td>

                        </tr>
                        @empty
                        <tr id="emptyDataRow">
                            <td colspan="8" class="text-center text-muted py-4">
                                Belum ada data
                            </td>
                        </tr>
                        @endforelse

                        <tr id="noSearchResultRow" style="display:none;">
                            <td colspan="8" class="text-center text-muted py-4">
                                Tidak ada treatment yang cocok dengan pencarian.
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<script>
(function () {
    const searchInput = document.getElementById('treatmentSearchInput');
    const resetButton = document.getElementById('treatmentSearchReset');
    const infoBox = document.getElementById('treatmentSearchInfo');
    const rows = Array.from(document.querySelectorAll('.treatment-row'));
    const noResultRow = document.getElementById('noSearchResultRow');
    const emptyDataRow = document.getElementById('emptyDataRow');

    if (!searchInput) {
        return;
    }

    function filterTreatmentTable() {
        const keyword = (searchInput.value || '').toLowerCase().trim();
        let visibleCount = 0;

        rows.forEach(function (row) {
            const haystack = (row.getAttribute('data-search') || '').toLowerCase();
            const matched = keyword === '' || haystack.includes(keyword);

            row.style.display = matched ? '' : 'none';

            if (matched) {
                visibleCount++;
            }
        });

        if (emptyDataRow) {
            emptyDataRow.style.display = rows.length === 0 ? '' : 'none';
        }

        if (noResultRow) {
            noResultRow.style.display = (rows.length > 0 && visibleCount === 0) ? '' : 'none';
        }

        if (infoBox) {
            if (keyword === '') {
                infoBox.textContent = 'Menampilkan semua data';
            } else {
                infoBox.textContent = 'Ditemukan ' + visibleCount + ' treatment';
            }
        }
    }

    searchInput.addEventListener('input', filterTreatmentTable);

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            searchInput.value = '';
            filterTreatmentTable();
            searchInput.focus();
        });
    }

    filterTreatmentTable();
})();
</script>
@endsection