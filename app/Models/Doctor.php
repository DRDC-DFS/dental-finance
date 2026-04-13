<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $table = 'doctors';

    protected $fillable = [
        'name',
        'type',
        'default_fee_percent',
        'is_active',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'doctor_id');
    }

    public function mitraUsers()
    {
        return $this->hasMany(User::class, 'doctor_id')
            ->where('role', User::ROLE_DOKTER_MITRA);
    }

    public function incomeTransactions()
    {
        return $this->hasMany(IncomeTransaction::class, 'doctor_id');
    }

    public function doctorNotes()
    {
        return $this->hasMany(DoctorNote::class, 'doctor_id');
    }
}
