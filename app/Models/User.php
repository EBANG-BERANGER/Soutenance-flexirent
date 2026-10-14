<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role', 'phone', 'address', 'is_suspended', 'profile_photo_path', 'banned_at',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_suspended' => 'boolean',
        'banned_at' => 'datetime',
        'reliability_score' => 'integer',
    ];

    public function partner()
    {
        return $this->hasOne(Partner::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'client_id');
    }

    public function reliabilityEvents()
    {
        return $this->hasMany(ReliabilityEvent::class);
    }

    /**
     * Below this, PaymentController blocks the "deposit only" option:
     * repeated late cancellations / unpaid expiries mean the client only
     * gets full-payment bookings going forward.
     */
    public function isRestrictedToFullPayment(): bool
    {
        return $this->reliability_score <= -30;
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPartner(): bool
    {
        return $this->role === 'partner';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo_path) {
            return asset('storage/'.$this->profile_photo_path);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->username ?? $this->name).'&background=0b1f3a&color=d4af37';
    }
}
