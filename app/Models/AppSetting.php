<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    // Pakai tabel SETTINGS sesuai DATABASE_SCHEMA_FINAL
    protected $table = 'settings';

    // Di schema settings: kolom-kolom ini boleh diisi
    protected $fillable = [
        'clinic_name',
        'clinic_address',
        'clinic_phone',
        'owner_doctor_name',
        'logo_path',
        'login_background_path',
        'favicon_path',
    ];

    /**
     * Mapping key lama (app_settings) -> kolom di settings
     */
    public static function getValue(string $key, $default = null)
    {
        $row = static::query()->first(); // settings = 1 row utama

        if (!$row) return $default;

        return match ($key) {
            'app_background' => $row->login_background_path ?? $default,
            'login_background' => $row->login_background_path ?? $default,
            'favicon' => $row->favicon_path ?? $default,
            'logo' => $row->logo_path ?? $default,
            'clinic_name' => $row->clinic_name ?? $default,
            'clinic_address' => $row->clinic_address ?? $default,
            'clinic_phone' => $row->clinic_phone ?? $default,
            'owner_doctor_name' => $row->owner_doctor_name ?? $default,
            default => $default,
        };
    }
}