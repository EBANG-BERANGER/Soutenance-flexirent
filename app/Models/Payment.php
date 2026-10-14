<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id', 'method', 'type', 'amount', 'transaction_ref', 'ussd_code', 'status',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
