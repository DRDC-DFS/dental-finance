<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeTransactionItem extends Model
{
    use HasFactory;

    protected $table = 'income_transaction_items';

    protected $fillable = [
        'transaction_id',
        'treatment_id',
        'qty',
        'unit_price',
        'discount_amount',
        'subtotal',
        'fee_amount',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'fee_amount' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(IncomeTransaction::class, 'transaction_id');
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }
}