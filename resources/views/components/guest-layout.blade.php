<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $clinicName = class_exists(\App\Models\AppSetting::class)
                ? \App\Models\AppSetting::getValue('clinic_name', 'DentalFinance')
                : 'DentalFinance';
        @endphp

        <title>{{ $clinicName }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    @php
        $bgPath = class_exists(\App\Models\AppSetting::class)
            ? \App\Models\AppSetting::getValue('app_background', '')
            : '';

        $bgStyle = $bgPath
            ? "background-image:url('".asset('storage/'.$bgPath)."'); background-size:cover; background-repeat:no-repeat; background-attachment:fixed; background-position:center;"
            : "background-color:#f3f4f6;";
    @endphp

    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4" style="{{ $bgStyle }}">
            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white/90 shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>