<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'channel', 'title', 'content', 'read_status',
    ];

    protected $casts = [
        'read_status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
