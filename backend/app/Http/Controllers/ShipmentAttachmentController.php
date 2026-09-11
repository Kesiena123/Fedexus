<?php

namespace App\Http\Controllers;

use App\Events\ShipmentUpdated;
use App\Models\Shipment;
use App\Models\ShipmentAttachment;
use App\Services\AdminAuditLogger;
use App\Support\AdminPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShipmentAttachmentController extends Controller
{
    public function store(Request $request, Shipment $shipment, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::SHIPMENTS_ATTACHMENTS_MANAGE);

        $data = $request->validate([
            'category' => ['required', 'string', 'max:80'],
            'file' => ['required', 'file', 'max:10240'],
            'metadata' => ['nullable', 'array'],
        ]);

        $file = $data['file'];
        $storedName = Str::uuid()->toString().'_'.preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $path = $file->storeAs('shipments/'.$shipment->id, $storedName, 'public');

        $attachment = ShipmentAttachment::create([
            'shipment_id' => $shipment->id,
            'uploaded_by' => $request->user()->id,
            'category' => $data['category'],
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => (int) $file->getSize(),
            'metadata' => $data['metadata'] ?? null,
        ]);

        $audit->log($request, $request->user(), 'shipment.attachment.uploaded', [
            'target_type' => 'shipment_attachment',
            'target_id' => $attachment->id,
            'new_values' => [
                'shipment_id' => $shipment->id,
                'category' => $attachment->category,
                'original_name' => $attachment->original_name,
            ],
        ]);

        event(new ShipmentUpdated($shipment));

        return response()->json([
            'attachment' => $this->transformAttachment($attachment),
        ], 201);
    }

    public function index(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::SHIPMENTS_ATTACHMENTS_MANAGE);

        $attachments = ShipmentAttachment::where('shipment_id', $shipment->id)->latest()->get();

        return response()->json([
            'attachments' => $attachments->map(fn (ShipmentAttachment $attachment) => $this->transformAttachment($attachment)),
        ]);
    }

    public function destroy(Request $request, Shipment $shipment, ShipmentAttachment $attachment, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::SHIPMENTS_ATTACHMENTS_MANAGE);

        abort_unless($attachment->shipment_id === $shipment->id, 404);

        $audit->log($request, $request->user(), 'shipment.attachment.deleted', [
            'target_type' => 'shipment_attachment',
            'target_id' => $attachment->id,
            'previous_values' => [
                'shipment_id' => $shipment->id,
                'category' => $attachment->category,
                'original_name' => $attachment->original_name,
            ],
        ]);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        event(new ShipmentUpdated($shipment));

        return response()->json(['message' => 'Attachment deleted.']);
    }

    private function transformAttachment(ShipmentAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'shipment_id' => $attachment->shipment_id,
            'category' => $attachment->category,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size_bytes' => $attachment->size_bytes,
            'url' => Storage::disk($attachment->disk)->url($attachment->path),
            'metadata' => $attachment->metadata,
            'created_at' => $attachment->created_at,
        ];
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        AdminPermissions::authorize($request->user(), $permission);
    }
}
