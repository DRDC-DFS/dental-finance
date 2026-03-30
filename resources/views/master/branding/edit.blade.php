<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Master - Branding') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if (session('success'))
                        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('master.branding.update') }}">
                        @csrf

                        <div>
                            <x-input-label for="clinic_name" value="Nama Klinik" />
                            <x-text-input
                                id="clinic_name"
                                name="clinic_name"
                                class="mt-1 block w-full"
                                type="text"
                                required
                                value="{{ old('clinic_name', $settings->clinic_name) }}"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('clinic_name')" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="clinic_address" value="Alamat Klinik" />
                            <textarea
                                id="clinic_address"
                                name="clinic_address"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                rows="3"
                            >{{ old('clinic_address', $settings->clinic_address) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('clinic_address')" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="clinic_phone" value="Telepon Klinik" />
                            <x-text-input
                                id="clinic_phone"
                                name="clinic_phone"
                                class="mt-1 block w-full"
                                type="text"
                                value="{{ old('clinic_phone', $settings->clinic_phone) }}"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('clinic_phone')" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="owner_doctor_name" value="Nama Dokter Owner (untuk kwitansi)" />
                            <x-text-input
                                id="owner_doctor_name"
                                name="owner_doctor_name"
                                class="mt-1 block w-full"
                                type="text"
                                value="{{ old('owner_doctor_name', $settings->owner_doctor_name) }}"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('owner_doctor_name')" />
                        </div>

                        <div class="mt-6">
                            <x-primary-button>
                                Simpan
                            </x-primary-button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>