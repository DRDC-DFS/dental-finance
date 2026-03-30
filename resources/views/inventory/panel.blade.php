@extends('layouts.app')

@section('content')
@php
    $formatTanggal = function ($value) {
        if (!$value) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d-m-Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $activeTab = $activeTab ?? 'items';
    $stockItems = collect($stockItems ?? []);
    $items = collect($items ?? []);
    $movementsIn = collect($movementsIn ?? []);
    $movementsOut = collect($movementsOut ?? []);

    $stockSummary = [
        'below' => 0,
        'minimum' => 0,
        'safe' => 0,
    ];

    $priorityAlerts = [];

    foreach ($stockItems as $item) {
        $stock = (float) ($stockEnd[$item->id] ?? 0);
        $minimum = (float) ($item->minimum_stock ?? 0);

        $isBelow = $minimum > 0 && $stock < $minimum;
        $isAtMinimum = $minimum > 0 && $stock == $minimum;

        if ($isBelow) {
            $stockSummary['below']++;
            $priorityAlerts[] = [
                'name' => $item->name,
                'unit' => $item->unit,
                'stock' => $stock,
                'minimum' => $minimum,
                'status' => 'below',
                'gap' => $minimum - $stock,
            ];
        } elseif ($isAtMinimum) {
            $stockSummary['minimum']++;
            $priorityAlerts[] = [
                'name' => $item->name,
                'unit' => $item->unit,
                'stock' => $stock,
                'minimum' => $minimum,
                'status' => 'minimum',
                'gap' => 0,
            ];
        } else {
            $stockSummary['safe']++;
        }
    }

    usort($priorityAlerts, function ($a, $b) {
        $statusWeight = function ($status) {
            return $status === 'below' ? 1 : 2;
        };

        $weightA = $statusWeight($a['status'] ?? '');
        $weightB = $statusWeight($b['status'] ?? '');

        if ($weightA !== $weightB) {
            return $weightA <=> $weightB;
        }

        return ((float) ($b['gap'] ?? 0)) <=> ((float) ($a['gap'] ?? 0));
    });

    $priorityAlerts = array_slice($priorityAlerts, 0, 8);

    $totalInQty = (float) $movementsIn->sum('qty');
    $totalOutQty = (float) $movementsOut->sum(fn ($row) => abs((float) $row->qty));
@endphp

<style>
    .inventory-panel-tabs {
        border-bottom: 1px solid #dee2e6;
        gap: 6px;
    }

    .inventory-panel-tabs .nav-link {
        border: 1px solid transparent;
        border-top-left-radius: .5rem;
        border-top-right-radius: .5rem;
        font-weight: 600;
        padding: .65rem 1rem;
        transition: all .2s ease;
        background: #f8f9fa;
    }

    .inventory-panel-tabs .nav-link:hover {
        opacity: .92;
    }

    .inventory-panel-tabs .nav-link.tab-items {
        color: #0d6efd;
    }

    .inventory-panel-tabs .nav-link.tab-in {
        color: #198754;
    }

    .inventory-panel-tabs .nav-link.tab-out {
        color: #dc3545;
    }

    .inventory-panel-tabs .nav-link.tab-stock {
        color: #212529;
    }

    .inventory-panel-tabs .nav-link.tab-items.active {
        color: #0d6efd;
        background: #eaf2ff;
        border-color: #b6d4fe #b6d4fe #fff;
        box-shadow: inset 0 -3px 0 #0d6efd;
    }

    .inventory-panel-tabs .nav-link.tab-in.active {
        color: #198754;
        background: #eaf7ef;
        border-color: #a3cfbb #a3cfbb #fff;
        box-shadow: inset 0 -3px 0 #198754;
    }

    .inventory-panel-tabs .nav-link.tab-out.active {
        color: #dc3545;
        background: #fdecef;
        border-color: #f1aeb5 #f1aeb5 #fff;
        box-shadow: inset 0 -3px 0 #dc3545;
    }

    .inventory-panel-tabs .nav-link.tab-stock.active {
        color: #212529;
        background: #f1f3f5;
        border-color: #ced4da #ced4da #fff;
        box-shadow: inset 0 -3px 0 #212529;
    }

    .inventory-alert-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 1rem;
        height: 100%;
        background: #fff;
    }

    .inventory-alert-card .alert-label {
        font-size: .85rem;
        color: #6c757d;
        margin-bottom: .35rem;
    }

    .inventory-alert-card .alert-value {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1;
    }

    .inventory-alert-card.alert-danger-soft {
        background: #fff5f5;
        border-color: #f5c2c7;
    }

    .inventory-alert-card.alert-warning-soft {
        background: #fff8e1;
        border-color: #ffe69c;
    }

    .inventory-alert-card.alert-success-soft {
        background: #f0fff4;
        border-color: #badbcc;
    }

    .inventory-priority-box {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        background: #fff;
    }

    .inventory-priority-item + .inventory-priority-item {
        border-top: 1px dashed #e9ecef;
    }

    .inventory-priority-item {
        padding: .75rem 0;
    }

    .inventory-mini-badge {
        display: inline-block;
        padding: .25rem .5rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700;
    }

    .inventory-mini-badge.badge-below {
        background: #dc3545;
        color: #fff;
    }

    .inventory-mini-badge.badge-minimum {
        background: #ffc107;
        color: #212529;
    }
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0">Panel Inventory</h4>
        <div class="text-muted small">
            Ringkasan master item, inventory masuk, inventory keluar, dan stok dalam satu halaman.
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('inventory.items.create') }}" class="btn btn-primary btn-sm">
            + Tambah Item
        </a>
        <a href="{{ route('inventory.movements.create', ['type' => 'in']) }}" class="btn btn-success btn-sm">
            + Tambah Masuk
        </a>
        <a href="{{ route('inventory.movements.create', ['type' => 'out']) }}" class="btn btn-warning btn-sm">
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

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="inventory-alert-card alert-danger-soft">
            <div class="alert-label">Item di bawah minimum</div>
            <div class="alert-value text-danger">{{ number_format($stockSummary['below'], 0, ',', '.') }}</div>
            <div class="small text-muted mt-2">Perlu diprioritaskan untuk pembelian atau restock.</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="inventory-alert-card alert-warning-soft">
            <div class="alert-label">Item pas minimum</div>
            <div class="alert-value text-warning">{{ number_format($stockSummary['minimum'], 0, ',', '.') }}</div>
            <div class="small text-muted mt-2">Masih aman tipis, tetapi sebaiknya mulai dipantau.</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="inventory-alert-card alert-success-soft">
            <div class="alert-label">Item aman</div>
            <div class="alert-value text-success">{{ number_format($stockSummary['safe'], 0, ',', '.') }}</div>
            <div class="small text-muted mt-2">Stok masih berada di atas batas minimum.</div>
        </div>
    </div>
</div>

@if(count($priorityAlerts) > 0)
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div>
                    <h5 class="mb-0">Prioritas Alert Stok</h5>
                    <div class="text-muted small">Item yang perlu diperhatikan lebih dulu.</div>
                </div>

                @if($activeTab !== 'stock')
                    <a href="{{ route('inventory.panel', array_filter([
                        'tab' => 'stock',
                        'type' => $typeFilter ?? null,
                        'date' => $filterDate ?? null,
                        'date_start' => $dateStart ?? null,
                        'date_end' => $dateEnd ?? null,
                    ])) }}" class="btn btn-outline-dark btn-sm">
                        Buka Tab Stok
                    </a>
                @endif
            </div>

            <div class="inventory-priority-box p-3">
                @foreach($priorityAlerts as $alert)
                    <div class="inventory-priority-item d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="fw-semibold">{{ $alert['name'] }}</div>
                            <div class="small text-muted">
                                Stok: {{ number_format((float) $alert['stock'], 2, ',', '.') }} {{ $alert['unit'] ?: '' }}
                                • Minimum: {{ number_format((float) $alert['minimum'], 2, ',', '.') }} {{ $alert['unit'] ?: '' }}
                            </div>
                        </div>

                        <div class="text-end">
                            @if(($alert['status'] ?? '') === 'below')
                                <span class="inventory-mini-badge badge-below">DI BAWAH MINIMUM</span>
                            @else
                                <span class="inventory-mini-badge badge-minimum">MINIMUM</span>
                            @endif

                            @if((float) ($alert['gap'] ?? 0) > 0)
                                <div class="small text-danger mt-1">
                                    Kurang {{ number_format((float) $alert['gap'], 2, ',', '.') }} {{ $alert['unit'] ?: '' }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.panel') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <input type="hidden" name="type" value="{{ $typeFilter ?? '' }}">

            @if($isOwner ?? false)
                <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="date_start" class="form-control" value="{{ $dateStart ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="date_end" class="form-control" value="{{ $dateEnd ?? '' }}">
                </div>
            @else
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" class="form-control" value="{{ $filterDate ?? '' }}">
                </div>
            @endif

            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100">Tampilkan Data</button>
            </div>

            <div class="col-md-2">
                <a href="{{ route('inventory.panel') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>

        <div class="small text-muted mt-2">{{ $periodLabel ?? '' }}</div>
    </div>
</div>

<ul class="nav nav-tabs mb-3 inventory-panel-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link tab-items {{ $activeTab === 'items' ? 'active' : '' }}"
           href="{{ route('inventory.panel', array_filter([
               'tab' => 'items',
               'type' => $typeFilter ?? null,
               'date' => $filterDate ?? null,
               'date_start' => $dateStart ?? null,
               'date_end' => $dateEnd ?? null,
           ])) }}">
            📦 Master Item
        </a>
    </li>

    <li class="nav-item" role="presentation">
        <a class="nav-link tab-in {{ $activeTab === 'in' ? 'active' : '' }}"
           href="{{ route('inventory.panel', array_filter([
               'tab' => 'in',
               'type' => $typeFilter ?? null,
               'date' => $filterDate ?? null,
               'date_start' => $dateStart ?? null,
               'date_end' => $dateEnd ?? null,
           ])) }}">
            ➕ Inventory Masuk
        </a>
    </li>

    <li class="nav-item" role="presentation">
        <a class="nav-link tab-out {{ $activeTab === 'out' ? 'active' : '' }}"
           href="{{ route('inventory.panel', array_filter([
               'tab' => 'out',
               'type' => $typeFilter ?? null,
               'date' => $filterDate ?? null,
               'date_start' => $dateStart ?? null,
               'date_end' => $dateEnd ?? null,
           ])) }}">
            ➖ Inventory Keluar
        </a>
    </li>

    <li class="nav-item" role="presentation">
        <a class="nav-link tab-stock {{ $activeTab === 'stock' ? 'active' : '' }}"
           href="{{ route('inventory.panel', array_filter([
               'tab' => 'stock',
               'type' => $typeFilter ?? null,
               'date' => $filterDate ?? null,
               'date_start' => $dateStart ?? null,
               'date_end' => $dateEnd ?? null,
           ])) }}">
            📊 Stok
        </a>
    </li>
</ul>

<div class="tab-content">

    <div class="tab-pane fade {{ $activeTab === 'items' ? 'show active' : '' }}" role="tabpanel">
        <div class="card mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="mb-0">Master Item Inventory</h5>
                        <div class="text-muted small">Daftar item yang digunakan pada modul inventory.</div>
                    </div>

                    <a href="{{ route('inventory.items.index') }}" class="btn btn-outline-secondary btn-sm">
                        Halaman Penuh
                    </a>
                </div>

                <form method="GET" action="{{ route('inventory.panel') }}" class="row g-2 align-items-end mb-3">
                    <input type="hidden" name="tab" value="items">
                    <input type="hidden" name="date" value="{{ $filterDate ?? '' }}">
                    <input type="hidden" name="date_start" value="{{ $dateStart ?? '' }}">
                    <input type="hidden" name="date_end" value="{{ $dateEnd ?? '' }}">

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
                        <a href="{{ route('inventory.panel', array_filter([
                            'tab' => 'items',
                            'date' => $filterDate ?? null,
                            'date_start' => $dateStart ?? null,
                            'date_end' => $dateEnd ?? null,
                        ])) }}" class="btn btn-outline-secondary w-100">
                            Reset
                        </a>
                    </div>
                </form>

                @if($items->count() === 0)
                    <div class="text-muted">Belum ada data item inventory.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 28%">Nama Item</th>
                                    <th style="width: 12%">Jenis</th>
                                    <th style="width: 12%">Satuan</th>
                                    <th style="width: 14%" class="text-end">Stok Saat Ini</th>
                                    <th style="width: 14%" class="text-end">Minimum Stok</th>
                                    <th style="width: 10%">Status</th>
                                    <th style="width: 10%">Aktif</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    @php
                                        $stock = (float) ($stockEnd[$item->id] ?? 0);
                                        $minimum = (float) ($item->minimum_stock ?? 0);
                                        $isBelow = $minimum > 0 && $stock < $minimum;
                                        $isAtMinimum = $minimum > 0 && $stock == $minimum;
                                    @endphp
                                    <tr class="{{ $isBelow || $isAtMinimum ? 'table-warning' : '' }}">
                                        <td>{{ $item->name }}</td>
                                        <td>{{ ucfirst($item->type ?? 'barang') }}</td>
                                        <td>{{ $item->unit ?: '-' }}</td>
                                        <td class="text-end fw-semibold">{{ number_format($stock, 2, ',', '.') }}</td>
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
                                        <td>
                                            @if($item->is_active)
                                                <span class="badge bg-success">ACTIVE</span>
                                            @else
                                                <span class="badge bg-secondary">INACTIVE</span>
                                            @endif
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

    <div class="tab-pane fade {{ $activeTab === 'in' ? 'show active' : '' }}" role="tabpanel">
        <div class="card mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="mb-0">Inventory Masuk</h5>
                        <div class="text-muted small">{{ $periodLabel ?? '' }}</div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('inventory.movements.create', ['type' => 'in']) }}" class="btn btn-primary btn-sm">+ Tambah Masuk</a>
                        <a href="{{ route('inventory.movements.index', array_filter([
                            'type' => 'in',
                            'date' => $filterDate ?? null,
                            'date_start' => $dateStart ?? null,
                            'date_end' => $dateEnd ?? null,
                        ])) }}" class="btn btn-outline-secondary btn-sm">Halaman Penuh</a>
                        <a href="{{ route('inventory.movements.export.pdf', array_filter([
                            'type' => 'in',
                            'date' => $filterDate ?? null,
                            'date_start' => $dateStart ?? null,
                            'date_end' => $dateEnd ?? null,
                        ])) }}" class="btn btn-outline-danger btn-sm">PDF</a>
                        <a href="{{ route('inventory.movements.export.excel', array_filter([
                            'type' => 'in',
                            'date' => $filterDate ?? null,
                            'date_start' => $dateStart ?? null,
                            'date_end' => $dateEnd ?? null,
                        ])) }}" class="btn btn-outline-success btn-sm">Excel</a>
                    </div>
                </div>

                @if($movementsIn->count() === 0)
                    <div class="text-muted">Belum ada data inventory masuk pada filter yang dipilih.</div>
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
                                        <td>{{ $formatTanggal($m->date) }}</td>
                                        <td>{{ $m->item->name ?? '-' }}</td>
                                        <td class="text-end">{{ number_format((float) $m->qty, 2, ',', '.') }}</td>
                                        <td>{{ $m->reference ?: '-' }}</td>
                                        <td>{{ $m->notes ?: '-' }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('inventory.movements.edit', ['type' => 'in', 'id' => $m->id]) }}" class="btn btn-sm btn-warning">
                                                Edit
                                            </a>

                                            <form method="POST"
                                                  action="{{ route('inventory.movements.destroy', ['type' => 'in', 'id' => $m->id]) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Hapus data ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2" class="text-end">Total Qty Masuk</th>
                                    <th class="text-end">{{ number_format($totalInQty, 2, ',', '.') }}</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <div class="tab-pane fade {{ $activeTab === 'out' ? 'show active' : '' }}" role="tabpanel">
        <div class="card mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="mb-0">Inventory Keluar</h5>
                        <div class="text-muted small">{{ $periodLabel ?? '' }}</div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('inventory.movements.create', ['type' => 'out']) }}" class="btn btn-primary btn-sm">+ Tambah Keluar</a>
                        <a href="{{ route('inventory.movements.index', array_filter([
                            'type' => 'out',
                            'date' => $filterDate ?? null,
                            'date_start' => $dateStart ?? null,
                            'date_end' => $dateEnd ?? null,
                        ])) }}" class="btn btn-outline-secondary btn-sm">Halaman Penuh</a>
                        <a href="{{ route('inventory.movements.export.pdf', array_filter([
                            'type' => 'out',
                            'date' => $filterDate ?? null,
                            'date_start' => $dateStart ?? null,
                            'date_end' => $dateEnd ?? null,
                        ])) }}" class="btn btn-outline-danger btn-sm">PDF</a>
                        <a href="{{ route('inventory.movements.export.excel', array_filter([
                            'type' => 'out',
                            'date' => $filterDate ?? null,
                            'date_start' => $dateStart ?? null,
                            'date_end' => $dateEnd ?? null,
                        ])) }}" class="btn btn-outline-success btn-sm">Excel</a>
                    </div>
                </div>

                @if($movementsOut->count() === 0)
                    <div class="text-muted">Belum ada data inventory keluar pada filter yang dipilih.</div>
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
                                        <td>{{ $formatTanggal($m->date) }}</td>
                                        <td>{{ $m->item->name ?? '-' }}</td>
                                        <td class="text-end">{{ number_format(abs((float) $m->qty), 2, ',', '.') }}</td>
                                        <td>{{ $m->reference ?: '-' }}</td>
                                        <td>{{ $m->notes ?: '-' }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('inventory.movements.edit', ['type' => 'out', 'id' => $m->id]) }}" class="btn btn-sm btn-warning">
                                                Edit
                                            </a>

                                            <form method="POST"
                                                  action="{{ route('inventory.movements.destroy', ['type' => 'out', 'id' => $m->id]) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Hapus data ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2" class="text-end">Total Qty Keluar</th>
                                    <th class="text-end">{{ number_format($totalOutQty, 2, ',', '.') }}</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <div class="tab-pane fade {{ $activeTab === 'stock' ? 'show active' : '' }}" role="tabpanel">
        <div class="card mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="mb-0">Laporan Stok Inventory</h5>
                        <div class="text-muted small">{{ $periodLabel ?? '' }}</div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('inventory.stok', array_filter([
                            'date' => $filterDate ?? null,
                            'date_start' => $dateStart ?? null,
                            'date_end' => $dateEnd ?? null,
                        ])) }}" class="btn btn-outline-secondary btn-sm">Halaman Penuh</a>
                        <a href="{{ route('inventory.stok.export.pdf', array_filter([
                            'date' => $filterDate ?? null,
                            'date_start' => $dateStart ?? null,
                            'date_end' => $dateEnd ?? null,
                        ])) }}" class="btn btn-outline-danger btn-sm">PDF</a>
                        <a href="{{ route('inventory.stok.export.excel', array_filter([
                            'date' => $filterDate ?? null,
                            'date_start' => $dateStart ?? null,
                            'date_end' => $dateEnd ?? null,
                        ])) }}" class="btn btn-outline-success btn-sm">Excel</a>
                    </div>
                </div>

                @if($stockItems->count() === 0)
                    <div class="text-muted">Belum ada data item inventory.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th style="width:120px">Satuan</th>
                                    <th style="width:150px" class="text-end">Masuk Periode</th>
                                    <th style="width:150px" class="text-end">Keluar Periode</th>
                                    <th style="width:150px" class="text-end">Stok Akhir</th>
                                    <th style="width:150px" class="text-end">Minimum Stok</th>
                                    <th style="width:170px">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockItems as $item)
                                    @php
                                        $qtyIn = (float) ($periodIn[$item->id] ?? 0);
                                        $qtyOut = (float) ($periodOut[$item->id] ?? 0);
                                        $stock = (float) ($stockEnd[$item->id] ?? 0);
                                        $minimum = (float) ($item->minimum_stock ?? 0);

                                        $isBelow = $minimum > 0 && $stock < $minimum;
                                        $isAtMinimum = $minimum > 0 && $stock == $minimum;
                                    @endphp

                                    <tr class="{{ $isBelow || $isAtMinimum ? 'table-warning' : '' }}">
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->unit ?: '-' }}</td>
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
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2" class="text-end">Total</th>
                                    <th class="text-end">{{ number_format((float) collect($periodIn)->sum(), 2, ',', '.') }}</th>
                                    <th class="text-end">{{ number_format((float) collect($periodOut)->sum(), 2, ',', '.') }}</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>

</div>
@endsection