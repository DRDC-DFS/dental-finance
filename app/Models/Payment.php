<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'transaction_id',
        'amount',
        'method',
        'notes',
    ];

    // Relasi ke transaksi
    public function transaction()
    {
        return $this->belongsTo(IncomeTransaction::class, 'transaction_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ⚠️ NONAKTIFKAN SEMENTARA
    |--------------------------------------------------------------------------
    | Model PaymentMethod TIDAK ADA di sistem saat ini
    | Jika dibiarkan → error seperti yang Anda alami
    |--------------------------------------------------------------------------
    */

    // public function paymentMethod()
    // {
    //     return $this->belongsTo(PaymentMethod::class);
    // }
}