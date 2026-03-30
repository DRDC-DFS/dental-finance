@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Stok Inventori</h4>
  <a href="{{ route('inventory.items.index') }}" class="btn btn-outline-primary btn-sm">Master Item</a>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
      <div>
        <label class="form-label">Filter Jenis</label>
        <select name="type" class="form-select">
          <option value="">-- Semua --</option>
          <option value="barang" {{ $typeFilter==='barang' ? 'selected' : '' }}>Barang</option>
          <option value="bahan" {{ $typeFilter==='bahan' ? 'selected' : '' }}>Bahan</option>
          <option value="alat" {{ $typeFilter==='alat' ? 'selected' : '' }}>Alat</option>
        </select>
      </div>
      <div>
        <button class="btn btn-outline-primary">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    @if($rows->count() === 0)
      <div class="text-muted">Belum ada item.</div>
    @else
      <table class="table table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th>Item</th>
            <th style="width:120px;">Jenis</th>
            <th style="width:110px;">Satuan</th>
            <th style="width:140px;">Stok</th>
            <th style="width:140px;">Minimum</th>
            <th style="width:110px;">Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $r)
            <tr>
              <td>{{ $r['name'] }}</td>
              <td>{{ ucfirst($r['type']) }}</td>
              <td>{{ $r['unit'] }}</td>
              <td><strong>{{ number_format((float)$r['stock'], 2, ',', '.') }}</strong></td>
              <td>{{ number_format((float)$r['minimum_stock'], 2, ',', '.') }}</td>
              <td>
                @if(!$r['is_active'])
                  <span class="badge bg-secondary">Inactive</span>
                @elseif($r['is_low'])
                  <span class="badge bg-danger">LOW</span>
                @else
                  <span class="badge bg-success">OK</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
</div>

@endsection