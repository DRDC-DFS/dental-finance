<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}