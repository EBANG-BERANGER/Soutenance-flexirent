<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'partner_type', 'company_name', 'tin', 'national_id_number', 'documents_path', 'status', 'is_suspended',
    ];

    public function isIndividual(): bool
    {
        return $this->partner_type === 'individual';
    }

    /**
     * Safe-to-show label for public pages: never a personal name, never
     * blank for an individual owner without a company_name.
     */
    public function publicLabel(): string
    {
        if ($this->isIndividual()) {
            return 'Independent owner';
        }

        return $this->company_name ?: 'FlexiRent partner';
    }

    /**
     * Label for admin-facing screens, where showing the real owner name is
     * fine (unlike publicLabel()): company name if there is one, otherwise
     * the account holder's own name.
     */
    public function adminLabel(): string
    {
        return $this->company_name ?: $this->user->name . ' (individual)';
    }

    /**
     * Lifetime total this partner has been paid out (all 'confirmed'/
     * 'completed' reservations across every vehicle they've ever listed,
     * regardless of period). Same query Reservation::financialHistoryQuery()
     * that backs the admin/partner financial history pages, just with no
     * filters applied, so this number and an unfiltered history page never
     * disagree.
     */
    public function totalReceived(): float
    {
        return (float) Reservation::financialHistoryQuery($this->vehicles()->pluck('id'))->sum('owner_payout');
    }

    protected $casts = [
        'is_suspended' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function isValidated(): bool
    {
        return $this->status === 'validated' && ! $this->is_suspended;
    }
}
