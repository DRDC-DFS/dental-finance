<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerFinanceMonthlyLedger extends Model
{
    use HasFactory;

    protected $table = 'owner_finance_monthly_ledgers';

    protected $fillable = [
        'owner_finance_case_id',
        'ledger_month',
        'opening_balance',
        'income_amount',
        'installment_paid',
        'expense_end_month',
        'closing_balance',
        'is_closed',
        'notes',
    ];

    protected $casts = [
        'ledger_month' => 'date',
        'opening_balance' => 'decimal:2',
        'income_amount' => 'decimal:2',
        'installment_paid' => 'decimal:2',
        'expense_end_month' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'is_closed' => 'boolean',
    ];

    public function ownerFinanceCase()
    {
        return $this->belongsTo(OwnerFinanceCase::class, 'owner_finance_case_id');
    }
}