@extends('layouts.app')

@section('content')

<h4 class="mb-3">Tambah Item Gudang</h4>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">

        <form method="POST" action="{{ route('warehouse.items.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Nama Item</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                <div class="form-text">Contoh: Cotton roll cadangan, Box sarung tangan, bahan stok pusat, dll.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Satuan</label>
                <input type="text" name="unit" class="form-control" value="{{ old('unit', 'pcs') }}" required>
                <div class="form-text">Contoh: pcs, box, botol, ml, gram, set.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Minimum Stok</label>
                <input type="number" step="0.01" min="0" name="minimum_stock" class="form-control" value="{{ old('minimum_stock', 0) }}" required>
            </div>

            <button class="btn btn-primary">Simpan</button>
            <a href="{{ route('warehouse.items.index') }}" class="btn btn-secondary">Kembali</a>

        </form>

    </div>
</div>

@endsection