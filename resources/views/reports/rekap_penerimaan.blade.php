<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Rekap Penerimaan
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="flex flex-wrap gap-4 mb-4">
                    <a class="underline text-sm text-gray-600"
                       href="{{ route('report.rekap_penerimaan', ['mode' => 'harian']) }}">
                        Harian (Hari ini)
                    </a>

                    <form method="GET" class="flex flex-wrap gap-3 items-end">
                        <input type="hidden" name="mode" value="mingguan">
                        <div>
                            <x-input-label for="start" value="Tanggal Mulai" />
                            <x-text-input id="start" name="start" type="date" class="mt-1 block" value="{{ $start }}" />
                        </div>
                        <div>
                            <x-input-label for="end" value="Tanggal Akhir" />
                            <x-text-input id="end" name="end" type="date" class="mt-1 block" value="{{ $end }}" />
                        </div>
                        <x-primary-button>Mingguan</x-primary-button>
                    </form>

                    <form method="GET" class="flex flex-wrap gap-3 items-end">
                        <input type="hidden" name="mode" value="bulanan">
                        <div>
                            <x-input-label for="start2" value="Tanggal Mulai" />
                            <x-text-input id="start2" name="start" type="date" class="mt-1 block" value="{{ $start }}" />
                        </div>
                        <div>
                            <x-input-label for="end2" value="Tanggal Akhir" />
                            <x-text-input id="end2" name="end" type="date" class="mt-1 block" value="{{ $end }}" />
                        </div>
                        <x-primary-button>Bulanan</x-primary-button>
                    </form>
                </div>

                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="text-left">No</th>
                            <th class="text-left">Tanggal</th>
                            <th class="text-left">Nama Pasien</th>
                            <th class="text-left">Dokter</th>
                            <th class="text-left">Status</th>
                            <th class="text-right">Tunai</th>
                            <th class="text-right">BCA</th>
                            <th class="text-right">BNI</th>
                            <th class="text-right">BRI</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $sumTunai = 0; $sumBca = 0; $sumBni = 0; $sumBri = 0; $sumTotal = 0;
                        @endphp

                        @forelse($rows as $i => $r)
                            @php
                                $sumTunai += $r->pay_tunai;
                                $sumBca += $r->pay_bca;
                                $sumBni += $r->pay_bni;
                                $sumBri += $r->pay_bri;
                                $sumTotal += $r->pay_total;
                            @endphp
                            <tr class="border-t">
                                <td class="py-2">{{ $i + 1 }}</td>
                                <td class="py-2">{{ $r->trx_date }}</td>
                                <td class="py-2">{{ $r->patient_name }}</td>
                                <td class="py-2">{{ $r->doctor?->name }}</td>
                                <td class="py-2">{{ $r->status }}</td>
                                <td class="py-2 text-right">{{ number_format($r->pay_tunai, 0, ',', '.') }}</td>
                                <td class="py-2 text-right">{{ number_format($r->pay_bca, 0, ',', '.') }}</td>
                                <td class="py-2 text-right">{{ number_format($r->pay_bni, 0, ',', '.') }}</td>
                                <td class="py-2 text-right">{{ number_format($r->pay_bri, 0, ',', '.') }}</td>
                                <td class="py-2 text-right">{{ number_format($r->pay_total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr class="border-t">
                                <td class="py-2" colspan="10">Tidak ada data pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t font-semibold">
                            <td class="py-2" colspan="5">JUMLAH</td>
                            <td class="py-2 text-right">{{ number_format($sumTunai, 0, ',', '.') }}</td>
                            <td class="py-2 text-right">{{ number_format($sumBca, 0, ',', '.') }}</td>
                            <td class="py-2 text-right">{{ number_format($sumBni, 0, ',', '.') }}</td>
                            <td class="py-2 text-right">{{ number_format($sumBri, 0, ',', '.') }}</td>
                            <td class="py-2 text-right">{{ number_format($sumTotal, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>

            </div>
        </div>
    </div>
</x-app-layout>