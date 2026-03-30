<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerFinanceCase extends Model
{
    use HasFactory;

    protected $table = 'owner_finance_cases';

    protected $fillable = [
        'income_transaction_id',
        'case_type',
        'lab_paid',
        'installed',
        'prostho_case_type',
        'prostho_case_detail',
        'lab_bill_amount',
        'clinic_income_amount',
        'revenue_recognized_at',
        'owner_private_notes',
        'ortho_allocation_amount',
        'ortho_payment_mode',
        'ortho_installment_count',
        'ortho_paid_amount',
        'ortho_remaining_balance',

        'needs_setup',
        'owner_followup_status',
        'case_progress_status',
        'owner_last_action_note',
        'owner_last_action_at',

        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'lab_paid' => 'boolean',
        'installed' => 'boolean',
        'needs_setup' => 'boolean',
        'is_active' => 'boolean',

        'lab_bill_amount' => 'decimal:2',
        'clinic_income_amount' => 'decimal:2',
        'ortho_allocation_amount' => 'decimal:2',
        'ortho_paid_amount' => 'decimal:2',
        'ortho_remaining_balance' => 'decimal:2',

        'revenue_recognized_at' => 'datetime',
        'owner_last_action_at' => 'datetime',
    ];

    public function incomeTransaction()
    {
        return $this->belongsTo(IncomeTransaction::class, 'income_transaction_id');
    }

    public function installments()
    {
        return $this->hasMany(OwnerFinanceInstallment::class, 'owner_finance_case_id')
            ->orderBy('installment_date')
            ->orderBy('id');
    }

    public function monthlyLedgers()
    {
        return $this->hasMany(OwnerFinanceMonthlyLedger::class, 'owner_finance_case_id')
            ->orderBy('ledger_month')
            ->orderBy('id');
    }

    public function ownerAccountMutations()
    {
        return $this->hasMany(OwnerAccountMutation::class, 'owner_finance_case_id')
            ->orderBy('mutation_date')
            ->orderBy('id');
    }

    public function getCaseTypeLabelAttribute(): string
    {
        return match ((string) $this->case_type) {
            'prostodonti' => 'Prostodonti',
            'ortho' => 'Ortho',
            'retainer' => 'Retainer',
            'lab' => 'Dental Laboratory',
            default => ucfirst((string) $this->case_type),
        };
    }

    public function getCarryForwardActiveAttribute(): bool
    {
        return !($this->lab_paid && $this->installed);
    }

    public function getProsthoCaseTypeLabelAttribute(): string
    {
        return match (strtolower((string) $this->prostho_case_type)) {
            'lepasan' => 'Gigi Tiruan Lepasan',
            'bridge' => 'Bridge',
            'implant', 'implan' => 'Implan',
            'retainer' => 'Retainer',
            'lab' => 'Dental Laboratory',
            default => $this->prostho_case_type
                ? ucfirst((string) $this->prostho_case_type)
                : '-',
        };
    }

    public function getOwnerStatusLabelAttribute(): string
    {
        $followupStatus = strtolower((string) ($this->owner_followup_status ?? ''));

        if ($followupStatus === 'done') {
            return 'Selesai';
        }

        if ($followupStatus === 'in_progress') {
            return 'Berjalan';
        }

        if ($followupStatus === 'followed_up') {
            return 'Sudah Ditindaklanjuti';
        }

        if ((bool) $this->needs_setup) {
            return 'Butuh Dilengkapi';
        }

        if ($this->lab_paid && $this->installed) {
            return 'Selesai';
        }

        if ($this->lab_paid && !$this->installed) {
            return 'Dental Laboratory sudah dibayar, belum terpasang';
        }

        if (!$this->lab_paid && $this->installed) {
            return 'Sudah terpasang, Dental Laboratory belum dibayar';
        }

        if ($this->case_type === 'ortho' && (float) $this->ortho_paid_amount > 0) {
            return 'Berjalan';
        }

        return 'Pending';
    }

    public function getPosisiSaatIniAttribute(): string
    {
        $progress = strtolower((string) ($this->case_progress_status ?? ''));

        if ($progress !== '') {
            return match ($progress) {
                'waiting_owner_setup' => 'Menunggu data owner',
                'waiting_setup' => 'Menunggu setup alokasi dana',
                'waiting_lab_payment' => 'Menunggu pembayaran Dental Laboratory',
                'lab_paid_not_installed' => 'Dental Laboratory sudah dibayar, belum terpasang',
                'installed_lab_not_paid' => 'Sudah terpasang, Dental Laboratory belum dibayar',
                'installment_running' => 'Cicilan berjalan',
                'remaining_balance' => 'Sisa dana masih ada',
                'done' => 'Selesai',
                default => ucfirst(str_replace('_', ' ', $progress)),
            };
        }

        if ((bool) $this->needs_setup) {
            return $this->case_type === 'ortho'
                ? 'Menunggu setup alokasi dana'
                : 'Menunggu data owner';
        }

        if ($this->lab_paid && $this->installed) {
            return 'Selesai';
        }

        if ($this->lab_paid && !$this->installed) {
            return 'Dental Laboratory sudah dibayar, belum terpasang';
        }

        if (!$this->lab_paid && $this->installed) {
            return 'Sudah terpasang, Dental Laboratory belum dibayar';
        }

        if ($this->case_type === 'ortho' && (float) $this->ortho_remaining_balance > 0) {
            return 'Sisa dana masih ada';
        }

        return 'Menunggu pembayaran Dental Laboratory';
    }
}