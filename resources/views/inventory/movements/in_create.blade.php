@extends('layouts.app')

@section('content')
@php
    $isEdit = isset($movement) && $movement;
    $pageTitle = $isEdit ? 'Edit Inventori Masuk' : 'Tambah Inventori Masuk';

    $selectedItemId = old('item_id', $movement->item_id ?? '');
    $selectedQty = old('qty', $isEdit ? number_format((float) $movement->qty, 2, '.', '') : '');
    $selectedDate = old('date', isset($movement->date) ? \Carbon\Carbon::parse($movement->date)->format('Y-m-d') : date('Y-m-d'));
    $selectedReference = old('reference', $movement->reference ?? '');
    $selectedNotes = old('notes', $movement->notes ?? '');

    $formAction = $isEdit
        ? route('inventory.movements.update', ['type' => 'in', 'id' => $movement->id])
        : route('inventory.movements.store', ['type' => 'in']);

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
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-0">{{ $pageTitle }}</h4>
        <div class="text-muted small">
            {{ $isEdit ? 'Perbarui data inventori masuk yang sudah tersimpan.' : 'Input data inventori masuk baru.' }}
        </div>
    </div>

    <a href="{{ route('inventory.movements.index', ['type' => 'in']) }}" class="btn btn-outline-secondary btn-sm">
        Kembali ke Daftar
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $formAction }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="card mb-4">
        <div class="card-body">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Item</label>
                    <select name="item_id" class="form-control" required>
                        <option value="">Pilih Item</option>
                        @foreach($items as $id => $name)
                            <option value="{{ $id }}" {{ (string) $selectedItemId === (string) $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Qty Masuk</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        name="qty"
                        class="form-control"
                        required
                        value="{{ $selectedQty }}"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input
                        type="date"
                        name="date"
                        class="form-control"
                        value="{{ $selectedDate }}"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Reference</label>
                    <input
                        type="text"
                        name="reference"
                        class="form-control"
                        maxlength="150"
                        value="{{ $selectedReference }}"
                        placeholder="Contoh: faktur, pembelian, supplier, dll"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <textarea
                        name="notes"
                        class="form-control"
                        rows="3"
                        maxlength="500"
                        placeholder="Catatan tambahan (opsional)"
                    >{{ $selectedNotes }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button class="btn btn-primary">
                    {{ $isEdit ? 'Update' : 'Simpan' }}
                </button>

                <a href="{{ route('inventory.movements.index', ['type' => 'in']) }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>

        </div>
    </div>
</form>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <h5 class="mb-0">Riwayat Inventori Masuk (20 terakhir)</h5>
                <div class="small text-muted">Untuk cek cepat sebelum input atau edit data.</div>
            </div>

            <a href="{{ route('inventory.movements.index', ['type' => 'in']) }}" class="btn btn-outline-primary btn-sm">
                Lihat Semua
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:170px">Tanggal</th>
                        <th>Item</th>
                        <th style="width:110px" class="text-end">Qty</th>
                        <th style="width:180px">Reference</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                        <tr @if($isEdit && (int) $m->id === (int) $movement->id) class="table-warning" @endif>
                            <td>{{ $formatTanggal($m->date) }}</td>
                            <td>{{ $m->item->name ?? '-' }}</td>
                            <td class="text-end">{{ number_format((float) $m->qty, 2, ',', '.') }}</td>
                            <td>{{ $m->reference ?: '-' }}</td>
                            <td>{{ $m->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Belum ada data inventori masuk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection