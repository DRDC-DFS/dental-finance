<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseMovement extends Model
{
    use HasFactory;

    protected $table = 'warehouse_movements';

    protected $fillable = [
        'item_id',
        'type',
        'qty',
        'date',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(WarehouseItem::class, 'item_id');
    }
}