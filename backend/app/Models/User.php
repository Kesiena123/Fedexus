<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'role',
        'password',
        'email_verification_code',
        'password_reset_token',
        'password_reset_expires_at',
        'two_factor_enabled',
        'two_factor_code_hash',
        'two_factor_expires_at',
        'last_login_at',
        'is_suspended',
        'suspended_at',
        'suspension_reason',
        'granted_admin_permissions',
        'revoked_admin_permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code',
        'password_reset_token',
        'two_factor_code_hash',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_reset_expires_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'two_factor_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime',
        'granted_admin_permissions' => 'array',
        'revoked_admin_permissions' => 'array',
        'password' => 'hashed',
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(InAppNotification::class);
    }

    public function uploadedAttachments(): HasMany
    {
        return $this->hasMany(ShipmentAttachment::class, 'uploaded_by');
    }

    public function adminAuditLogs(): HasMany
    {
        return $this->hasMany(AdminAuditLog::class, 'admin_user_id');
    }

    public function notificationPreference()
    {
        return $this->hasOne(NotificationPreference::class);
    }
}
