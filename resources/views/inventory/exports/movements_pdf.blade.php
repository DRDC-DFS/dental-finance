<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Data Inventaris' }}</title>
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
    </style>
</head>
<body>
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

    <h2>{{ $title ?? 'Data Inventaris' }}</h2>
    <div class="muted">{{ $periodLabel ?? '' }}</div>

    <table>
        <thead>
            <tr>
                <th style="width: 110px;">Tanggal</th>
                <th>Item</th>
                <th style="width: 100px;" class="text-end">Qty</th>
                <th style="width: 160px;">Reference</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $m)
                @php
                    $qty = ($type ?? 'in') === 'out'
                        ? abs((float) $m->qty)
                        : (float) $m->qty;

                    $grandTotal += $qty;
                @endphp
                <tr>
                    <td>{{ $formatTanggal($m->date) }}</td>
                    <td>{{ $m->item->name ?? '-' }}</td>
                    <td class="text-end">{{ number_format($qty, 2, ',', '.') }}</td>
                    <td>{{ $m->reference ?: '-' }}</td>
                    <td>{{ $m->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>

        @if(($movements ?? collect())->count() > 0)
            <tfoot>
                <tr>
                    <th colspan="2" class="text-end">
                        {{ ($type ?? 'in') === 'out' ? 'Total Qty Keluar' : 'Total Qty Masuk' }}
                    </th>
                    <th class="text-end">{{ number_format((float) $grandTotal, 2, ',', '.') }}</th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>