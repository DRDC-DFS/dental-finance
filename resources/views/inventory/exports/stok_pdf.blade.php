<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Inventori</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
        }

        h2 {
            margin: 0 0 6px 0;
        }

        .muted {
            color: #666;
            margin-bottom: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #999;
            padding: 6px 8px;
            vertical-align: middle;
        }

        th {
            background: #f1f5f9;
            text-align: left;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .warning {
            background: #fff7ed;
        }
    </style>
</head>
<body>
    <h2>Laporan Stok Inventori</h2>
    <div class="muted">{{ $periodLabel ?? '' }}</div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th style="width: 90px;">Satuan</th>
                <th style="width: 120px;" class="text-end">Masuk Periode</th>
                <th style="width: 120px;" class="text-end">Keluar Periode</th>
                <th style="width: 120px;" class="text-end">Stok Akhir</th>
                <th style="width: 120px;" class="text-end">Minimum Stok</th>
                <th style="width: 140px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalIn = 0;
                $totalOut = 0;
            @endphp

            @forelse($items as $item)
                @php
                    $qtyIn = (float) ($periodIn[$item->id] ?? 0);
                    $qtyOut = (float) ($periodOut[$item->id] ?? 0);
                    $stock = (float) ($stockEnd[$item->id] ?? 0);
                    $minimum = (float) ($item->minimum_stock ?? 0);

                    $isBelow = $minimum > 0 && $stock < $minimum;
                    $isAtMinimum = $minimum > 0 && $stock == $minimum;
                    $isAlert = $minimum > 0 && $stock <= $minimum;

                    $totalIn += $qtyIn;
                    $totalOut += $qtyOut;
                @endphp

                <tr class="{{ $isAlert ? 'warning' : '' }}">
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->unit ?: '-' }}</td>
                    <td class="text-end">{{ number_format($qtyIn, 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($qtyOut, 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($stock, 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($minimum, 2, ',', '.') }}</td>
                    <td>
                        @if($isBelow)
                            DI BAWAH MINIMUM
                        @elseif($isAtMinimum)
                            MINIMUM
                        @else
                            AMAN
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Belum ada data item inventori.</td>
                </tr>
            @endforelse
        </tbody>

        @if(($items ?? collect())->count() > 0)
            <tfoot>
                <tr>
                    <th colspan="2" class="text-end">Total</th>
                    <th class="text-end">{{ number_format((float) $totalIn, 2, ',', '.') }}</th>
                    <th class="text-end">{{ number_format((float) $totalOut, 2, ',', '.') }}</th>
                    <th colspan="3"></th>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>