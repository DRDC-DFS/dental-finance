<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Item Inventori</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('inv.items.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nama Item" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" required value="{{ old('name') }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="unit" value="Satuan" />
                        <x-text-input id="unit" name="unit" class="mt-1 block w-full" required value="{{ old('unit') }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('unit')" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="opening_stock" value="Stok Awal" />
                        <x-text-input id="opening_stock" name="opening_stock" type="number" step="0.01" min="0"
                            class="mt-1 block w-full text-right" required value="{{ old('opening_stock', 0) }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('opening_stock')" />
                    </div>

                    <div class="mt-6 flex gap-4 items-center">
                        <x-primary-button>Simpan</x-primary-button>
                        <a href="{{ route('inv.items.index') }}" class="underline text-sm text-gray-600">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>