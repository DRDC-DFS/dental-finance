<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeTransaction extends Model
{
    use HasFactory;

    protected $table = 'income_transactions';

    protected $fillable = [
        'invoice_number',
        'trx_date',
        'doctor_id',
        'patient_id',
        'payer_type',
        'ortho_case_mode',
        'prosto_case_mode',
        'status',
        'bill_total',
        'doctor_fee_total',
        'pay_total',
        'visibility',
        'notes',
        'created_by',
        'receipt_verify_code',
        'receipt_pdf_path',
    ];

    protected $casts = [
        'trx_date' => 'date',
        'bill_total' => 'decimal:2',
        'doctor_fee_total' => 'decimal:2',
        'pay_total' => 'decimal:2',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function items()
    {
        return $this->hasMany(IncomeTransactionItem::class, 'transaction_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'transaction_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * NEW SAFE STRUCTURE:
     * 1 transaksi bisa memiliki banyak owner finance case,
     * dipisah per case_type.
     */
    public function ownerFinanceCases()
    {
        return $this->hasMany(OwnerFinanceCase::class, 'income_transaction_id')
            ->orderBy('id');
    }

    /**
     * LEGACY COMPAT:
     * Pertahankan nama lama agar kode lama yang masih memanggil
     * ownerFinanceCase tidak langsung rusak.
     * Akan mengambil case pertama saja.
     */
    public function ownerFinanceCase()
    {
        return $this->hasOne(OwnerFinanceCase::class, 'income_transaction_id')
            ->oldestOfMany();
    }
}