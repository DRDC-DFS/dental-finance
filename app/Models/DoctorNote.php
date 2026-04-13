<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorNote extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DONE = 'done';
    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'doctor_notes';

    protected $fillable = [
        'income_transaction_id',
        'doctor_id',
        'note',
        'status',
    ];

    protected $casts = [
        'income_transaction_id' => 'integer',
        'doctor_id' => 'integer',
    ];

    public function transaction()
    {
        return $this->belongsTo(IncomeTransaction::class, 'income_transaction_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function notifications()
    {
        return $this->hasMany(DoctorNoteNotification::class, 'doctor_note_id')
            ->latest('id');
    }

    public function isActive(): bool
    {
        return (string) $this->status === self::STATUS_ACTIVE;
    }

    public function isDone(): bool
    {
        return (string) $this->status === self::STATUS_DONE;
    }

    public function isArchived(): bool
    {
        return (string) $this->status === self::STATUS_ARCHIVED;
    }
}
