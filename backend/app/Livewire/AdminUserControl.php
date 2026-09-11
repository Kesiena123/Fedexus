<?php

namespace App\Livewire;

use App\Models\AdminAuditLog;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Support\AdminPermissions;
use App\Support\AdminSecurity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('User Control - Admin')]
class AdminUserControl extends Component
{
    public ?int $selectedUserId = null;

    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    public string $createName = '';

    public string $createEmail = '';

    public string $createPhone = '';

    public string $createCompany = '';

    public string $createRole = 'customer';

    public string $createPassword = '';

    public bool $createEmailVerified = false;

    public ?array $editForm = null;

    public string $suspensionReason = 'Administrative hold pending review.';

    #[Locked]
    public ?array $detail = null;

    #[Locked]
    public bool $loadingDetail = false;

    #[Locked]
    public ?string $busyAction = null;

    #[Locked]
    public int $detailRefreshVersion = 0;

    protected AdminAuditLogger $auditLogger;

    public function boot(AdminAuditLogger $auditLogger): void
    {
        $this->auditLogger = $auditLogger;
    }

    public function mount(): void
    {
        $users = $this->users;
        if (! empty($users)) {
            $this->selectedUserId = $users[0]['id'];
            $this->loadDetail();
        }
    }

    #[Computed]
    public function users(): array
    {
        $query = User::query();

        if ($search = trim($this->search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) like ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) like ?', ["%{$search}%"]);
            });
        }

        if ($this->roleFilter) {
            $query->where('role', $this->roleFilter);
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_suspended', false);
        } elseif ($this->statusFilter === 'suspended') {
            $query->where('is_suspended', true);
        }

        return $query->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_suspended', 'last_login_at'])
            ->toArray();
    }

    #[Computed]
    public function activeUsers(): int
    {
        return User::where('is_suspended', false)->count();
    }

    #[Computed]
    public function canManageUsers(): bool
    {
        return Auth::user() && AdminPermissions::can(Auth::user(), AdminPermissions::USERS_MANAGE);
    }

    public function loadDetail(): void
    {
        if (! $this->selectedUserId) {
            $this->detail = null;
            $this->editForm = null;

            return;
        }

        $this->loadingDetail = true;

        $user = User::find($this->selectedUserId);
        if (! $user) {
            $this->detail = null;
            $this->editForm = null;
            $this->loadingDetail = false;

            return;
        }

        $defaultPermissions = AdminPermissions::defaultsForRole($user->role);
        $effectivePermissions = AdminPermissions::forUser($user);

        $this->detail = [
            'user' => $user->toArray(),
            'shipment_history' => Shipment::where('user_id', $user->id)->latest()->limit(20)->get(['id', 'tracking_number', 'status'])->toArray(),
            'payment_history' => Payment::whereHas('shipment', fn ($q) => $q->where('user_id', $user->id))
                ->with('shipment:id,tracking_number')
                ->latest()
                ->limit(20)
                ->get()
                ->toArray(),
            'activity_logs' => AdminAuditLog::where('admin_user_id', $user->id)
                ->with('admin:id,name')
                ->latest()
                ->limit(20)
                ->get()
                ->toArray(),
            'role_default_permissions' => $defaultPermissions,
            'effective_permissions' => $effectivePermissions,
        ];

        $this->editForm = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'company' => $user->company ?? '',
            'role' => $user->role,
            'granted_admin_permissions' => $user->granted_admin_permissions ?? [],
            'revoked_admin_permissions' => $user->revoked_admin_permissions ?? [],
        ];

        $this->loadingDetail = false;
    }

    public function updatedSelectedUserId(): void
    {
        $this->loadDetail();
    }

    public function selectUser(int $id): void
    {
        $this->selectedUserId = $id;
        $this->loadDetail();
    }

    public function createUser(): void
    {
        if (! $this->canManageUsers) {
            return;
        }

        $this->busyAction = 'create-user';

        try {
            $this->validate([
                'createName' => 'required|string|max:255',
                'createEmail' => 'required|email|max:255|unique:users,email',
                'createPhone' => 'nullable|string|max:50',
                'createCompany' => 'nullable|string|max:255',
                'createRole' => 'required|string|in:customer,driver,warehouse,support,manager,admin,super_admin',
                'createPassword' => 'nullable|string|min:8',
            ]);

            $userData = [
                'name' => $this->createName,
                'email' => $this->createEmail,
                'phone' => $this->createPhone ?: null,
                'company' => $this->createCompany ?: null,
                'role' => $this->createRole,
                'password' => $this->createPassword ? Hash::make($this->createPassword) : Hash::make(bin2hex(random_bytes(4))),
            ];

            if ($this->createEmailVerified) {
                $userData['email_verified_at'] = now();
            }

            $user = User::create($userData);

            $this->auditLogger->log(
                request(),
                Auth::user(),
                'user.created',
                [
                    'target_type' => 'user',
                    'target_id' => $user->id,
                    'new_values' => ['name' => $user->name, 'email' => $user->email, 'role' => $user->role],
                ]
            );

            $this->resetCreateForm();
            $this->selectedUserId = $user->id;
            $this->loadDetail();

            $this->dispatch('userMutated', message: 'User account created.', selectedUserId: $user->id);
        } finally {
            $this->busyAction = null;
        }
    }

    protected function resetCreateForm(): void
    {
        $this->createName = '';
        $this->createEmail = '';
        $this->createPhone = '';
        $this->createCompany = '';
        $this->createRole = 'customer';
        $this->createPassword = '';
        $this->createEmailVerified = false;
    }

    public function saveUser(): void
    {
        if (! $this->canManageUsers || ! $this->selectedUserId || ! $this->editForm) {
            return;
        }

        $this->busyAction = 'save-user';

        try {
            $user = User::findOrFail($this->selectedUserId);

            $this->validate([
                'editForm.name' => 'required|string|max:255',
                'editForm.phone' => 'nullable|string|max:50',
                'editForm.company' => 'nullable|string|max:255',
                'editForm.role' => 'required|string|in:customer,driver,warehouse,support,manager,admin,super_admin',
            ]);

            $adminEligible = AdminSecurity::isAdminRole($this->editForm['role']);

            $user->update([
                'name' => $this->editForm['name'],
                'phone' => $this->editForm['phone'] ?: null,
                'company' => $this->editForm['company'] ?: null,
                'role' => $this->editForm['role'],
                'granted_admin_permissions' => $adminEligible ? $this->editForm['granted_admin_permissions'] : [],
                'revoked_admin_permissions' => $adminEligible ? $this->editForm['revoked_admin_permissions'] : [],
            ]);

            $this->auditLogger->log(
                request(),
                Auth::user(),
                'user.updated',
                [
                    'target_type' => 'user',
                    'target_id' => $user->id,
                    'new_values' => ['name' => $user->name, 'role' => $user->role],
                ]
            );

            $this->loadDetail();
            $this->dispatch('userMutated', message: 'User profile updated.', selectedUserId: $user->id);
        } finally {
            $this->busyAction = null;
        }
    }

    public function suspendUser(): void
    {
        if (! $this->canManageUsers || ! $this->selectedUserId) {
            return;
        }

        $this->busyAction = 'suspend-user';

        try {
            $user = User::findOrFail($this->selectedUserId);
            $user->update([
                'is_suspended' => true,
                'suspended_at' => now(),
                'suspension_reason' => $this->suspensionReason,
            ]);

            $this->auditLogger->log(
                request(),
                Auth::user(),
                'user.suspended',
                [
                    'target_type' => 'user',
                    'target_id' => $user->id,
                    'reason' => $this->suspensionReason,
                ]
            );

            $this->loadDetail();
            $this->dispatch('userMutated', message: 'User account suspended.', selectedUserId: $user->id);
        } finally {
            $this->busyAction = null;
        }
    }

    public function reactivateUser(): void
    {
        if (! $this->canManageUsers || ! $this->selectedUserId) {
            return;
        }

        $this->busyAction = 'reactivate-user';

        try {
            $user = User::findOrFail($this->selectedUserId);
            $user->update([
                'is_suspended' => false,
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);

            $this->auditLogger->log(
                request(),
                Auth::user(),
                'user.reactivated',
                [
                    'target_type' => 'user',
                    'target_id' => $user->id,
                ]
            );

            $this->loadDetail();
            $this->dispatch('userMutated', message: 'User account reactivated.', selectedUserId: $user->id);
        } finally {
            $this->busyAction = null;
        }
    }

    public function deleteUser(): void
    {
        if (! $this->canManageUsers || ! $this->selectedUserId) {
            return;
        }

        $this->busyAction = 'delete-user';

        try {
            $user = User::findOrFail($this->selectedUserId);

            $this->auditLogger->log(
                request(),
                Auth::user(),
                'user.deleted',
                [
                    'target_type' => 'user',
                    'target_id' => $user->id,
                    'new_values' => ['name' => $user->name, 'email' => $user->email],
                ]
            );

            $user->delete();

            $remaining = User::first();
            $this->selectedUserId = $remaining?->id;
            $this->loadDetail();

            $this->dispatch('userMutated', message: 'User account deleted.');
        } finally {
            $this->busyAction = null;
        }
    }

    public function verifyEmail(): void
    {
        if (! $this->canManageUsers || ! $this->selectedUserId) {
            return;
        }

        $this->busyAction = 'verify-email';

        try {
            $user = User::findOrFail($this->selectedUserId);
            $user->update(['email_verified_at' => now()]);

            $this->auditLogger->log(
                request(),
                Auth::user(),
                'user.email.verified',
                [
                    'target_type' => 'user',
                    'target_id' => $user->id,
                ]
            );

            $this->loadDetail();
            $this->dispatch('userMutated', message: 'Email address marked as verified.', selectedUserId: $user->id);
        } finally {
            $this->busyAction = null;
        }
    }

    public function issuePasswordReset(): void
    {
        if (! $this->canManageUsers || ! $this->selectedUserId) {
            return;
        }

        $this->busyAction = 'password-reset';

        try {
            $user = User::findOrFail($this->selectedUserId);
            $token = bin2hex(random_bytes(32));
            $user->update([
                'password_reset_token' => $token,
                'password_reset_expires_at' => now()->addHours(24),
            ]);

            $this->auditLogger->log(
                request(),
                Auth::user(),
                'user.password.reset.issued',
                [
                    'target_type' => 'user',
                    'target_id' => $user->id,
                ]
            );

            $this->loadDetail();
            $this->dispatch('userMutated', message: 'Password reset token issued.', selectedUserId: $user->id);
        } finally {
            $this->busyAction = null;
        }
    }

    public function toggleGrantedPermission(string $permission, bool $checked): void
    {
        if (! $this->editForm || ! $this->canManageUsers) {
            return;
        }

        $granted = $this->editForm['granted_admin_permissions'];
        $revoked = $this->editForm['revoked_admin_permissions'];

        if ($checked) {
            $granted[] = $permission;
            $revoked = array_values(array_filter($revoked, fn ($p) => $p !== $permission));
        } else {
            $granted = array_values(array_filter($granted, fn ($p) => $p !== $permission));
        }

        $this->editForm['granted_admin_permissions'] = array_values(array_unique($granted));
        $this->editForm['revoked_admin_permissions'] = $revoked;
    }

    public function toggleRevokedPermission(string $permission, bool $checked): void
    {
        if (! $this->editForm || ! $this->canManageUsers) {
            return;
        }

        $granted = $this->editForm['granted_admin_permissions'];
        $revoked = $this->editForm['revoked_admin_permissions'];

        if ($checked) {
            $revoked[] = $permission;
            $granted = array_values(array_filter($granted, fn ($p) => $p !== $permission));
        } else {
            $revoked = array_values(array_filter($revoked, fn ($p) => $p !== $permission));
        }

        $this->editForm['granted_admin_permissions'] = $granted;
        $this->editForm['revoked_admin_permissions'] = array_values(array_unique($revoked));
    }

    public function render()
    {
        return view('livewire.admin-user-control', [
            'users' => $this->users,
            'activeUsers' => $this->activeUsers,
            'canManageUsers' => $this->canManageUsers,
            'availableRoles' => ['customer', 'driver', 'warehouse', 'support', 'manager', 'admin', 'super_admin'],
            'adminPermissionOptions' => AdminPermissions::all(),
            'assignablePermissions' => Auth::user() && AdminSecurity::isAdminRole(Auth::user()->role)
                ? AdminPermissions::assignableBy(Auth::user())
                : [],
            'adminEligibleRoles' => AdminSecurity::ROLES,
        ]);
    }
}
