<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Barang Keluar</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('inv.out.store') }}">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="move_date" value="Tanggal" />
                            <x-text-input id="move_date" name="move_date" type="date" class="mt-1 block w-full" required value="{{ old('move_date', date('Y-m-d')) }}" />
                            <x-input-error class="mt-2" :messages="$errors->get('move_date')" />
                        </div>

                        <div>
                            <x-input-label for="item_id" value="Item" />
                            <select id="item_id" name="item_id" class="mt-1 block w-full border-gray-300 rounded-md" required>
                                <option value="">- Pilih Item -</option>
                                @foreach($items as $it)
                                    <option value="{{ $it->id }}">{{ $it->name }} ({{ $it->unit }})</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('item_id')" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="qty" value="Jumlah Keluar" />
                        <x-text-input id="qty" name="qty" type="number" step="0.01" min="0.01" class="mt-1 block w-full text-right" required value="{{ old('qty', 1) }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('qty')" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="note" value="Keterangan (opsional)" />
                        <x-text-input id="note" name="note" class="mt-1 block w-full" value="{{ old('note') }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('note')" />
                    </div>

                    <div class="mt-6 flex gap-4 items-center">
                        <x-primary-button>Simpan</x-primary-button>
                        <a href="{{ route('inv.out.index') }}" class="underline text-sm text-gray-600">Kembali</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>