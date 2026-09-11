<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Support\AdminPermissions;
use App\Support\AdminSecurity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    private function adminUserPayload(User $user): array
    {
        $payload = $user->toArray();
        $payload['granted_admin_permissions'] = $user->granted_admin_permissions ?? [];
        $payload['revoked_admin_permissions'] = $user->revoked_admin_permissions ?? [];
        $payload['role_default_admin_permissions'] = AdminPermissions::defaultsForRole($user->role);
        $payload['effective_admin_permissions'] = AdminPermissions::forUser($user);

        return $payload;
    }

    public function session(Request $request, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::DASHBOARD_VIEW);

        $audit->log($request, $request->user(), 'admin.session.validated');

        return response()->json([
            'user' => $this->adminUserPayload($request->user()),
            'session_timeout_minutes' => (int) config('admin.session_timeout_minutes', 30),
            'permissions' => AdminPermissions::forUser($request->user()),
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::DASHBOARD_VIEW);

        return response()->json([
            'permissions' => AdminPermissions::forUser($request->user()),
            'metrics' => [
                'shipments' => Shipment::count(),
                'active_shipments' => Shipment::whereNotIn('status', ['delivered', 'cancelled'])->count(),
                'revenue' => Payment::whereIn('status', ['paid', 'verified'])->sum('amount'),
                'pending_payment_amount' => Payment::where('status', '!=', 'paid')->sum('amount'),
                'open_tickets' => SupportTicket::whereIn('status', ['open', 'pending'])->count(),
                'customers' => User::where('role', 'customer')->count(),
            ],
            'approval_queue' => Shipment::with('user')
                ->whereNull('tracking_number')
                ->whereIn('status', ['shipment_requested', 'admin_review'])
                ->latest()
                ->limit(10)
                ->get(),
            'tracked_shipments' => Shipment::with(['user', 'trackingEvents', 'payments'])
                ->whereNotNull('tracking_number')
                ->latest()
                ->limit(15)
                ->get(),
            'payment_queue' => Payment::with('shipment')
                ->where('status', 'paid')
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::USERS_VIEW);

        return response()->json(['users' => User::latest()->paginate(30)]);
    }

    public function tickets(Request $request): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::DASHBOARD_VIEW);

        return response()->json(['tickets' => SupportTicket::latest()->paginate(30)]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::AUDIT_VIEW);

        $query = $this->auditLogQuery($request);

        return response()->json(['audit_logs' => $query->paginate((int) $request->integer('per_page', 50))]);
    }

    public function exportAuditLogs(Request $request): StreamedResponse
    {
        $this->authorizePermission($request, AdminPermissions::AUDIT_VIEW);

        $fileName = 'admin-audit-logs-'.now()->format('Ymd-His').'.csv';
        $rows = $this->auditLogQuery($request)->limit((int) $request->integer('limit', 500))->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'created_at',
                'action',
                'admin_name',
                'admin_email',
                'admin_role',
                'target_type',
                'target_id',
                'reason',
                'ip_address',
                'device_name',
                'device_fingerprint',
                'previous_values',
                'new_values',
                'metadata',
            ]);

            foreach ($rows as $log) {
                fputcsv($handle, [
                    $log->created_at,
                    $log->action,
                    $log->admin?->name,
                    $log->admin?->email,
                    $log->admin?->role,
                    $log->target_type,
                    $log->target_id,
                    $log->reason,
                    $log->ip_address,
                    $log->device_name,
                    $log->device_fingerprint,
                    $log->previous_values ? json_encode($log->previous_values) : null,
                    $log->new_values ? json_encode($log->new_values) : null,
                    $log->metadata ? json_encode($log->metadata) : null,
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless(AdminSecurity::isAdminRole($request->user()->role), 403, 'Admin access is required.');
        AdminPermissions::authorize($request->user(), $permission);
    }

    private function auditLogQuery(Request $request)
    {
        $query = AdminAuditLog::with('admin')->latest();

        if ($action = trim((string) $request->query('action', ''))) {
            $query->where('action', $action);
        }

        if ($adminId = $request->query('admin_user_id')) {
            $query->where('admin_user_id', $adminId);
        }

        if ($targetType = trim((string) $request->query('target_type', ''))) {
            $query->where('target_type', $targetType);
        }

        if ($from = trim((string) $request->query('from', ''))) {
            $query->where('created_at', '>=', $this->parseAuditDateFilter($from, false));
        }

        if ($to = trim((string) $request->query('to', ''))) {
            $query->where('created_at', '<=', $this->parseAuditDateFilter($to, true));
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('action', 'like', "%{$search}%")
                    ->orWhere('target_type', 'like', "%{$search}%")
                    ->orWhere('target_id', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('admin', function ($adminQuery) use ($search): void {
                        $adminQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    private function parseAuditDateFilter(string $value, bool $endOfRange): Carbon
    {
        try {
            $date = Carbon::parse($value);
        } catch (\Throwable) {
            abort(422, 'Invalid audit date filter.');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $endOfRange ? $date->endOfDay() : $date->startOfDay();
        }

        return $date;
    }
}
