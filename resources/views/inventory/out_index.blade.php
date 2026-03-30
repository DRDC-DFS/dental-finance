<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Inventori - Barang Keluar</h2>
    </x-slot>

    @php
        $isOwner = auth()->user()->role === 'OWNER';
        $today = date('Y-m-d');
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if (session('success'))
                    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
                @endif

                <div class="mb-4">
                    <a href="{{ route('inv.out.create') }}" class="underline text-sm text-gray-600">+ Tambah Barang Keluar</a>
                </div>

                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Tanggal</th>
                            <th class="text-left">Item</th>
                            <th class="text-right">Qty</th>
                            <th class="text-left">Catatan</th>
                            <th class="text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                            @php
                                $canDelete = $isOwner || ($r->move_date === $today);
                            @endphp
                            <tr class="border-t">
                                <td class="py-2">{{ $r->move_date }}</td>
                                <td class="py-2">{{ $r->item?->name }}</td>
                                <td class="py-2 text-right">{{ number_format($r->qty, 2, ',', '.') }} {{ $r->item?->unit }}</td>
                                <td class="py-2">{{ $r->note }}</td>
                                <td class="py-2">
                                    @if($canDelete)
                                        <form method="POST" action="{{ route('inv.out.delete', $r->id) }}" onsubmit="return confirm('Hapus transaksi ini?')">
                                            @csrf
                                            <button class="underline text-sm text-red-600" type="submit">Hapus</button>
                                        </form>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t"><td colspan="5" class="py-2">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="text-sm text-gray-500 mt-4">
                    Catatan: Admin hanya bisa hapus transaksi inventori pada <b>hari yang sama</b>.
                </div>

            </div>
        </div>
    </div>
</x-app-layout>