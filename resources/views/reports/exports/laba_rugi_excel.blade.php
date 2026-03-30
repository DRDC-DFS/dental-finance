<table>
    <tr>
        <td colspan="2"><strong>LAPORAN LABA RUGI</strong></td>
    </tr>
    <tr>
        <td colspan="2">Periode:
            {{ ($start ?? '-') === ($end ?? '-') ? ($start ?? '-') : (($start ?? '-') . ' s/d ' . ($end ?? '-')) }}
        </td>
    </tr>
    <tr></tr>

    <tr>
        <th>Keterangan</th>
        <th>Nominal</th>
    </tr>

    <tr>
        <td>Total Pendapatan Klinik</td>
        <td>{{ (float) ($totalClinicIncome ?? 0) }}</td>
    </tr>
    <tr>
        <td>Total Pengeluaran Klinik</td>
        <td>{{ (float) ($totalExpense ?? 0) }}</td>
    </tr>
    <tr>
        <td>Net Arus Kas Klinik</td>
        <td>{{ (float) ($netClinicCashflow ?? 0) }}</td>
    </tr>
    <tr>
        <td>Pendapatan Kotor Klinik</td>
        <td>{{ (float) ($grossIncome ?? 0) }}</td>
    </tr>
    <tr>
        <td>Fee Dokter</td>
        <td>{{ (float) ($doctorFee ?? 0) }}</td>
    </tr>
    <tr>
        <td>Pendapatan Bersih Klinik</td>
        <td>{{ (float) ($netClinicIncome ?? 0) }}</td>
    </tr>
    <tr>
        <td>Laba Bersih Operasional Klinik</td>
        <td>{{ (float) ($netProfit ?? 0) }}</td>
    </tr>

    <tr></tr>
    <tr>
        <td colspan="2"><strong>BREAKDOWN PENDAPATAN KLINIK</strong></td>
    </tr>
    <tr>
        <td>Pendapatan Reguler Non Owner Finance</td>
        <td>{{ (float) ($grossIncomeRegular ?? 0) }}</td>
    </tr>
    <tr>
        <td>Pendapatan Prosto / Retainer Diakui</td>
        <td>{{ (float) ($recognizedProsthoRetainerIncome ?? 0) }}</td>
    </tr>
    <tr>
        <td>Pendapatan Klinik Dental Laboratory</td>
        <td>{{ (float) ($recognizedDentalLaboratoryIncome ?? 0) }}</td>
    </tr>
    <tr>
        <td>Pendapatan Private Owner</td>
        <td>{{ (float) ($privateOwnerIncome ?? 0) }}</td>
    </tr>

    <tr></tr>
    <tr>
        <td colspan="2"><strong>BREAKDOWN PENGELUARAN KLINIK</strong></td>
    </tr>
    <tr>
        <td>Pengeluaran Operasional Klinik</td>
        <td>{{ (float) ($operationalExpense ?? 0) }}</td>
    </tr>
    <tr>
        <td>Pengeluaran Private Owner</td>
        <td>{{ (float) ($privateOwnerExpense ?? 0) }}</td>
    </tr>

    <tr></tr>
    <tr>
        <td colspan="2"><strong>MUTASI OWNER</strong></td>
    </tr>
    <tr>
        <td>Mutasi Owner Masuk</td>
        <td>{{ (float) ($ownerMutationIncome ?? 0) }}</td>
    </tr>
    <tr>
        <td>Mutasi Owner Keluar</td>
        <td>{{ (float) ($ownerMutationExpense ?? 0) }}</td>
    </tr>
    <tr>
        <td>Arus Bersih Mutasi Owner</td>
        <td>{{ (float) ($ownerNetCashflow ?? 0) }}</td>
    </tr>
</table>