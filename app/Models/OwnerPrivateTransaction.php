<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerPrivateTransaction extends Model
{
    use HasFactory;

    protected $table = 'owner_private_transactions';

    protected $fillable = [
        'trx_date',
        'type',
        'category',
        'source',
        'description',
        'payment_method',
        'amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'trx_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'income'
            ? 'Pemasukan Private'
            : 'Pengeluaran Private';
    }
}