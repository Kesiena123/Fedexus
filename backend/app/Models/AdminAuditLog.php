<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    protected $fillable = [
        'admin_user_id',
        'action',
        'target_type',
        'target_id',
        'ip_address',
        'user_agent',
        'device_name',
        'device_fingerprint',
        'reason',
        'previous_values',
        'new_values',
        'metadata',
    ];

    protected $casts = [
        'previous_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
