@extends('layouts.app')

@section('content')

@php
  $title = $type === 'IN' ? 'Tambah Inventori Masuk' : ($type === 'OUT' ? 'Tambah Inventori Keluar' : 'Tambah Adjustment Stok');
@endphp

<h4 class="mb-3">{{ $title }}</h4>

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
    <form method="POST" action="{{ route('inventory.movements.store', ['type' => strtolower($type)]) }}">
      @csrf

      <div class="mb-3">
        <label class="form-label">Tanggal</label>
        <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Item</label>
        <select name="item_id" class="form-select" required>
          <option value="">-- pilih --</option>
          @foreach($items as $it)
            <option value="{{ $it->id }}" {{ old('item_id') == $it->id ? 'selected' : '' }}>
              {{ $it->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Qty</label>
        <input type="number" step="0.01" min="0.01" name="qty" class="form-control" value="{{ old('qty') }}" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Reference (opsional)</label>
        <input type="text" name="reference" class="form-control" maxlength="120" value="{{ old('reference') }}">
      </div>

      <div class="mb-3">
        <label class="form-label">Catatan (opsional)</label>
        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
      </div>

      <button class="btn btn-primary">Simpan</button>
      <a href="{{ route('inventory.movements.index', ['type' => strtolower($type)]) }}" class="btn btn-secondary">Kembali</a>
    </form>
  </div>
</div>

@endsection