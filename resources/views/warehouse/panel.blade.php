@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0">Gudang Panel</h4>
        <div class="text-muted small">Gabungan Gudang Item, Gudang Masuk, Gudang Keluar, dan Stok Gudang</div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('warehouse.items.create') }}" class="btn btn-primary btn-sm">
            + Tambah Item
        </a>
        <a href="{{ route('warehouse.movements.create', ['type' => 'in']) }}" class="btn btn-success btn-sm">
            + Tambah Masuk
        </a>
        <a href="{{ route('warehouse.movements.create', ['type' => 'out']) }}" class="btn btn-warning btn-sm">
            + Tambah Keluar
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
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(isset($alertItems) && $alertItems->count() > 0)
    <div class="alert alert-warning border-warning shadow-sm mb-4">
        <div class="fw-bold mb-2">⚠ Alert Stok Minimum Gudang</div>
        <div class="small text-muted mb-2">
            Item berikut sudah mencapai atau berada di bawah minimum stok gudang:
        </div>
        <ul class="mb-0">
            @foreach($alertItems as $alert)
                <li>
                    <strong>{{ $alert->name }}</strong>
                    — stok {{ number_format((float) $alert->current_stock, 2, ',', '.') }}
                    {{ $alert->unit }}
                    / minimum {{ number_format((float) $alert->minimum_stock_value, 2, ',', '.') }}
                </li>
            @endforeach
        </ul>
    </div>
@endif

<ul class="nav nav-tabs mb-3" id="warehousePanelTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="items-tab" data-bs-toggle="tab" data-bs-target="#items-pane" type="button" role="tab">
            Gudang Item
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="in-tab" data-bs-toggle="tab" data-bs-target="#in-pane" type="button" role="tab">
            Gudang Masuk
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="out-tab" data-bs-toggle="tab" data-bs-target="#out-pane" type="button" role="tab">
            Gudang Keluar
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock-pane" type="button" role="tab">
            Stok Gudang
        </button>
    </li>
</ul>

<div class="tab-content" id="warehousePanelTabContent">

    <div class="tab-pane fade show active" id="items-pane" role="tabpanel">
        <div class="card mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">Gudang Item</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('warehouse.items.index') }}" class="btn btn-outline-secondary btn-sm">Halaman Penuh</a>
                    </div>
                </div>

                @if($items->count() === 0)
                    <div class="text-muted">Belum ada data item gudang.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45%">Nama Item</th>
                                    <th style="width: 15%">Satuan</th>
                                    <th style="width: 15%">Minimum Stok</th>
                                    <th style="width: 10%">Status</th>
                                    <th style="width: 15%">Aksi</th>
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
                    </div>
                @endif

            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="in-pane" role="tabpanel">
        <div class="card mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">Gudang Masuk</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('warehouse.movements.create', ['type' => 'in']) }}" class="btn btn-primary btn-sm">+ Tambah Masuk</a>
                        <a href="{{ route('warehouse.movements.index', ['type' => 'in']) }}" class="btn btn-outline-secondary btn-sm">Halaman Penuh</a>
                    </div>
                </div>

                @if($movementsIn->count() === 0)
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
                                @foreach($movementsIn as $m)
                                    <tr>
                                        <td>{{ $m->date }}</td>
                                        <td>{{ $m->item->name ?? '-' }}</td>
                                        <td class="text-end">{{ number_format((float)$m->qty, 2, ',', '.') }}</td>
                                        <td>{{ $m->reference }}</td>
                                        <td>{{ $m->notes }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('warehouse.movements.edit', ['type' => 'in', 'id' => $m->id]) }}" class="btn btn-sm btn-warning">
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('warehouse.movements.destroy', ['type' => 'in', 'id' => $m->id]) }}" class="d-inline"
                                                  onsubmit="return confirm('Hapus data gudang ini?')">
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
    </div>

    <div class="tab-pane fade" id="out-pane" role="tabpanel">
        <div class="card mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">Gudang Keluar</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('warehouse.movements.create', ['type' => 'out']) }}" class="btn btn-primary btn-sm">+ Tambah Keluar</a>
                        <a href="{{ route('warehouse.movements.index', ['type' => 'out']) }}" class="btn btn-outline-secondary btn-sm">Halaman Penuh</a>
                    </div>
                </div>

                @if($movementsOut->count() === 0)
                    <div class="text-muted">Belum ada data gudang keluar.</div>
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
                                @foreach($movementsOut as $m)
                                    <tr>
                                        <td>{{ $m->date }}</td>
                                        <td>{{ $m->item->name ?? '-' }}</td>
                                        <td class="text-end">{{ number_format(abs((float)$m->qty), 2, ',', '.') }}</td>
                                        <td>{{ $m->reference }}</td>
                                        <td>{{ $m->notes }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('warehouse.movements.edit', ['type' => 'out', 'id' => $m->id]) }}" class="btn btn-sm btn-warning">
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('warehouse.movements.destroy', ['type' => 'out', 'id' => $m->id]) }}" class="d-inline"
                                                  onsubmit="return confirm('Hapus data gudang ini?')">
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
    </div>

    <div class="tab-pane fade" id="stock-pane" role="tabpanel">
        <div class="card mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">Stok Gudang</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('warehouse.stok') }}" class="btn btn-outline-secondary btn-sm">Halaman Penuh</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Satuan</th>
                                <th class="text-end">Masuk</th>
                                <th class="text-end">Keluar</th>
                                <th class="text-end">Stok</th>
                                <th class="text-end">Minimum Stok</th>
                                <th class="text-start">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockItems as $item)
                                @php
                                    $qtyIn = (float)($in[$item->id] ?? 0);
                                    $qtyOut = (float)($out[$item->id] ?? 0);
                                    $stock = $qtyIn - $qtyOut;
                                    $minimum = (float)($item->minimum_stock ?? 0);

                                    $isBelow = $minimum > 0 && $stock < $minimum;
                                    $isAtMinimum = $minimum > 0 && $stock == $minimum;
                                    $isAlert = $minimum > 0 && $stock <= $minimum;

                                    $rowStyle = $isAlert ? 'background:#fff7ed;' : '';
                                @endphp

                                <tr style="{{ $rowStyle }}">
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->unit }}</td>
                                    <td class="text-end">{{ number_format($qtyIn, 2, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($qtyOut, 2, ',', '.') }}</td>
                                    <td class="text-end fw-bold">{{ number_format($stock, 2, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($minimum, 2, ',', '.') }}</td>
                                    <td>
                                        @if($isBelow)
                                            <span class="badge bg-danger">DI BAWAH MINIMUM</span>
                                        @elseif($isAtMinimum)
                                            <span class="badge bg-warning text-dark">MINIMUM</span>
                                        @else
                                            <span class="badge bg-success">AMAN</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        Belum ada data item gudang
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection