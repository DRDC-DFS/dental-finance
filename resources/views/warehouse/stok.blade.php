@extends('layouts.app')

@section('content')

<h4 class="mb-4">Gudang - Laporan Stok</h4>

@if(isset($alertItems) && $alertItems->count() > 0)
    <div class="alert alert-warning border-warning shadow-sm">
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

<div class="card">
    <div class="card-body">

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

                @forelse($items as $item)

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

                        <td class="text-end">
                            {{ number_format($qtyIn, 2, ',', '.') }}
                        </td>

                        <td class="text-end">
                            {{ number_format($qtyOut, 2, ',', '.') }}
                        </td>

                        <td class="text-end fw-bold">
                            {{ number_format($stock, 2, ',', '.') }}
                        </td>

                        <td class="text-end">
                            {{ number_format($minimum, 2, ',', '.') }}
                        </td>

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

@endsection