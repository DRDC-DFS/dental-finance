<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorNoteNotification extends Model
{
    use HasFactory;

    public const STATUS_UNREAD = 'unread';
    public const STATUS_READ = 'read';

    protected $table = 'doctor_note_notifications';

    protected $fillable = [
        'doctor_note_id',
        'income_transaction_id',
        'doctor_id',
        'owner_user_id',
        'status',
    ];

    protected $casts = [
        'doctor_note_id' => 'integer',
        'income_transaction_id' => 'integer',
        'doctor_id' => 'integer',
        'owner_user_id' => 'integer',
    ];

    public function doctorNote()
    {
        return $this->belongsTo(DoctorNote::class, 'doctor_note_id');
    }

    public function transaction()
    {
        return $this->belongsTo(IncomeTransaction::class, 'income_transaction_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function isUnread(): bool
    {
        return (string) $this->status === self::STATUS_UNREAD;
    }

    public function isRead(): bool
    {
        return (string) $this->status === self::STATUS_READ;
    }
}
