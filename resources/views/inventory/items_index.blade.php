<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Inventori - Master Item</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if (session('success'))
                    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
                @endif

                <div class="mb-4">
                    <a href="{{ route('inv.items.create') }}" class="underline text-sm text-gray-600">+ Tambah Item</a>
                </div>

                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Nama</th>
                            <th class="text-left">Satuan</th>
                            <th class="text-right">Stok Awal</th>
                            <th class="text-left">Aktif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $it)
                            <tr class="border-t">
                                <td class="py-2">{{ $it->name }}</td>
                                <td class="py-2">{{ $it->unit }}</td>
                                <td class="py-2 text-right">{{ number_format($it->opening_stock, 2, ',', '.') }}</td>
                                <td class="py-2">{{ $it->is_active ? 'Ya' : 'Tidak' }}</td>
                            </tr>
                        @empty
                            <tr class="border-t"><td colspan="4" class="py-2">Belum ada item.</td></tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</x-app-layout>