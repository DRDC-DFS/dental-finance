@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Gudang Item</h4>
    <a href="{{ route('warehouse.items.create') }}" class="btn btn-primary btn-sm">
        + Tambah Item
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body">

        @if($items->count() === 0)
            <div class="text-muted">Belum ada data item gudang.</div>
        @else
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 50%">Nama Item</th>
                        <th style="width: 15%">Satuan</th>
                        <th style="width: 15%">Minimum Stok</th>
                        <th style="width: 10%">Status</th>
                        <th style="width: 10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->unit }}</td>
                            <td>{{ number_format((float)$item->minimum_stock, 2, ',', '.') }}</td>
                            <td>
                                @if($item->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('warehouse.items.edit', $item) }}" class="btn btn-outline-primary btn-sm">
                                    Edit
                                </a>

                                <form method="POST" action="{{ route('warehouse.items.update', $item) }}" class="d-inline">
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
            </table>
        @endif

    </div>
</div>

@endsection