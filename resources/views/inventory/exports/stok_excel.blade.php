<table border="1">
    <thead>
        <tr>
            <th colspan="7" style="font-weight: bold; text-align: center;">
                Laporan Stok Inventori
            </th>
        </tr>
        <tr>
            <th colspan="7" style="text-align: center;">
                {{ $periodLabel ?? '' }}
            </th>
        </tr>
        <tr>
            <th style="font-weight: bold;">Item</th>
            <th style="font-weight: bold;">Satuan</th>
            <th style="font-weight: bold;">Masuk Periode</th>
            <th style="font-weight: bold;">Keluar Periode</th>
            <th style="font-weight: bold;">Stok Akhir</th>
            <th style="font-weight: bold;">Minimum Stok</th>
            <th style="font-weight: bold;">Status</th>
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

                $status = 'AMAN';
                if ($isBelow) {
                    $status = 'DI BAWAH MINIMUM';
                } elseif ($isAtMinimum) {
                    $status = 'MINIMUM';
                }

                $totalIn += $qtyIn;
                $totalOut += $qtyOut;
            @endphp
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->unit ?: '-' }}</td>
                <td>{{ number_format($qtyIn, 2, ',', '.') }}</td>
                <td>{{ number_format($qtyOut, 2, ',', '.') }}</td>
                <td>{{ number_format($stock, 2, ',', '.') }}</td>
                <td>{{ number_format($minimum, 2, ',', '.') }}</td>
                <td>{{ $status }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align: center;">Belum ada data item inventori.</td>
            </tr>
        @endforelse
    </tbody>

    @if(($items ?? collect())->count() > 0)
        <tfoot>
            <tr>
                <th colspan="2" style="text-align: right;">Total</th>
                <th>{{ number_format((float) $totalIn, 2, ',', '.') }}</th>
                <th>{{ number_format((float) $totalOut, 2, ',', '.') }}</th>
                <th colspan="3"></th>
            </tr>
        </tfoot>
    @endif
</table>