<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $table = 'inventory_movements';

    protected $fillable = [
        'item_id',
        'type',        // IN | OUT | ADJUSTMENT
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
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}