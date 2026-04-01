<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');

            // 🔥 AUTO FIX STORAGE UNTUK RAILWAY
            $target = public_path('storage');

            if (!File::exists($target)) {
                try {
                    Artisan::call('storage:link');
                } catch (\Throwable $e) {
                    // biar tidak crash kalau gagal
                }
            }
        }
    }
}