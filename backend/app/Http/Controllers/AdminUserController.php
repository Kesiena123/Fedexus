<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\NotificationDispatchService;
use App\Support\AdminPermissions;
use App\Support\AdminSecurity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::USERS_VIEW);

        $query = User::query()
            ->withCount('shipments')
            ->latest();

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->query('status')) {
            if ($status === 'suspended') {
                $query->where('is_suspended', true);
            }

            if ($status === 'active') {
                $query->where('is_suspended', false);
            }
        }

        return response()->json(['users' => $query->paginate(30)]);
    }

    public function store(Request $request, NotificationDispatchService $notifications, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::USERS_MANAGE);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:140'],
            'role' => ['required', Rule::in($this->manageableRoles($request))],
            'password' => ['nullable', Password::min(10)->mixedCase()->numbers()],
            'email_verified' => ['nullable', 'boolean'],
            'granted_admin_permissions' => ['nullable', 'array'],
            'granted_admin_permissions.*' => [Rule::in($this->assignablePermissions($request))],
            'revoked_admin_permissions' => ['nullable', 'array'],
            'revoked_admin_permissions.*' => [Rule::in($this->assignablePermissions($request))],
        ]);

        $temporaryPassword = $data['password'] ?? Str::random(16).'1aA';
        $permissionOverrides = $this->normalizedPermissionOverrides($data, $data['role']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'role' => $data['role'],
            'password' => $temporaryPassword,
            'email_verified_at' => ($data['email_verified'] ?? false) ? now() : null,
            'granted_admin_permissions' => $permissionOverrides['granted_admin_permissions'],
            'revoked_admin_permissions' => $permissionOverrides['revoked_admin_permissions'],
        ]);

        $notifications->send(
            $user,
            'Your FreightFlow account was created',
            'An administrator created your account. Use the temporary password '.$temporaryPassword.' and reset it after signing in.',
            ['type' => 'account_created'],
            ['in_app', 'email']
        );

        $audit->log($request, $request->user(), 'user.created', [
            'target_type' => 'user',
            'target_id' => $user->id,
            'new_values' => [
                'role' => $user->role,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'granted_admin_permissions' => $user->granted_admin_permissions,
                'revoked_admin_permissions' => $user->revoked_admin_permissions,
            ],
            'reason' => 'Account created from admin control center.',
        ]);

        return response()->json(['user' => $this->userPayload($user->fresh())], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::USERS_VIEW);

        $shipments = Shipment::with(['payments', 'trackingEvents'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        $payments = Payment::with('shipment')
            ->whereHas('shipment', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->limit(10)
            ->get();

        $auditLogs = AdminAuditLog::with('admin')
            ->where(function ($query) use ($user): void {
                $query
                    ->where(function ($builder) use ($user): void {
                        $builder->where('target_type', 'user')->where('target_id', (string) $user->id);
                    })
                    ->orWhere('admin_user_id', $user->id);
            })
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'user' => $this->userPayload($user->loadCount('shipments')),
            'shipment_history' => $shipments,
            'payment_history' => $payments,
            'activity_logs' => $auditLogs,
        ]);
    }

    public function update(Request $request, User $user, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::USERS_MANAGE);
        $this->protectPrivilegedTarget($request, $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:140'],
            'role' => ['required', Rule::in($this->manageableRoles($request))],
            'granted_admin_permissions' => ['nullable', 'array'],
            'granted_admin_permissions.*' => [Rule::in($this->assignablePermissions($request))],
            'revoked_admin_permissions' => ['nullable', 'array'],
            'revoked_admin_permissions.*' => [Rule::in($this->assignablePermissions($request))],
        ]);

        $permissionOverrides = $this->normalizedPermissionOverrides($data, $data['role']);
        $previous = $user->only(['name', 'email', 'phone', 'company', 'role', 'granted_admin_permissions', 'revoked_admin_permissions']);
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'role' => $data['role'],
            'granted_admin_permissions' => $permissionOverrides['granted_admin_permissions'],
            'revoked_admin_permissions' => $permissionOverrides['revoked_admin_permissions'],
        ]);

        $audit->log($request, $request->user(), 'user.updated', [
            'target_type' => 'user',
            'target_id' => $user->id,
            'previous_values' => $previous,
            'new_values' => $user->only(['name', 'email', 'phone', 'company', 'role', 'granted_admin_permissions', 'revoked_admin_permissions']),
        ]);

        return response()->json(['user' => $this->userPayload($user->fresh())]);
    }

    public function suspend(Request $request, User $user, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::USERS_MANAGE);
        $this->protectPrivilegedTarget($request, $user);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $user->forceFill([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspension_reason' => $data['reason'],
        ])->save();

        $user->tokens()->delete();

        $audit->log($request, $request->user(), 'user.suspended', [
            'target_type' => 'user',
            'target_id' => $user->id,
            'new_values' => [
                'is_suspended' => true,
                'suspended_at' => $user->suspended_at,
            ],
            'reason' => $data['reason'],
        ]);

        return response()->json(['user' => $this->userPayload($user->fresh())]);
    }

    public function reactivate(Request $request, User $user, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::USERS_MANAGE);
        $this->protectPrivilegedTarget($request, $user);

        $user->forceFill([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();

        $audit->log($request, $request->user(), 'user.reactivated', [
            'target_type' => 'user',
            'target_id' => $user->id,
            'new_values' => [
                'is_suspended' => false,
            ],
        ]);

        return response()->json(['user' => $this->userPayload($user->fresh())]);
    }

    public function verifyEmail(Request $request, User $user, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::USERS_MANAGE);
        $this->protectPrivilegedTarget($request, $user);

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
        ])->save();

        $audit->log($request, $request->user(), 'user.email.verified', [
            'target_type' => 'user',
            'target_id' => $user->id,
            'new_values' => [
                'email_verified_at' => $user->email_verified_at,
            ],
        ]);

        return response()->json(['user' => $this->userPayload($user->fresh())]);
    }

    public function issuePasswordReset(Request $request, User $user, NotificationDispatchService $notifications, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::USERS_MANAGE);
        $this->protectPrivilegedTarget($request, $user);

        $token = Str::upper(Str::random(32));

        $user->forceFill([
            'password_reset_token' => Hash::make($token),
            'password_reset_expires_at' => now()->addMinutes(30),
        ])->save();

        $notifications->send(
            $user,
            'Your FreightFlow password reset token',
            'An administrator requested a password reset for your account. Use token '.$token.' within 30 minutes.',
            ['type' => 'password_reset'],
            ['in_app', 'email']
        );

        $audit->log($request, $request->user(), 'user.password_reset.issued', [
            'target_type' => 'user',
            'target_id' => $user->id,
        ]);

        return response()->json(['message' => 'Password reset token issued and delivered to the user notification channels.']);
    }

    public function destroy(Request $request, User $user, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::USERS_MANAGE);
        $this->protectPrivilegedTarget($request, $user);

        $snapshot = $user->only(['name', 'email', 'role']);
        $userId = $user->id;
        $user->delete();

        $audit->log($request, $request->user(), 'user.deleted', [
            'target_type' => 'user',
            'target_id' => $userId,
            'previous_values' => $snapshot,
        ]);

        return response()->json(['message' => 'User deleted.']);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless(AdminSecurity::isAdminRole($request->user()->role), 403, 'Admin access is required.');
        AdminPermissions::authorize($request->user(), $permission);
    }

    private function manageableRoles(Request $request): array
    {
        return $request->user()->role === 'super_admin'
            ? ['super_admin', 'admin', 'manager', 'support', 'warehouse', 'driver', 'customer']
            : ['manager', 'support', 'warehouse', 'driver', 'customer'];
    }

    private function assignablePermissions(Request $request): array
    {
        return AdminPermissions::assignableBy($request->user());
    }

    private function normalizedPermissionOverrides(array $data, string $role): array
    {
        if (! AdminSecurity::isAdminRole($role)) {
            return [
                'granted_admin_permissions' => [],
                'revoked_admin_permissions' => [],
            ];
        }

        $granted = AdminPermissions::sanitize($data['granted_admin_permissions'] ?? []);
        $revoked = array_values(array_diff(AdminPermissions::sanitize($data['revoked_admin_permissions'] ?? []), $granted));

        return [
            'granted_admin_permissions' => $granted,
            'revoked_admin_permissions' => $revoked,
        ];
    }

    private function userPayload(User $user): array
    {
        $payload = $user->toArray();
        $payload['granted_admin_permissions'] = $user->granted_admin_permissions ?? [];
        $payload['revoked_admin_permissions'] = $user->revoked_admin_permissions ?? [];
        $payload['role_default_admin_permissions'] = AdminPermissions::defaultsForRole($user->role);
        $payload['effective_admin_permissions'] = AdminPermissions::forUser($user);

        return $payload;
    }

    private function protectPrivilegedTarget(Request $request, User $user): void
    {
        abort_if($request->user()->id === $user->id, 422, 'You cannot modify your own account from this control surface.');
        abort_if($user->role === 'super_admin' && $request->user()->role !== 'super_admin', 403, 'Only a super admin can modify another super admin.');
        abort_if(
            $request->user()->role !== 'super_admin' && AdminPermissions::hasHighRiskAccess($user),
            403,
            'Only a super admin can modify an account with high-risk administrative access.'
        );
    }
}
