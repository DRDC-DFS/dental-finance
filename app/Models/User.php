<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'photo_path',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Appended attributes.
     *
     * @var list<string>
     */
    protected $appends = [
        'photo_url',
        'role_label',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function getPhotoUrlAttribute(): string
    {
        if (!empty($this->photo_path)) {
            return asset('storage/' . ltrim((string) $this->photo_path, '/'));
        }

        $name = trim((string) ($this->name ?? 'User'));
        $initials = collect(preg_split('/\s+/', $name) ?: [])
            ->filter()
            ->take(2)
            ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        if ($initials === '') {
            $initials = 'U';
        }

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">
    <rect width="120" height="120" rx="60" fill="#0ea5e9"/>
    <text x="50%" y="50%" text-anchor="middle" dy=".35em"
          font-family="Arial, Helvetica, sans-serif"
          font-size="42" font-weight="700" fill="#ffffff">{$initials}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function getRoleLabelAttribute(): string
    {
        return match (strtolower((string) ($this->role ?? ''))) {
            'owner' => 'OWNER',
            'admin' => 'ADMIN',
            'staff' => 'STAFF',
            default => strtoupper((string) ($this->role ?? 'USER')),
        };
    }
}