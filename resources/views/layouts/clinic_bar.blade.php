@php
    $clinicName = class_exists(\App\Models\AppSetting::class)
        ? \App\Models\AppSetting::getValue('clinic_name', '')
        : '';

    $logoPath = class_exists(\App\Models\AppSetting::class)
        ? \App\Models\AppSetting::getValue('clinic_logo', '')
        : '';

    $mode = class_exists(\App\Models\AppSetting::class)
        ? \App\Models\AppSetting::getValue('rt_mode', 'STATIC')
        : 'STATIC';

    $size = class_exists(\App\Models\AppSetting::class)
        ? \App\Models\AppSetting::getValue('rt_size', 'LG')
        : 'LG';

    $speed = class_exists(\App\Models\AppSetting::class)
        ? \App\Models\AppSetting::getValue('rt_speed', 'SLOW')
        : 'SLOW';

    $color = class_exists(\App\Models\AppSetting::class)
        ? \App\Models\AppSetting::getValue('rt_color', '#D4AF37')
        : '#D4AF37';

    $bg = class_exists(\App\Models\AppSetting::class)
        ? \App\Models\AppSetting::getValue('rt_bg_color', '#1f2937')
        : '#1f2937';

    $textImg = class_exists(\App\Models\AppSetting::class)
        ? \App\Models\AppSetting::getValue('rt_text_image', '')
        : '';

    if (!$clinicName) $clinicName = 'Nama Klinik belum diatur (Owner: Master > Branding)';

    // Tinggi bar dan ukuran konten (agar hampir memenuhi bar)
    $barHeight = match($size) {
        'SM' => 70,
        'MD' => 90,
        'LG' => 120,
        default => 90,
    };

    $contentHeight = (int) round($barHeight * 0.82); // ~82% tinggi bar

    // Speed untuk mode RUNNING (kalau sewaktu-waktu dipakai)
    $duration = match($speed) {
        'SLOW' => '35s',
        'MED'  => '22s',
        'FAST' => '14s',
        default => '35s',
    };
@endphp

<style>
@keyframes scroll-left { 0% {transform:translateX(100%);} 100% {transform:translateX(-100%);} }
.running-wrap { overflow:hidden; white-space:nowrap; width:100%; }
.running-text { display:inline-block; padding-left:100%; animation:scroll-left linear infinite; }
.running-text.static { padding-left:0; animation:none !important; }
</style>

<div style="background-color: {{ $bg }}; border-bottom:1px solid #e5e7eb;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative"
         style="height: {{ $barHeight }}px;">

        {{-- Tombol Master Branding (pojok kanan) --}}
        @if(auth()->check() && auth()->user()->role === 'OWNER' && Route::has('master.branding.edit'))
            <div style="position:absolute; right:16px; top:50%; transform:translateY(-50%); z-index:5;">
                <a href="{{ route('master.branding.edit') }}"
                   class="underline text-sm"
                   style="color: {{ $color }};">
                    Master Branding
                </a>
            </div>
        @endif

        @if($mode === 'STATIC')
            {{-- MODE TETAP: center --}}
            <div class="flex items-center justify-center gap-4 h-full">

                @if($logoPath)
                    <img src="{{ asset('storage/'.$logoPath) }}" alt="Logo"
                         style="height: {{ $contentHeight }}px; width:auto; object-fit:contain;">
                @endif

                @if($textImg)
                    {{-- PNG tulisan: tinggi hampir penuh bar, lebar maksimal 1/3 bar --}}
                    <img src="{{ asset('storage/'.$textImg) }}" alt="Nama Klinik"
                         style="
                            height: {{ $contentHeight }}px;
                            width: auto;
                            max-width: 33%;
                            object-fit: contain;
                            display:block;
                         ">
                @else
                    {{-- fallback kalau PNG belum diupload --}}
                    <div style="
                        color: {{ $color }};
                        font-size: {{ $contentHeight }}px;
                        font-weight: 700;
                        text-align:center;
                        line-height: 1;
                    ">
                        {{ $clinicName }}
                    </div>
                @endif

            </div>
        @else
            {{-- MODE RUNNING --}}
            <div class="flex items-center gap-3 h-full">

                @if($logoPath)
                    <img src="{{ asset('storage/'.$logoPath) }}" alt="Logo"
                         style="height: {{ $contentHeight }}px; width:auto; object-fit:contain;">
                @endif

                <div class="running-wrap">
                    <div class="running-text" style="animation-duration: {{ $duration }};">
                        @if($textImg)
                            <img src="{{ asset('storage/'.$textImg) }}" alt="Nama Klinik"
                                 style="height: {{ $contentHeight }}px; width:auto; object-fit:contain;">
                        @else
                            <span style="color: {{ $color }}; font-size: {{ $contentHeight }}px; font-weight:700;">
                                {{ $clinicName }}
                            </span>
                        @endif
                    </div>
                </div>

            </div>
        @endif

    </div>
</div>