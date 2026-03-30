<table>
    <tr>
        <td colspan="8"><strong>LAPORAN KAS HARIAN</strong></td>
    </tr>
    <tr>
        <td colspan="8">Periode:
            {{ ($start ?? '-') === ($end ?? '-') ? ($start ?? '-') : (($start ?? '-') . ' s/d ' . ($end ?? '-')) }}
        </td>
    </tr>
    <tr>
        <td colspan="8">Role Export: {{ strtolower((string) (auth()->user()->role ?? 'admin')) }}</td>
    </tr>
    <tr></tr>

    <tr>
        <th>Tanggal</th>
        <th>Keterangan</th>
        <th>Total Pembayaran Masuk Nyata</th>
        <th>Masuk Klinik Reguler</th>
        <th>Bayar Kasus Khusus</th>
        <th>Keluar Tunai Klinik</th>
        <th>Keluar Non Tunai Klinik</th>
        <th>Net Tunai Disetor</th>
    </tr>

    @php
        $rows = is_array($rows ?? null) ? $rows : [];
        $isOwner = strtolower((string) (auth()->user()->role ?? '')) === 'owner';
        $totals = [
            'total_pembayaran_operasional' => 0,
            'masuk_klinik_reguler' => 0,
            'masuk_kasus_khusus' => 0,
            'keluar_tunai' => 0,
            'keluar_non_tunai' => 0,
            'net_tunai_disetor' => 0,
        ];
    @endphp

    @forelse($rows as $r)
        @php
            $totals['total_pembayaran_operasional'] += (float) ($r['total_pembayaran_operasional'] ?? 0);
            $totals['masuk_klinik_reguler'] += (float) ($r['masuk_klinik_reguler'] ?? 0);
            $totals['masuk_kasus_khusus'] += (float) ($r['masuk_kasus_khusus'] ?? 0);
            $totals['keluar_tunai'] += (float) ($r['keluar_tunai'] ?? 0);
            $totals['keluar_non_tunai'] += (float) ($r['keluar_non_tunai'] ?? 0);
            $totals['net_tunai_disetor'] += (float) ($r['net_tunai_disetor'] ?? 0);
        @endphp
        <tr>
            <td>{{ $r['date'] ?? '-' }}</td>
            <td>{{ $r['payer_label'] ?? '-' }}</td>
            <td>{{ (float) ($r['total_pembayaran_operasional'] ?? 0) }}</td>
            <td>{{ (float) ($r['masuk_klinik_reguler'] ?? 0) }}</td>
            <td>{{ (float) ($r['masuk_kasus_khusus'] ?? 0) }}</td>
            <td>{{ (float) ($r['keluar_tunai'] ?? 0) }}</td>
            <td>{{ (float) ($r['keluar_non_tunai'] ?? 0) }}</td>
            <td>{{ (float) ($r['net_tunai_disetor'] ?? 0) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8">Tidak ada data pada periode ini.</td>
        </tr>
    @endforelse

    @if(!empty($rows))
        <tr>
            <td colspan="2"><strong>TOTAL</strong></td>
            <td><strong>{{ $totals['total_pembayaran_operasional'] }}</strong></td>
            <td><strong>{{ $totals['masuk_klinik_reguler'] }}</strong></td>
            <td><strong>{{ $totals['masuk_kasus_khusus'] }}</strong></td>
            <td><strong>{{ $totals['keluar_tunai'] }}</strong></td>
            <td><strong>{{ $totals['keluar_non_tunai'] }}</strong></td>
            <td><strong>{{ $totals['net_tunai_disetor'] }}</strong></td>
        </tr>
    @endif

    <tr></tr>
    <tr>
        <td colspan="8"><strong>DETAIL TRANSAKSI MASUK KLINIK REGULER / ADMIN</strong></td>
    </tr>
    <tr>
        <th>Tanggal</th>
        <th>Invoice</th>
        <th>Pasien</th>
        <th>Kategori Pasien</th>
        <th>Metode</th>
        <th>Channel</th>
        <th>Jumlah</th>
        <th></th>
    </tr>

    @php
        $paymentDetails = is_array($paymentDetails ?? null) ? $paymentDetails : [];
    @endphp

    @forelse($paymentDetails as $d)
        <tr>
            <td>{{ $d['date'] ?? '-' }}</td>
            <td>{{ $d['invoice_number'] ?? '-' }}</td>
            <td>{{ $d['patient_name'] ?? '-' }}</td>
            <td>{{ $d['payer_label'] ?? '-' }}</td>
            <td>{{ $d['payment_method_name'] ?? '-' }}</td>
            <td>{{ $d['channel'] ?? '-' }}</td>
            <td>{{ (float) ($d['amount'] ?? 0) }}</td>
            <td></td>
        </tr>
    @empty
        <tr>
            <td colspan="8">Tidak ada detail transaksi reguler/admin pada periode ini.</td>
        </tr>
    @endforelse

    @if($isOwner)
        <tr></tr>
        <tr>
            <td colspan="8"><strong>DETAIL PENDAPATAN KLINIK DIAKUI</strong></td>
        </tr>
        <tr>
            <th>Tanggal Diakui</th>
            <th>Invoice</th>
            <th>Pasien</th>
            <th>Jenis Kasus</th>
            <th>Biaya Vendor / LAB</th>
            <th>Pendapatan Klinik Diakui</th>
            <th></th>
            <th></th>
        </tr>

        @php
            $recognizedIncomeDetails = is_array($recognizedIncomeDetails ?? null) ? $recognizedIncomeDetails : [];
        @endphp

        @forelse($recognizedIncomeDetails as $item)
            <tr>
                <td>{{ $item['recognized_date'] ?? '-' }}</td>
                <td>{{ $item['invoice_number'] ?? '-' }}</td>
                <td>{{ $item['patient_name'] ?? '-' }}</td>
                <td>{{ $item['case_type_label'] ?? '-' }}</td>
                <td>{{ (float) ($item['lab_bill_amount'] ?? 0) }}</td>
                <td>{{ (float) ($item['clinic_income_amount'] ?? 0) }}</td>
                <td></td>
                <td></td>
            </tr>
        @empty
            <tr>
                <td colspan="8">Belum ada pendapatan klinik diakui pada periode ini.</td>
            </tr>
        @endforelse
    @endif
</table>