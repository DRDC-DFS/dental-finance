<table>
    <tr>
        <td colspan="8"><strong>LAPORAN FEE DOKTER PER TINDAKAN</strong></td>
    </tr>
    <tr>
        <td colspan="8">Periode:
            {{ ($start ?? '-') === ($end ?? '-') ? ($start ?? '-') : (($start ?? '-') . ' s/d ' . ($end ?? '-')) }}
        </td>
    </tr>
    <tr></tr>

    <tr>
        <th>Dokter</th>
        <th>Tipe</th>
        <th>Tindakan</th>
        <th>Qty</th>
        <th>Jml Transaksi</th>
        <th>Gross</th>
        <th>Fee Dokter</th>
        <th>Net Klinik</th>
    </tr>

    @php
        $totalQty   = 0;
        $totalTrx   = 0;
        $totalGross = 0;
        $totalFee   = 0;
        $totalNet   = 0;
    @endphp

    @forelse(($rows ?? []) as $r)
        @php
            $qty   = (float)($r['qty_total'] ?? 0);
            $trx   = (int)($r['trx_count'] ?? 0);
            $gross = (float)($r['gross_total'] ?? 0);
            $fee   = (float)($r['fee_total'] ?? 0);
            $net   = (float)($r['net_klinik'] ?? 0);

            $totalQty += $qty;
            $totalTrx += $trx;
            $totalGross += $gross;
            $totalFee += $fee;
            $totalNet += $net;
        @endphp
        <tr>
            <td>{{ $r['doctor_name'] ?? '-' }}</td>
            <td>{{ $r['doctor_type'] ?? '-' }}</td>
            <td>{{ $r['treatment_name'] ?? '-' }}</td>
            <td>{{ $qty }}</td>
            <td>{{ $trx }}</td>
            <td>{{ $gross }}</td>
            <td>{{ $fee }}</td>
            <td>{{ $net }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8">Tidak ada data pada periode ini.</td>
        </tr>
    @endforelse

    <tr>
        <td colspan="3"><strong>TOTAL</strong></td>
        <td><strong>{{ $totalQty }}</strong></td>
        <td><strong>{{ $totalTrx }}</strong></td>
        <td><strong>{{ $totalGross }}</strong></td>
        <td><strong>{{ $totalFee }}</strong></td>
        <td><strong>{{ $totalNet }}</strong></td>
    </tr>
</table>