<?php

namespace App\Support;

use App\Models\User;

class AdminPermissions
{
    public const DASHBOARD_VIEW = 'dashboard.view';

    public const USERS_VIEW = 'users.view';

    public const USERS_MANAGE = 'users.manage';

    public const SHIPMENTS_VIEW = 'shipments.view';

    public const SHIPMENTS_CREATE = 'shipments.create';

    public const SHIPMENTS_APPROVE = 'shipments.approve';

    public const SHIPMENTS_UPDATE = 'shipments.update';

    public const SHIPMENTS_DELETE = 'shipments.delete';

    public const SHIPMENTS_ATTACHMENTS_MANAGE = 'shipments.attachments.manage';

    public const PAYMENTS_MANAGE = 'payments.manage';

    public const AUDIT_VIEW = 'audit.view';

    public static function all(): array
    {
        return [
            self::DASHBOARD_VIEW,
            self::USERS_VIEW,
            self::USERS_MANAGE,
            self::SHIPMENTS_VIEW,
            self::SHIPMENTS_CREATE,
            self::SHIPMENTS_APPROVE,
            self::SHIPMENTS_UPDATE,
            self::SHIPMENTS_DELETE,
            self::SHIPMENTS_ATTACHMENTS_MANAGE,
            self::PAYMENTS_MANAGE,
            self::AUDIT_VIEW,
        ];
    }

    public static function defaultsForRole(?string $role): array
    {
        return match ($role) {
            'super_admin', 'admin' => self::all(),
            'manager' => [
                self::DASHBOARD_VIEW,
                self::USERS_VIEW,
                self::SHIPMENTS_VIEW,
                self::SHIPMENTS_CREATE,
                self::SHIPMENTS_APPROVE,
                self::SHIPMENTS_UPDATE,
                self::SHIPMENTS_ATTACHMENTS_MANAGE,
                self::PAYMENTS_MANAGE,
                self::AUDIT_VIEW,
            ],
            default => [],
        };
    }

    public static function forUser(User $user): array
    {
        if (! AdminSecurity::isAdminRole($user->role)) {
            return [];
        }

        $granted = self::sanitize($user->granted_admin_permissions ?? []);
        $revoked = self::sanitize($user->revoked_admin_permissions ?? []);

        return array_values(array_diff(
            array_values(array_unique([...self::defaultsForRole($user->role), ...$granted])),
            $revoked
        ));
    }

    public static function assignableBy(User $user): array
    {
        return match ($user->role) {
            'super_admin' => self::all(),
            'admin' => array_values(array_diff(self::all(), [self::USERS_MANAGE])),
            default => [],
        };
    }

    public static function highRisk(): array
    {
        return [
            self::USERS_MANAGE,
        ];
    }

    public static function hasHighRiskAccess(User $user): bool
    {
        return count(array_intersect(self::forUser($user), self::highRisk())) > 0;
    }

    public static function sanitize(array $permissions): array
    {
        return array_values(array_intersect(self::all(), array_values(array_unique($permissions))));
    }

    public static function can(User $user, string $permission): bool
    {
        return in_array($permission, self::forUser($user), true);
    }

    public static function authorize(User $user, string $permission, string $message = 'You do not have permission to perform this action.'): void
    {
        abort_unless(self::can($user, $permission), 403, $message);
    }
}
