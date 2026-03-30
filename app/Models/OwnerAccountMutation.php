<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerAccountMutation extends Model
{
    use HasFactory;

    protected $table = 'owner_account_mutations';

    protected $fillable = [
        'owner_finance_case_id',
        'owner_finance_monthly_ledger_id',
        'mutation_date',
        'mutation_type',
        'source_type',
        'description',
        'amount',
        'reference_month',
        'is_system_generated',
    ];

    protected $casts = [
        'mutation_date' => 'date',
        'reference_month' => 'date',
        'amount' => 'decimal:2',
        'is_system_generated' => 'boolean',
    ];

    public function ownerFinanceCase()
    {
        return $this->belongsTo(OwnerFinanceCase::class, 'owner_finance_case_id');
    }

    public function ownerFinanceMonthlyLedger()
    {
        return $this->belongsTo(OwnerFinanceMonthlyLedger::class, 'owner_finance_monthly_ledger_id');
    }
}