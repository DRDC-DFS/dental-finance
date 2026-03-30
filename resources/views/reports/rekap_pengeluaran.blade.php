<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Rekap Pengeluaran
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="flex flex-wrap gap-4 mb-4">
                    <a class="underline text-sm text-gray-600"
                       href="{{ route('report.rekap_pengeluaran', ['mode' => 'harian']) }}">
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
                            <th class="text-left">Nama Pengeluaran</th>
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
                                $tunai = $r->pay_method === 'TUNAI' ? $r->amount : 0;
                                $bca   = $r->pay_method === 'BCA' ? $r->amount : 0;
                                $bni   = $r->pay_method === 'BNI' ? $r->amount : 0;
                                $bri   = $r->pay_method === 'BRI' ? $r->amount : 0;
                                $total = $r->amount;

                                $sumTunai += $tunai;
                                $sumBca += $bca;
                                $sumBni += $bni;
                                $sumBri += $bri;
                                $sumTotal += $total;
                            @endphp

                            <tr class="border-t">
                                <td class="py-2">{{ $i + 1 }}</td>
                                <td class="py-2">{{ $r->expense_date }}</td>
                                <td class="py-2">{{ $r->name }}</td>
                                <td class="py-2 text-right">{{ number_format($tunai, 0, ',', '.') }}</td>
                                <td class="py-2 text-right">{{ number_format($bca, 0, ',', '.') }}</td>
                                <td class="py-2 text-right">{{ number_format($bni, 0, ',', '.') }}</td>
                                <td class="py-2 text-right">{{ number_format($bri, 0, ',', '.') }}</td>
                                <td class="py-2 text-right">{{ number_format($total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr class="border-t">
                                <td class="py-2" colspan="8">Tidak ada data pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t font-semibold">
                            <td class="py-2" colspan="3">JUMLAH</td>
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