<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Set Fee Dokter Tamu (Owner)
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="mb-4">
                    <div><b>Tanggal:</b> {{ $trx->trx_date }}</div>
                    <div><b>Pasien:</b> {{ $trx->patient_name }}</div>
                    <div><b>Dokter:</b> {{ $trx->doctor?->name }} ({{ $trx->doctor?->type }})</div>
                    <div><b>Total Tagihan:</b> Rp {{ number_format($trx->bill_total, 0, ',', '.') }}</div>
                </div>

                <form method="POST" action="{{ route('income.fee_tamu.store', $trx->id) }}">
                    @csrf

                    <div>
                        <x-input-label for="doctor_fee_manual" value="Fee Dokter Tamu (Nominal Rp)" />
                        <x-text-input id="doctor_fee_manual" name="doctor_fee_manual" type="number" min="0" step="0.01"
                                      class="mt-1 block w-full text-right"
                                      required value="{{ old('doctor_fee_manual', $trx->doctor_fee_manual ?? 0) }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('doctor_fee_manual')" />
                    </div>

                    <div class="mt-6 flex gap-4 items-center">
                        <x-primary-button>Simpan</x-primary-button>
                        <a href="{{ route('income.index') }}" class="underline text-sm text-gray-600">Kembali</a>
                    </div>
                </form>

                <div class="text-sm text-gray-500 mt-4">
                    Catatan: hanya bisa untuk transaksi <b>LUNAS</b> dan dokter bertipe <b>TAMU</b>. Admin tidak dapat melihat fitur ini.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>