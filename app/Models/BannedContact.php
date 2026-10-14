<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannedContact extends Model
{
    protected $fillable = ['email', 'phone'];

    /**
     * True if either contact value belongs to a previously banned account,
     * so a banned user cannot simply re-register under a new username.
     */
    public static function matches(?string $email, ?string $phone): bool
    {
        if (! $email && ! $phone) {
            return false;
        }

        return static::query()
            ->where(function ($q) use ($email, $phone) {
                if ($email) {
                    $q->orWhere('email', $email);
                }
                if ($phone) {
                    $q->orWhere('phone', $phone);
                }
            })
            ->exists();
    }
}
