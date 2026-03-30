@extends('layouts.app')

@section('content')

<h4 class="mb-4">{{ isset($movement) ? 'Edit Gudang Keluar' : 'Tambah Gudang Keluar' }}</h4>

@if(session('success'))
<div class="alert alert-success">
{{ session('success') }}
</div>
@endif

@if ($errors->any())
<div class="alert alert-danger">
<ul class="mb-0">
@foreach ($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif

<form method="POST" action="{{ isset($movement) ? route('warehouse.movements.update',['type'=>'out','id'=>$movement->id]) : route('warehouse.movements.store',['type'=>'out']) }}">
@csrf
@if(isset($movement))
    @method('PUT')
@endif

<div class="card mb-4">
<div class="card-body">

<div class="mb-3">
<label class="form-label">Item</label>
<select name="item_id" class="form-control" required>
<option value="">Pilih Item</option>
@foreach($items as $id=>$name)
<option value="{{ $id }}" {{ (string) old('item_id', $movement->item_id ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
@endforeach
</select>
</div>

<div class="mb-3">
<label class="form-label">Qty Keluar</label>
<input type="number" step="0.01" name="qty" class="form-control" required value="{{ old('qty', isset($movement) ? abs((float) $movement->qty) : '') }}">
</div>

<div class="mb-3">
<label class="form-label">Tanggal</label>
<input type="date" name="date" class="form-control" value="{{ old('date', isset($movement) ? optional($movement->date)->format('Y-m-d') : date('Y-m-d')) }}" required>
</div>

<div class="mb-3">
<label class="form-label">Reference</label>
<input type="text" name="reference" class="form-control" value="{{ old('reference', $movement->reference ?? '') }}">
</div>

<div class="mb-3">
<label class="form-label">Notes</label>
<textarea name="notes" class="form-control">{{ old('notes', $movement->notes ?? '') }}</textarea>
</div>

<button class="btn btn-primary">{{ isset($movement) ? 'Simpan Perubahan' : 'Simpan' }}</button>

<a href="{{ route('warehouse.movements.index',['type'=>'out']) }}" class="btn btn-secondary">
Kembali
</a>

</div>
</div>
</form>

<div class="card">
<div class="card-body">
<div class="d-flex justify-content-between align-items-center mb-2">
<h5 class="mb-0">Riwayat Gudang Keluar (20 terakhir)</h5>
<a href="{{ route('warehouse.movements.index',['type'=>'out']) }}" class="btn btn-outline-primary btn-sm">Lihat Semua</a>
</div>

<div class="table-responsive">
<table class="table table-bordered align-middle mb-0">
<thead>
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
<tr>
<td>{{ $m->date }}</td>
<td>{{ $m->item->name ?? '-' }}</td>
<td class="text-end">{{ number_format(abs((float) $m->qty),2,',','.') }}</td>
<td>{{ $m->reference }}</td>
<td>{{ $m->notes }}</td>
</tr>
@empty
<tr>
<td colspan="5" class="text-center text-muted">Belum ada data gudang keluar.</td>
</tr>
@endforelse

</tbody>
</table>
</div>

</div>
</div>

@endsection