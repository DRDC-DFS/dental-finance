<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'clinic_name',
        'clinic_address',
        'clinic_phone',
        'owner_doctor_name',
        'logo_path',
        'login_background_path',
        'favicon_path',
    ];

    protected $appends = [
        'logo_url',
        'login_background_url',
        'favicon_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->buildAssetUrl($this->logo_path);
    }

    public function getLoginBackgroundUrlAttribute(): ?string
    {
        return $this->buildAssetUrl($this->login_background_path, asset('assets/login-bg.jpg'));
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->buildAssetUrl($this->favicon_path);
    }

    protected function buildAssetUrl(?string $path, ?string $fallback = null): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return $fallback;
        }

        // Kalau sudah URL penuh, pakai apa adanya.
        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        // Kandidat path yang mungkin tersimpan di database / file system.
        $publicCandidate = public_path($normalized);
        if (File::exists($publicCandidate)) {
            return asset($normalized);
        }

        $storageCandidate = public_path('storage/' . $normalized);
        if (File::exists($storageCandidate)) {
            return asset('storage/' . $normalized);
        }

        // Kalau path sudah diawali "storage/" atau "public/", jangan dobel.
        if (str_starts_with($normalized, 'storage/')) {
            return asset($normalized);
        }

        if (str_starts_with($normalized, 'public/')) {
            return asset($normalized);
        }

        // Default lama Laravel untuk file public disk.
        return asset('storage/' . $normalized);
    }
}