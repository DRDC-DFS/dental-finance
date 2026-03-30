@extends('layouts.app')

@section('content')
@php
    $items = collect($items ?? []);
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0">Master Item Inventory</h4>
        <div class="text-muted small">Kelola item inventory, jenis, satuan, minimum stok, dan status aktif.</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('inventory.panel') }}" class="btn btn-outline-secondary btn-sm">
            Kembali ke Panel
        </a>
        <a href="{{ route('inventory.items.create') }}" class="btn btn-primary btn-sm">
            + Tambah Item
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

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

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.items.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter Jenis</label>
                <select name="type" class="form-select">
                    <option value="">-- Semua --</option>
                    <option value="barang" {{ ($typeFilter ?? '') === 'barang' ? 'selected' : '' }}>Barang</option>
                    <option value="bahan" {{ ($typeFilter ?? '') === 'bahan' ? 'selected' : '' }}>Bahan</option>
                    <option value="alat" {{ ($typeFilter ?? '') === 'alat' ? 'selected' : '' }}>Alat</option>
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100">Filter</button>
            </div>

            <div class="col-md-2">
                <a href="{{ route('inventory.items.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">

        @if($items->count() === 0)
            <div class="text-muted">Belum ada data item inventory.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 32%">Nama Item</th>
                            <th style="width: 12%">Jenis</th>
                            <th style="width: 12%">Satuan</th>
                            <th style="width: 16%" class="text-end">Minimum Stok</th>
                            <th style="width: 12%">Status</th>
                            <th style="width: 16%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td>{{ ucfirst($item->type ?? 'barang') }}</td>
                                <td>{{ $item->unit ?: '-' }}</td>
                                <td class="text-end">{{ number_format((float) $item->minimum_stock, 2, ',', '.') }}</td>
                                <td>
                                    @if($item->is_active)
                                        <span class="badge bg-success">ACTIVE</span>
                                    @else
                                        <span class="badge bg-secondary">INACTIVE</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('inventory.items.edit', $item) }}" class="btn btn-outline-primary btn-sm">
                                        Edit
                                    </a>

                                    <form method="POST" action="{{ route('inventory.items.update', $item) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="is_active" value="{{ $item->is_active ? 0 : 1 }}">
                                        <button class="btn btn-outline-secondary btn-sm" type="submit">
                                            {{ $item->is_active ? 'Nonaktif' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="6">
                                Total Item: {{ number_format($items->count(), 0, ',', '.') }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

    </div>
</div>
@endsection