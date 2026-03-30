<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherIncome extends Model
{
    use HasFactory;

    protected $table = 'other_incomes';

    protected $fillable = [
        'trx_date',
        'title',
        'source_type',
        'amount',
        'payment_method',
        'bank_name',
        'payment_channel',
        'notes',
        'visibility',
        'include_in_report',
        'include_in_cashflow',
        'created_by',
    ];

    protected $casts = [
        'trx_date' => 'date',
        'amount' => 'decimal:2',
        'include_in_report' => 'boolean',
        'include_in_cashflow' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER ATTRIBUTE (OPSIONAL UNTUK VIEW)
    |--------------------------------------------------------------------------
    */

    public function getPaymentMethodLabelAttribute(): string
    {
        return strtolower((string) $this->payment_method) === 'bank' ? 'BANK' : 'TUNAI';
    }

    public function getPaymentChannelLabelAttribute(): string
    {
        if (strtolower((string) $this->payment_method) !== 'bank') {
            return '-';
        }

        return strtoupper((string) $this->payment_channel);
    }

    public function getSourceLabelAttribute(): string
    {
        return strtoupper((string) $this->source_type);
    }

    public function getBankNameLabelAttribute(): string
    {
        if (strtolower((string) $this->payment_method) !== 'bank') {
            return '-';
        }

        return strtoupper((string) $this->bank_name);
    }

    public function getReportBucketAttribute(): string
    {
        $paymentMethod = strtolower(trim((string) $this->payment_method));
        $bankName = strtoupper(trim((string) $this->bank_name));
        $channel = strtolower(trim((string) $this->payment_channel));

        if ($paymentMethod === 'cash') {
            return 'tunai';
        }

        if ($paymentMethod === 'bank') {
            if ($bankName === 'BCA') {
                if ($channel === 'transfer') {
                    return 'bca_transfer';
                }

                if ($channel === 'edc') {
                    return 'bca_edc';
                }

                if ($channel === 'qris') {
                    return 'bca_qris';
                }
            }

            if ($bankName === 'BNI') {
                if ($channel === 'transfer') {
                    return 'bni_transfer';
                }

                if ($channel === 'edc') {
                    return 'bni_edc';
                }

                if ($channel === 'qris') {
                    return 'bni_qris';
                }
            }

            if ($bankName === 'BRI') {
                if ($channel === 'transfer') {
                    return 'bri_transfer';
                }

                if ($channel === 'edc') {
                    return 'bri_edc';
                }

                if ($channel === 'qris') {
                    return 'bri_qris';
                }
            }
        }

        return 'lainnya';
    }
}