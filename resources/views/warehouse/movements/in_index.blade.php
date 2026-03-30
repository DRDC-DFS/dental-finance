@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Gudang Masuk</h4>

    <a href="{{ route('warehouse.movements.create', ['type' => 'in']) }}" class="btn btn-primary btn-sm">
        + Tambah Masuk
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->has('delete'))
    <div class="alert alert-danger">{{ $errors->first('delete') }}</div>
@endif

<div class="card">
    <div class="card-body">

        @if($movements->count() === 0)
            <div class="text-muted">Belum ada data gudang masuk.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:140px">Tanggal</th>
                            <th>Item</th>
                            <th style="width:120px" class="text-end">Qty</th>
                            <th style="width:180px">Reference</th>
                            <th>Notes</th>
                            <th style="width:140px" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $m)
                            <tr>
                                <td>{{ $m->date }}</td>
                                <td>{{ $m->item->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float)$m->qty, 2, ',', '.') }}</td>
                                <td>{{ $m->reference }}</td>
                                <td>{{ $m->notes }}</td>
                                <td class="text-center">
                                    <a href="{{ route('warehouse.movements.edit', ['type'=>'in','id'=>$m->id]) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>

                                    <form method="POST" action="{{ route('warehouse.movements.destroy', ['type'=>'in','id'=>$m->id]) }}" class="d-inline"
                                          onsubmit="return confirm('Hapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
</div>

@endsection