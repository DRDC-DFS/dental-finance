<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentCategory extends Model
{
    protected $table = 'treatment_categories';

    protected $fillable = [
        'name',
        'is_active'
    ];
}