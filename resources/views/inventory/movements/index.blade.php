@extends('layouts.app')

@section('content')

@php
  $title = $type === 'IN' ? 'Inventori Masuk' : ($type === 'OUT' ? 'Inventori Keluar' : 'Adjustment Stok');
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">{{ $title }}</h4>
  <a href="{{ route('inventory.movements.create', ['type' => strtolower($type)]) }}" class="btn btn-primary btn-sm">
    + Tambah
  </a>
</div>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="GET" action="{{ route('inventory.movements.index', ['type' => strtolower($type)]) }}">
      <div class="col-md-3">
        <label class="form-label">Start</label>
        <input type="date" name="start" class="form-control" value="{{ $start }}">
      </div>
      <div class="col-md-3">
        <label class="form-label">End</label>
        <input type="date" name="end" class="form-control" value="{{ $end }}">
      </div>
      <div class="col-md-4">
        <label class="form-label">Item</label>
        <select name="item_id" class="form-select">
          <option value="">-- Semua --</option>
          @foreach($items as $it)
            <option value="{{ $it->id }}" {{ (string)$itemId === (string)$it->id ? 'selected' : '' }}>
              {{ $it->name }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-outline-primary w-100">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    @if($movements->count() === 0)
      <div class="text-muted">Belum ada data.</div>
    @else
      <table class="table table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th style="width:140px;">Tanggal</th>
            <th>Item</th>
            <th style="width:140px;">Qty</th>
            <th style="width:160px;">Ref</th>
            <th>Catatan</th>
          </tr>
        </thead>
        <tbody>
          @foreach($movements as $m)
            <tr>
              <td>{{ optional($m->date)->format('Y-m-d') }}</td>
              <td>{{ $m->item?->name }}</td>
              <td>{{ number_format((float)$m->qty, 2, ',', '.') }}</td>
              <td>{{ $m->reference }}</td>
              <td>{{ $m->notes }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>

      <div class="mt-3">
        {{ $movements->links() }}
      </div>
    @endif
  </div>
</div>

@endsection