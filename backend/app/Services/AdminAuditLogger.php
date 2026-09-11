<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AdminAuditLogger
{
    public function log(Request $request, User $admin, string $action, array $context = []): AdminAuditLog
    {
        return AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'action' => $action,
            'target_type' => $context['target_type'] ?? null,
            'target_id' => isset($context['target_id']) ? (string) $context['target_id'] : null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'device_name' => $request->header('X-Device-Name'),
            'device_fingerprint' => $request->header('X-Device-Fingerprint'),
            'reason' => $context['reason'] ?? null,
            'previous_values' => $context['previous_values'] ?? null,
            'new_values' => $context['new_values'] ?? null,
            'metadata' => $context['metadata'] ?? null,
        ]);
    }
}
