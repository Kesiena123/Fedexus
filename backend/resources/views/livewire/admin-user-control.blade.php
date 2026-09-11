<div class="grid gap-5 xl:grid-cols-[360px_1fr]">
    <x-ui.card>
        <x-ui.card-content>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-navy-900">User Control</h2>
                    <p class="mt-1 text-sm text-slate-600">Create, suspend, verify, reset, and inspect accounts.</p>
                </div>
                <svg class="h-6 w-6 text-navy-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-3 text-sm">
                <div>
                    <p class="text-slate-500">Active</p>
                    <p class="text-xl font-bold text-navy-900">{{ $activeUsers }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Suspended</p>
                    <p class="text-xl font-bold text-navy-900">{{ count($users) - $activeUsers }}</p>
                </div>
            </div>

            @if ($canManageUsers)
                <form class="mt-5 grid gap-3" wire:submit="createUser">
                    <p class="text-sm font-semibold text-navy-800">Create Account</p>
                    <input type="text" wire:model="createName" placeholder="Full name" required class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 placeholder:text-slate-400 transition-colors hover:border-navy-300 focus:border-azure-400" />
                    <input type="email" wire:model="createEmail" placeholder="Email address" required class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 placeholder:text-slate-400 transition-colors hover:border-navy-300 focus:border-azure-400" />
                    <div class="grid gap-3 md:grid-cols-2">
                        <input type="text" wire:model="createPhone" placeholder="Phone" class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 placeholder:text-slate-400 transition-colors hover:border-navy-300 focus:border-azure-400" />
                        <select wire:model="createRole" class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 appearance-none pr-10">
                            @foreach ($availableRoles as $role)
                                <option value="{{ $role }}">{{ str_replace('_', ' ', $role) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="text" wire:model="createCompany" placeholder="Company" class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 placeholder:text-slate-400 transition-colors hover:border-navy-300 focus:border-azure-400" />
                    <input type="password" wire:model="createPassword" placeholder="Optional temporary password" class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 placeholder:text-slate-400 transition-colors hover:border-navy-300 focus:border-azure-400" />
                    <label class="flex items-center gap-2 text-sm text-navy-700">
                        <input type="checkbox" wire:model="createEmailVerified" class="rounded border-navy-300 text-azure-500 focus:ring-azure-400" />
                        Mark email as verified
                    </label>
                    <x-ui.button type="submit" :disabled="$busyAction === 'create-user'">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Create Account
                    </x-ui.button>
                </form>
            @endif

            <div class="mt-5">
                <div class="mb-3 grid gap-2">
                    <input type="text" wire:model.live.debounce="search" placeholder="Search users..." class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 placeholder:text-slate-400 transition-colors hover:border-navy-300 focus:border-azure-400" />
                    <div class="grid grid-cols-2 gap-2">
                        <select wire:model.live="roleFilter" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 appearance-none pr-10">
                            <option value="">All Roles</option>
                            @foreach ($availableRoles as $role)
                                <option value="{{ $role }}">{{ str_replace('_', ' ', ucfirst($role)) }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="statusFilter" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 appearance-none pr-10">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="max-h-[500px] overflow-auto rounded-2xl border border-navy-100">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase text-slate-500">
                            <tr><th class="border-b p-3">User</th><th class="border-b p-3">Role</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr wire:click="selectUser({{ $user['id'] }})" class="cursor-pointer transition-colors hover:bg-slate-50 {{ $selectedUserId === $user['id'] ? 'bg-azure-50' : '' }}">
                                    <td class="border-b border-navy-100 p-3">
                                        <span class="block font-semibold text-navy-900">{{ $user['name'] }}</span>
                                        <span class="block text-xs text-slate-500">{{ $user['email'] }}</span>
                                        @if ($user['is_suspended'])
                                            <span class="mt-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold uppercase text-red-700">Suspended</span>
                                        @endif
                                    </td>
                                    <td class="border-b border-navy-100 p-3 capitalize">{{ str_replace('_', ' ', $user['role']) }}</td>
                                </tr>
                            @empty
                                <tr><td class="p-4 text-center text-slate-500" colspan="2">No users found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-ui.card-content>
    </x-ui.card>

    <x-ui.card>
        <x-ui.card-content>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-xl font-bold text-navy-900">Account Detail</h3>
                    <p class="mt-1 text-sm text-slate-600">Profile control, shipment history, payment history, and audit activity.</p>
                </div>
                <x-ui.button variant="outline" wire:click="loadDetail" :disabled="!$selectedUserId || $loadingDetail">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </x-ui.button>
            </div>

            @if (!$detail && !$loadingDetail)
                <p class="mt-6 text-sm text-slate-500">Select a user to inspect and manage the account.</p>
            @endif
            @if ($loadingDetail)
                <p class="mt-6 text-sm text-slate-500">Loading account detail...</p>
            @endif

            @if ($detail && $editForm)
                @php $selectedUser = $detail['user']; $selectedRoleSupportsAdminOverrides = in_array($editForm['role'], $adminEligibleRoles); @endphp
                <div class="mt-5 grid gap-5">
                    <div class="grid gap-4 rounded-2xl border border-navy-100 p-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-navy-800">Full Name</label>
                            <input type="text" wire:model="editForm.name" {{ $canManageUsers ? '' : 'disabled' }} class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 transition-colors hover:border-navy-300 focus:border-azure-400" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-navy-800">Email</label>
                            <input type="email" wire:model="editForm.email" disabled class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-slate-100 px-4 text-sm text-slate-500 transition-colors" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-navy-800">Phone</label>
                            <input type="text" wire:model="editForm.phone" {{ $canManageUsers ? '' : 'disabled' }} class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 transition-colors hover:border-navy-300 focus:border-azure-400" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-navy-800">Company</label>
                            <input type="text" wire:model="editForm.company" {{ $canManageUsers ? '' : 'disabled' }} class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 transition-colors hover:border-navy-300 focus:border-azure-400" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-navy-800">Role</label>
                            <select wire:model="editForm.role" {{ $canManageUsers ? '' : 'disabled' }} class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 appearance-none pr-10">
                                @foreach ($availableRoles as $role)
                                    <option value="{{ $role }}">{{ str_replace('_', ' ', ucfirst($role)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 text-sm">
                            <p><span class="font-semibold text-navy-900">Verified:</span> {{ $selectedUser['email_verified_at'] ? \Carbon\Carbon::parse($selectedUser['email_verified_at'])->format('M j, Y g:i A') : 'No' }}</p>
                            <p class="mt-2"><span class="font-semibold text-navy-900">Last login:</span> {{ $selectedUser['last_login_at'] ? \Carbon\Carbon::parse($selectedUser['last_login_at'])->format('M j, Y g:i A') : '-' }}</p>
                            <p class="mt-2"><span class="font-semibold text-navy-900">Shipments:</span> {{ $selectedUser['shipments_count'] ?? count($detail['shipment_history']) }}</p>
                        </div>
                    </div>

                    @if ($canManageUsers)
                        <div class="flex flex-wrap gap-2">
                            <x-ui.button variant="outline" wire:click="saveUser" wire:loading.attr="disabled" :disabled="$busyAction === 'save-user'">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Save Changes
                            </x-ui.button>
                            <x-ui.button variant="outline" wire:click="verifyEmail" wire:loading.attr="disabled" :disabled="$busyAction === 'verify-email' || !!$selectedUser['email_verified_at']">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Verify Email
                            </x-ui.button>
                            <x-ui.button variant="outline" wire:click="issuePasswordReset" wire:loading.attr="disabled" :disabled="$busyAction === 'password-reset'">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                Reset Password
                            </x-ui.button>
                            @if ($selectedUser['is_suspended'])
                                <x-ui.button variant="outline" wire:click="reactivateUser" wire:loading.attr="disabled" :disabled="$busyAction === 'reactivate-user'">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    Reactivate
                                </x-ui.button>
                            @else
                                <x-ui.button variant="outline" wire:click="suspendUser" wire:loading.attr="disabled" :disabled="$busyAction === 'suspend-user'">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    Suspend
                                </x-ui.button>
                            @endif
                            <x-ui.button variant="outline" wire:click="deleteUser" wire:loading.attr="disabled" :disabled="$busyAction === 'delete-user'" wire:confirm="Delete this user account? This cannot be undone.">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete
                            </x-ui.button>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-navy-800">Suspension Reason</label>
                            <textarea wire:model="suspensionReason" class="focus-ring min-h-[60px] w-full rounded-xl border border-navy-200 bg-white px-4 py-3 text-sm text-navy-900 transition-colors hover:border-navy-300 focus:border-azure-400 resize-y leading-6"></textarea>
                            <p class="mt-1 text-xs text-slate-500">Required when suspending an account.</p>
                        </div>
                    @endif

                    <section class="rounded-2xl border border-navy-100 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="font-bold text-navy-900">Admin Permission Overrides</h4>
                                <p class="mt-1 text-sm text-slate-600">Apply per-user grants or revocations on top of the role default.</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                                Effective: {{ count($detail['effective_permissions']) }}
                            </div>
                        </div>

                        @if (!$selectedRoleSupportsAdminOverrides)
                            <p class="mt-4 text-sm text-slate-500">Permission overrides only apply to admin-access roles: super admin, admin, and manager.</p>
                        @endif

                        @if ($selectedRoleSupportsAdminOverrides)
                            <div class="mt-4 grid gap-3 md:grid-cols-3">
                                @foreach ($assignablePermissions as $permission)
                                    <div class="rounded-2xl border border-navy-100 p-3 text-sm">
                                        <p class="font-semibold text-navy-900">{{ str_replace('.', ' / ', $permission) }}</p>
                                        <div class="mt-3 space-y-2">
                                            <label class="inline-flex items-center gap-2 text-slate-600">
                                                <input type="checkbox"
                                                    {{ in_array($permission, $editForm['granted_admin_permissions'] ?? []) ? 'checked' : '' }}
                                                    wire:change="toggleGrantedPermission('{{ $permission }}', $event.target.checked)"
                                                    {{ $canManageUsers ? '' : 'disabled' }}
                                                    class="rounded border-navy-300 text-azure-500 focus:ring-azure-400" />
                                                Grant
                                            </label>
                                            <label class="inline-flex items-center gap-2 text-slate-600">
                                                <input type="checkbox"
                                                    {{ in_array($permission, $editForm['revoked_admin_permissions'] ?? []) ? 'checked' : '' }}
                                                    wire:change="toggleRevokedPermission('{{ $permission }}', $event.target.checked)"
                                                    {{ $canManageUsers ? '' : 'disabled' }}
                                                    class="rounded border-navy-300 text-red-400 focus:ring-red-300" />
                                                Revoke
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-4 grid gap-4 md:grid-cols-3">
                            <div class="rounded-2xl bg-slate-50 p-3 text-sm">
                                <p class="font-semibold text-navy-900">Role Default</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @forelse ($detail['role_default_permissions'] as $perm)
                                        <span class="rounded-full bg-white px-2 py-1 text-xs font-semibold text-slate-700">{{ str_replace('.', ' / ', $perm) }}</span>
                                    @empty
                                        <span class="text-slate-500">No role-default permissions.</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="rounded-2xl bg-green-50 p-3 text-sm">
                                <p class="font-semibold text-green-800">Granted Overrides</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @forelse (($editForm['granted_admin_permissions'] ?? []) as $perm)
                                        <span class="rounded-full bg-white px-2 py-1 text-xs font-semibold text-green-800">{{ str_replace('.', ' / ', $perm) }}</span>
                                    @empty
                                        <span class="text-green-700">None</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="rounded-2xl bg-amber-50 p-3 text-sm">
                                <p class="font-semibold text-amber-800">Revoked Overrides</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @forelse (($editForm['revoked_admin_permissions'] ?? []) as $perm)
                                        <span class="rounded-full bg-white px-2 py-1 text-xs font-semibold text-amber-800">{{ str_replace('.', ' / ', $perm) }}</span>
                                    @empty
                                        <span class="text-amber-700">None</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl bg-blue-50 p-3 text-sm">
                            <p class="font-semibold text-blue-900">Effective Access</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse ($detail['effective_permissions'] as $perm)
                                    <span class="rounded-full bg-white px-2 py-1 text-xs font-semibold text-blue-900">{{ str_replace('.', ' / ', $perm) }}</span>
                                @empty
                                    <span class="text-blue-800">No effective permissions.</span>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    <div class="grid gap-5 xl:grid-cols-3">
                        <section>
                            <h4 class="font-bold text-navy-900">Shipment History</h4>
                            <div class="mt-3 max-h-[300px] space-y-3 overflow-auto">
                                @forelse ($detail['shipment_history'] as $shipment)
                                    <div class="rounded-2xl border border-navy-100 p-3 text-sm">
                                        <p class="font-semibold text-navy-900">{{ $shipment['tracking_number'] ?? 'REQ-'.$shipment['id'] }}</p>
                                        <p class="mt-1 capitalize text-slate-600">{{ str_replace('_', ' ', $shipment['status']) }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">No recent shipments.</p>
                                @endforelse
                            </div>
                        </section>

                        <section>
                            <h4 class="font-bold text-navy-900">Payment History</h4>
                            <div class="mt-3 max-h-[300px] space-y-3 overflow-auto">
                                @forelse ($detail['payment_history'] as $payment)
                                    <div class="rounded-2xl border border-navy-100 p-3 text-sm">
                                        <p class="font-semibold text-navy-900">{{ $payment['shipment']['tracking_number'] ?? 'Shipment '.$payment['shipment_id'] }}</p>
                                        <p class="mt-1 text-slate-600">Stage {{ $payment['stage'] }} · {{ str_replace('_', ' ', $payment['status']) }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">No recent payments.</p>
                                @endforelse
                            </div>
                        </section>

                        <section>
                            <h4 class="font-bold text-navy-900">Activity Logs</h4>
                            <div class="mt-3 max-h-[300px] space-y-3 overflow-auto">
                                @forelse ($detail['activity_logs'] as $log)
                                    <div class="rounded-2xl border border-navy-100 p-3 text-sm">
                                        <p class="font-semibold text-navy-900">{{ $log['action'] }}</p>
                                        <p class="mt-1 text-slate-600">{{ $log['admin']['name'] ?? 'System' }} · {{ \Carbon\Carbon::parse($log['created_at'])->format('M j, Y g:i A') }}</p>
                                        @if ($log['reason'])
                                            <p class="mt-2 text-slate-600">{{ $log['reason'] }}</p>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">No recent activity logs.</p>
                                @endforelse
                            </div>
                        </section>
                    </div>
                </div>
            @endif
        </x-ui.card-content>
    </x-ui.card>
</div>
