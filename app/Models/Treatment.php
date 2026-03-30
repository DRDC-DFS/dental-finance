<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    use HasFactory;

    protected $table = 'treatments';

    protected $fillable = [
        'category_id',
        'name',
        'price',
        'price_mode',
        'allow_zero_price',
        'is_free',
        'unit',
        'notes_hint',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'allow_zero_price' => 'boolean',
        'is_free' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(TreatmentCategory::class, 'category_id');
    }

    public function getIsManualPriceAttribute(): bool
    {
        return strtolower((string) ($this->price_mode ?? 'fixed')) === 'manual';
    }

    public function getPriceModeLabelAttribute(): string
    {
        return $this->is_manual_price ? 'Manual' : 'Tetap';
    }

    public function getAllowsZeroPriceAttribute(): bool
    {
        return (bool) ($this->allow_zero_price ?? false);
    }

    public function getIsFreeTreatmentAttribute(): bool
    {
        return (bool) ($this->is_free ?? false);
    }
}