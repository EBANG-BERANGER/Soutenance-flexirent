<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id', 'action', 'subject_type', 'subject_id', 'description', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public static function record(string $action, Model $subject, string $description): void
    {
        static::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'subject_type' => class_basename($subject),
            'subject_id' => $subject->getKey(),
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
