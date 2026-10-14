<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReliabilityEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'reservation_id', 'points', 'reason', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
