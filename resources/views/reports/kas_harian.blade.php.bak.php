<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Kas Harian
                    </h2>
                    {{-- TANDA: kalau ini tidak kelihatan, berarti file ini belum kepakai --}}
                    <div class="text-xs text-red-600 font-semibold">
                        ✅ FILTER AKTIF (Hari ini / Mingguan / Bulanan / Rentang)
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('reports.kas_harian', ['start' => now()->toDateString(), 'end' => now()->toDateString()]) }}"
                       class="px-3 py-1 rounded border text-sm hover:bg-gray-50">
                        Hari ini
                    </a>

                    <a href="{{ route('reports.kas_harian', ['start' => now()->startOfWeek()->toDateString(), 'end' => now()->endOfWeek()->toDateString()]) }}"
                       class="px-3 py-1 rounded border text-sm hover:bg-gray-50">
                        Mingguan
                    </a>

                    <a href="{{ route('reports.kas_harian', ['start' => now()->startOfMonth()->toDateString(), 'end' => now()->endOfMonth()->toDateString()]) }}"
                       class="px-3 py-1 rounded border text-sm hover:bg-gray-50">
                        Bulanan
                    </a>

                    <a href="{{ route('reports.kas_harian') }}"
                       class="px-3 py-1 rounded border text-sm hover:bg-gray-50">
                        Reset
                    </a>
                </div>
            </div>

            <form method="GET" action="{{ route('reports.kas_harian') }}" class="flex items-center gap-2 flex-wrap">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Mulai</label>
                    <input
                        type="date"
                        name="start"
                        value="{{ request('start', $start ?? '') }}"
                        class="border rounded px-2 py-1 text-sm"
                    >
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Sampai</label>
                    <input
                        type="date"
                        name="end"
                        value="{{ request('end', $end ?? '') }}"
                        class="border rounded px-2 py-1 text-sm"
                    >
                </div>

                <button type="submit" class="px-3 py-1 rounded bg-gray-800 text-white text-sm">
                    Terapkan Rentang
                </button>
            </form>

            <div class="text-sm text-gray-600">
                Periode:
                <span class="font-medium">{{ request('start', $start ?? '-') }}</span>
                s/d
                <span class="font-medium">{{ request('end', $end ?? '-') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="text-left py-2">Tanggal</th>
                                    <th class="text-right py-2">Masuk</th>
                                    <th class="text-right py-2">Keluar</th>
                                    <th class="text-right py-2">Net</th>
                                </tr>
                            </thead>

                            <tbody>
                                @php
                                    $totalMasuk = 0;
                                    $totalKeluar = 0;
                                    $totalNet = 0;
                                @endphp

                                @forelse($rows as $r)
                                    @php
                                        $masuk = (float)($r['masuk'] ?? 0);
                                        $keluar = (float)($r['keluar'] ?? 0);
                                        $net = (float)($r['net'] ?? ($masuk - $keluar));

                                        $totalMasuk += $masuk;
                                        $totalKeluar += $keluar;
                                        $totalNet += $net;
                                    @endphp

                                    <tr class="border-t">
                                        <td class="py-2">{{ $r['date'] ?? '-' }}</td>
                                        <td class="py-2 text-right">{{ number_format($masuk, 0, ',', '.') }}</td>
                                        <td class="py-2 text-right">{{ number_format($keluar, 0, ',', '.') }}</td>
                                        <td class="py-2 text-right">{{ number_format($net, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr class="border-t">
                                        <td class="py-2" colspan="4">Tidak ada data pada periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot>
                                <tr class="border-t font-semibold bg-gray-50">
                                    <td class="py-2 text-left">TOTAL</td>
                                    <td class="py-2 text-right">{{ number_format($totalMasuk, 0, ',', '.') }}</td>
                                    <td class="py-2 text-right">{{ number_format($totalKeluar, 0, ',', '.') }}</td>
                                    <td class="py-2 text-right">{{ number_format($totalNet, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>