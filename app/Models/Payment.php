<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'transaction_id',
        'payment_method_id',
        'amount',
        'pay_date',
    ];

    protected $casts = [
        'pay_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(IncomeTransaction::class, 'transaction_id');
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}