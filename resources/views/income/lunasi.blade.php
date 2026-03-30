<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Lunasi Pemasukan
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="mb-4">
                    <div><b>Tanggal:</b> {{ $trx->trx_date }}</div>
                    <div><b>Pasien:</b> {{ $trx->patient_name }}</div>
                    <div><b>Dokter:</b> {{ $trx->doctor?->name }}</div>
                    <div><b>Total Tagihan:</b> Rp {{ number_format($trx->bill_total, 0, ',', '.') }}</div>
                </div>

                <div class="mb-6">
                    <b>Detail Tindakan:</b>
                    <div class="mt-2">
                        @foreach($trx->items as $it)
                            <div>
                                {{ $it->treatment?->name ?? '-' }}
                                @if($it->calc_type_snapshot === 'PER_GIGI')
                                    — {{ $it->qty }} x {{ number_format($it->unit_price, 0, ',', '.') }}
                                    = {{ number_format($it->subtotal, 0, ',', '.') }}
                                @else
                                    — {{ number_format($it->unit_price, 0, ',', '.') }}
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <form method="POST" action="{{ route('income.lunasi.store', $trx->id) }}">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <x-input-label for="pay_tunai" value="Tunai" />
                            <x-text-input id="pay_tunai" name="pay_tunai" type="number" min="0" step="0.01"
                                class="mt-1 block w-full text-right" value="{{ old('pay_tunai', 0) }}" />
                        </div>
                        <div>
                            <x-input-label for="pay_bca" value="BCA" />
                            <x-text-input id="pay_bca" name="pay_bca" type="number" min="0" step="0.01"
                                class="mt-1 block w-full text-right" value="{{ old('pay_bca', 0) }}" />
                        </div>
                        <div>
                            <x-input-label for="pay_bni" value="BNI" />
                            <x-text-input id="pay_bni" name="pay_bni" type="number" min="0" step="0.01"
                                class="mt-1 block w-full text-right" value="{{ old('pay_bni', 0) }}" />
                        </div>
                        <div>
                            <x-input-label for="pay_bri" value="BRI" />
                            <x-text-input id="pay_bri" name="pay_bri" type="number" min="0" step="0.01"
                                class="mt-1 block w-full text-right" value="{{ old('pay_bri', 0) }}" />
                        </div>
                    </div>

                    <x-input-error class="mt-2" :messages="$errors->get('pay_tunai')" />

                    <div class="mt-6 flex gap-4 items-center">
                        <x-primary-button>Lunasi</x-primary-button>
                        <a href="{{ route('income.index') }}" class="underline text-sm text-gray-600">Kembali</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>