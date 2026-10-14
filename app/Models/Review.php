<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = ['reservation_id', 'rating', 'comment', 'moderation_reason'];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
