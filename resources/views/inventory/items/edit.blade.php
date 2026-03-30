@extends('layouts.app')

@section('content')
@php
    $selectedName = old('name', $item->name ?? '');
    $selectedType = old('type', $item->type ?? 'barang');
    $selectedUnit = old('unit', $item->unit ?? 'pcs');
    $selectedMinimumStock = old('minimum_stock', $item->minimum_stock ?? 0);
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0">Edit Item Inventory</h4>
        <div class="text-muted small">Perbarui master item inventory agar data stok dan pergerakan tetap akurat.</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('inventory.panel') }}" class="btn btn-outline-secondary btn-sm">
            Kembali ke Panel
        </a>
        <a href="{{ route('inventory.items.index') }}" class="btn btn-outline-dark btn-sm">
            Master Item
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
        <ul class="mb-0">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">

        <form method="POST" action="{{ route('inventory.items.update', $item) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Item</label>
                    <input type="text" name="name" class="form-control" value="{{ $selectedName }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Jenis</label>
                    <select name="type" class="form-select" required>
                        <option value="barang" {{ $selectedType === 'barang' ? 'selected' : '' }}>Barang</option>
                        <option value="bahan" {{ $selectedType === 'bahan' ? 'selected' : '' }}>Bahan</option>
                        <option value="alat" {{ $selectedType === 'alat' ? 'selected' : '' }}>Alat</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Satuan</label>
                    <input type="text" name="unit" class="form-control" value="{{ $selectedUnit }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Minimum Stok</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="minimum_stock"
                        class="form-control"
                        value="{{ $selectedMinimumStock }}"
                        required
                    >
                </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('inventory.items.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </form>

    </div>
</div>
@endsection