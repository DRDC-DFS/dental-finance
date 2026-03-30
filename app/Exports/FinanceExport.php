<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;

class FinanceExport implements FromCollection
{
    public function collection()
    {
        return Expense::select(
            'expense_date',
            'description',
            'amount',
            'pay_method'
        )->get();
    }
}