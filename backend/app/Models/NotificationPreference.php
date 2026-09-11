<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'in_app',
        'sms',
        'marketing',
    ];

    protected function casts(): array
    {
        return [
            'email' => 'boolean',
            'in_app' => 'boolean',
            'sms' => 'boolean',
            'marketing' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
