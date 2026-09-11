<?php

namespace App\Http\Controllers;

use App\Models\ShipmentDraft;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Support\AdminPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentDraftController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::SHIPMENTS_CREATE);

        $draft = ShipmentDraft::where('admin_user_id', $request->user()->id)->first();

        return response()->json([
            'draft' => $draft ? [
                'last_step' => $draft->last_step,
                'payload' => $draft->payload,
                'updated_at' => $draft->updated_at,
            ] : null,
        ]);
    }

    public function upsert(Request $request, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::SHIPMENTS_CREATE);

        $data = $request->validate([
            'last_step' => ['required', 'integer', 'min:1', 'max:12'],
            'payload' => ['required', 'array'],
        ]);

        $customerUserId = $data['payload']['customerId'] ?? null;

        if ($customerUserId) {
            abort_unless(
                User::whereKey($customerUserId)->where('role', 'customer')->exists(),
                422,
                'Draft customer must be a valid customer user.'
            );
        }

        $draft = ShipmentDraft::updateOrCreate(
            ['admin_user_id' => $request->user()->id],
            [
                'customer_user_id' => $customerUserId,
                'last_step' => $data['last_step'],
                'payload' => $data['payload'],
                'expires_at' => now()->addDays(30),
            ]
        );

        $audit->log($request, $request->user(), 'shipment.draft.saved', [
            'target_type' => 'shipment_draft',
            'target_id' => $draft->id,
            'new_values' => [
                'last_step' => $draft->last_step,
                'customer_user_id' => $draft->customer_user_id,
            ],
        ]);

        return response()->json([
            'draft' => [
                'last_step' => $draft->last_step,
                'payload' => $draft->payload,
                'updated_at' => $draft->updated_at,
            ],
        ]);
    }

    public function destroy(Request $request, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::SHIPMENTS_CREATE);

        $draft = ShipmentDraft::where('admin_user_id', $request->user()->id)->first();

        ShipmentDraft::where('admin_user_id', $request->user()->id)->delete();

        if ($draft) {
            $audit->log($request, $request->user(), 'shipment.draft.deleted', [
                'target_type' => 'shipment_draft',
                'target_id' => $draft->id,
            ]);
        }

        return response()->json(['message' => 'Draft deleted.']);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        AdminPermissions::authorize($request->user(), $permission);
    }
}
