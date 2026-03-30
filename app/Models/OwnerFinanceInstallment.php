<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerFinanceInstallment extends Model
{
    use HasFactory;

    protected $table = 'owner_finance_installments';

    protected $fillable = [
        'owner_finance_case_id',
        'installment_no',
        'installment_date',
        'amount',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'installment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function ownerFinanceCase()
    {
        return $this->belongsTo(OwnerFinanceCase::class, 'owner_finance_case_id');
    }
}