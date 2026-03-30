<table border="1">
    <thead>
        <tr>
            <th colspan="5" style="font-weight: bold; text-align: center;">
                {{ $title ?? 'Data Inventaris' }}
            </th>
        </tr>
        <tr>
            <th colspan="5" style="text-align: center;">
                {{ $periodLabel ?? '' }}
            </th>
        </tr>
        <tr>
            <th style="font-weight: bold;">Tanggal</th>
            <th style="font-weight: bold;">Item</th>
            <th style="font-weight: bold;">Qty</th>
            <th style="font-weight: bold;">Reference</th>
            <th style="font-weight: bold;">Notes</th>
        </tr>
    </thead>
    <tbody>
        @php
            $grandTotal = 0;

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
        @endphp

        @forelse($movements as $m)
            @php
                $qty = $type === 'out'
                    ? abs((float) $m->qty)
                    : (float) $m->qty;

                $grandTotal += $qty;
            @endphp
            <tr>
                <td>{{ $formatTanggal($m->date) }}</td>
                <td>{{ $m->item->name ?? '-' }}</td>
                <td>{{ number_format($qty, 2, ',', '.') }}</td>
                <td>{{ $m->reference ?: '-' }}</td>
                <td>{{ $m->notes ?: '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align: center;">Tidak ada data.</td>
            </tr>
        @endforelse
    </tbody>

    @if(($movements ?? collect())->count() > 0)
        <tfoot>
            <tr>
                <th colspan="2" style="text-align: right;">
                    {{ ($type ?? 'in') === 'out' ? 'Total Qty Keluar' : 'Total Qty Masuk' }}
                </th>
                <th>{{ number_format((float) $grandTotal, 2, ',', '.') }}</th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    @endif
</table>