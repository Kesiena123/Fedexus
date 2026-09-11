<?php

namespace App\Support;

use App\Models\User;

class AdminSecurity
{
    public const TOKEN_ABILITY = 'admin:access';

    public const ROLES = ['super_admin', 'admin', 'manager'];

    public static function isAdminRole(?string $role): bool
    {
        return in_array($role, self::ROLES, true);
    }

    public static function isAdminUser(?User $user): bool
    {
        return $user instanceof User && self::isAdminRole($user->role);
    }
}
