<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseItem extends Model
{
    use HasFactory;

    protected $table = 'warehouse_items';

    protected $fillable = [
        'name',
        'unit',
        'minimum_stock',
        'is_active',
    ];

    protected $casts = [
        'minimum_stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function movements()
    {
        return $this->hasMany(WarehouseMovement::class, 'item_id');
    }
}