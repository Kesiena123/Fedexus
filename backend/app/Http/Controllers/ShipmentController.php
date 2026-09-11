<?php

namespace App\Http\Controllers;

use App\Events\ShipmentUpdated;
use App\Models\Shipment;
use App\Models\ShipmentDraft;
use App\Models\ShipmentSchedule;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\LocationCoordinateResolver;
use App\Services\NotificationDispatchService;
use App\Services\PaymentStageService;
use App\Services\QuoteService;
use App\Services\TrackingNumberService;
use App\Support\ShipmentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Shipment::with(['payments', 'trackingEvents'])->latest();
        if ($request->user()->role === 'customer') {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json(['shipments' => $query->paginate(20)]);
    }

    public function store(Request $request, QuoteService $quotes, AdminAuditLogger $audit, TrackingNumberService $tracking, LocationCoordinateResolver $coordinates): JsonResponse
    {
        $this->authorizeAdminRole($request);

        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'driver_id' => ['nullable', 'exists:users,id'],
            'service_level' => ['required', 'in:domestic_express,international_priority,freight'],
            'sender_name' => ['required', 'string', 'max:140'],
            'recipient_name' => ['required', 'string', 'max:140'],
            'origin_address' => ['required', 'array'],
            'destination_address' => ['required', 'array'],
            'weight_kg' => ['required', 'numeric', 'min:0.1'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
            'tracking_format' => ['nullable', 'in:GEX,FDX,TRK,LOG'],
            'metadata' => ['nullable', 'array'],
        ]);

        $ledgerUserId = $data['user_id'] ?? $this->publicShipmentLedgerUserId();
        unset($data['user_id']);

        $quote = $quotes->calculate([
            'origin_country' => $data['origin_address']['country'] ?? 'US',
            'origin_postal_code' => $data['origin_address']['postal_code'] ?? '00000',
            'destination_country' => $data['destination_address']['country'] ?? 'US',
            'destination_postal_code' => $data['destination_address']['postal_code'] ?? '00000',
            'weight_kg' => $data['weight_kg'],
            'declared_value' => $data['declared_value'] ?? 0,
            'service_level' => $data['service_level'],
        ]);

        $originAddress = $data['origin_address'];
        $destinationAddress = $data['destination_address'];
        $originCoords = $coordinates->resolveAddress($originAddress);
        $destinationCoords = $coordinates->resolveAddress($destinationAddress);
        if ($originCoords) {
            $originAddress = array_merge($originAddress, $originCoords);
        }
        if ($destinationCoords) {
            $destinationAddress = array_merge($destinationAddress, $destinationCoords);
        }

        $shipmentData = array_merge($data, [
            'user_id' => $ledgerUserId,
            'origin_address' => $originAddress,
            'destination_address' => $destinationAddress,
            'tracking_number' => $tracking->generate($data['tracking_format'] ?? null),
            'quoted_amount' => $quote['amount'],
            'estimated_delivery_at' => $quote['estimated_delivery_at'],
            'status' => 'shipment_requested',
            'metadata' => array_merge($data['metadata'] ?? [], ['created_by_admin_id' => $request->user()->id]),
        ]);
        unset($shipmentData['tracking_format']);

        $shipment = Shipment::create($shipmentData);

        $creationDescription = data_get($data, 'metadata.notifications.internal_shipment_notes')
            ?: 'Shipment created.';

        $shipment->trackingEvents()->create([
            'status' => $shipment->status,
            'location' => $data['origin_address']['city'] ?? null,
            'description' => $creationDescription,
            'created_by' => $request->user()->id,
            'occurred_at' => now(),
            'latitude' => $originCoords['latitude'] ?? null,
            'longitude' => $originCoords['longitude'] ?? null,
            'country_code' => $originAddress['country'] ?? null,
            'checkpoint_label' => 'Current Location',
        ]);

        $shipment->routePoints()->createMany([
            [
                'sort_order' => 1,
                'type' => 'origin',
                'label' => 'Origin',
                'location' => trim(($originAddress['city'] ?? 'Origin').', '.($originAddress['country'] ?? ''), ', '),
                'latitude' => $originCoords['latitude'] ?? null,
                'longitude' => $originCoords['longitude'] ?? null,
                'country_code' => $originAddress['country'] ?? null,
                'description' => 'Shipment origin recorded by administrator.',
                'created_by' => $request->user()->id,
            ],
            [
                'sort_order' => 100,
                'type' => 'destination',
                'label' => 'Destination',
                'location' => trim(($destinationAddress['city'] ?? 'Destination').', '.($destinationAddress['country'] ?? ''), ', '),
                'latitude' => $destinationCoords['latitude'] ?? null,
                'longitude' => $destinationCoords['longitude'] ?? null,
                'country_code' => $destinationAddress['country'] ?? null,
                'description' => 'Shipment destination recorded by administrator.',
                'created_by' => $request->user()->id,
            ],
        ]);

        $audit->log($request, $request->user(), 'shipment.created', [
            'target_type' => 'shipment',
            'target_id' => $shipment->id,
            'new_values' => [
                'status' => $shipment->status,
                'service_level' => $shipment->service_level,
                'driver_id' => $shipment->driver_id,
            ],
            'metadata' => [
                'tracking_preview' => data_get($data, 'metadata.tracking.preview'),
                'template_id' => data_get($data, 'metadata.workflow.template_id'),
            ],
        ]);

        event(new ShipmentUpdated($shipment));

        return response()->json(['shipment' => $shipment->load(['payments', 'trackingEvents'])], 201);
    }

    public function show(Shipment $shipment): JsonResponse
    {
        $this->authorizeShipmentAccess($shipment);

        return response()->json(['shipment' => $shipment->load(['payments', 'trackingEvents'])]);
    }

    public function update(Request $request, Shipment $shipment, QuoteService $quotes, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizeAdminRole($request);

        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'driver_id' => ['nullable', 'exists:users,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'service_level' => ['nullable', 'in:domestic_express,international_priority,freight'],
            'sender_name' => ['nullable', 'string', 'max:140'],
            'recipient_name' => ['nullable', 'string', 'max:140'],
            'origin_address' => ['nullable', 'array'],
            'destination_address' => ['nullable', 'array'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.1'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'metadata.shipment_reference' => ['nullable', 'string', 'max:120'],
            'metadata.packages' => ['nullable', 'array', 'min:1'],
            'metadata.packages.*.id' => ['required_with:metadata.packages', 'string', 'max:80'],
            'metadata.packages.*.packageName' => ['required_with:metadata.packages', 'string', 'max:140'],
            'metadata.packages.*.description' => ['nullable', 'string', 'max:1000'],
            'metadata.packages.*.category' => ['required_with:metadata.packages', 'string', 'max:80'],
            'metadata.packages.*.packageType' => ['required_with:metadata.packages', 'string', 'max:80'],
            'metadata.packages.*.quantity' => ['required_with:metadata.packages', 'integer', 'min:1'],
            'metadata.packages.*.weight' => ['required_with:metadata.packages', 'numeric', 'min:0.1'],
            'metadata.packages.*.length' => ['required_with:metadata.packages', 'numeric', 'min:0'],
            'metadata.packages.*.width' => ['required_with:metadata.packages', 'numeric', 'min:0'],
            'metadata.packages.*.height' => ['required_with:metadata.packages', 'numeric', 'min:0'],
            'metadata.packages.*.declaredValue' => ['required_with:metadata.packages', 'numeric', 'min:0'],
            'metadata.packages.*.currency' => ['required_with:metadata.packages', 'string', 'size:3'],
            'metadata.packages.*.insuranceValue' => ['nullable', 'numeric', 'min:0'],
            'metadata.packages.*.showImagesToCustomer' => ['nullable', 'boolean'],
            'metadata.packages.*.imageCount' => ['nullable', 'integer', 'min:0'],
        ]);

        if (array_key_exists('user_id', $data)) {
            abort_unless(
                User::whereKey($data['user_id'])->where('role', 'customer')->exists(),
                422,
                'Shipments can only be assigned to customer accounts.'
            );
        }

        $previousValues = [
            'user_id' => $shipment->user_id,
            'driver_id' => $shipment->driver_id,
            'warehouse_id' => $shipment->warehouse_id,
            'service_level' => $shipment->service_level,
            'sender_name' => $shipment->sender_name,
            'recipient_name' => $shipment->recipient_name,
            'weight_kg' => $shipment->weight_kg,
            'declared_value' => $shipment->declared_value,
            'metadata' => $shipment->metadata,
        ];

        $nextOrigin = $data['origin_address'] ?? $shipment->origin_address;
        $nextDestination = $data['destination_address'] ?? $shipment->destination_address;
        $nextWeight = $data['weight_kg'] ?? $shipment->weight_kg;
        $nextDeclaredValue = $data['declared_value'] ?? $shipment->declared_value;
        $nextServiceLevel = $data['service_level'] ?? $shipment->service_level;
        $nextMetadata = array_key_exists('metadata', $data)
            ? array_merge($shipment->metadata ?? [], $data['metadata'] ?? [])
            : ($shipment->metadata ?? []);
        $nextMetadata['created_by_admin_id'] = $shipment->metadata['created_by_admin_id'] ?? $request->user()->id;

        $quote = $quotes->calculate([
            'origin_country' => $nextOrigin['country'] ?? 'US',
            'origin_postal_code' => $nextOrigin['postal_code'] ?? '00000',
            'destination_country' => $nextDestination['country'] ?? 'US',
            'destination_postal_code' => $nextDestination['postal_code'] ?? '00000',
            'weight_kg' => $nextWeight,
            'declared_value' => $nextDeclaredValue ?? 0,
            'service_level' => $nextServiceLevel,
        ]);

        $shipment->update([
            'user_id' => $data['user_id'] ?? $shipment->user_id,
            'driver_id' => array_key_exists('driver_id', $data) ? $data['driver_id'] : $shipment->driver_id,
            'warehouse_id' => array_key_exists('warehouse_id', $data) ? $data['warehouse_id'] : $shipment->warehouse_id,
            'service_level' => $nextServiceLevel,
            'sender_name' => $data['sender_name'] ?? $shipment->sender_name,
            'recipient_name' => $data['recipient_name'] ?? $shipment->recipient_name,
            'origin_address' => $nextOrigin,
            'destination_address' => $nextDestination,
            'weight_kg' => $nextWeight,
            'declared_value' => $nextDeclaredValue,
            'quoted_amount' => $quote['amount'],
            'estimated_delivery_at' => $quote['estimated_delivery_at'],
            'metadata' => $nextMetadata,
        ]);

        $shipment->trackingEvents()->create([
            'status' => $shipment->status,
            'location' => $shipment->origin_address['city'] ?? null,
            'description' => 'Shipment profile and package details updated by administrator.',
            'created_by' => $request->user()->id,
            'occurred_at' => now(),
        ]);

        $currentValues = [
            'user_id' => $shipment->user_id,
            'driver_id' => $shipment->driver_id,
            'warehouse_id' => $shipment->warehouse_id,
            'service_level' => $shipment->service_level,
            'sender_name' => $shipment->sender_name,
            'recipient_name' => $shipment->recipient_name,
            'weight_kg' => $shipment->weight_kg,
            'declared_value' => $shipment->declared_value,
            'metadata' => $shipment->metadata,
        ];
        $changed = [];
        foreach ($currentValues as $key => $val) {
            $prev = $previousValues[$key] ?? null;
            if (json_encode($prev) !== json_encode($val)) {
                $changed[$key] = $val;
            }
        }
        $audit->log($request, $request->user(), 'shipment.updated', [
            'target_type' => 'shipment',
            'target_id' => $shipment->id,
            'previous_values' => $previousValues,
            'new_values' => $changed ?: ['status' => $shipment->status],
        ]);

        event(new ShipmentUpdated($shipment));

        return response()->json(['shipment' => $shipment->fresh(['payments', 'trackingEvents', 'attachments'])]);
    }

    public function track(string $trackingNumber): JsonResponse
    {
        $shipment = Shipment::with(['payments', 'paymentRequests', 'routePoints', 'trackingEvents', 'driver', 'warehouse', 'attachments'])
            ->where('tracking_number', $trackingNumber)
            ->firstOrFail();

        $payload = $shipment->toArray();
        $payload['attachments'] = $shipment->attachments
            ->filter(fn ($attachment) => $attachment->isVisibleOnTracking())
            ->values()
            ->map(fn ($attachment) => $attachment->publicPayload())
            ->all();

        return response()->json(['shipment' => $payload]);
    }

    public function status(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorizeOperationsRole($request);

        $data = $request->validate([
            'status' => ['required', 'string', 'max:80'],
            'location' => ['nullable', 'string'],
            'description' => ['required', 'string'],
            'driver_id' => ['nullable', 'exists:users,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'checkpoint_label' => ['nullable', 'string', 'max:120'],
            'warehouse_name' => ['nullable', 'string', 'max:120'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['status'] = ShipmentStatus::normalizeForDatabase($data['status']);
        abort_unless(in_array($data['status'], ShipmentStatus::databaseStatuses(), true), 422, 'Unsupported shipment status.');

        $shipment->update(collect($data)->only(['status', 'driver_id', 'warehouse_id'])->all());
        $shipment->trackingEvents()->create([
            'status' => $data['status'],
            'location' => $data['location'] ?? null,
            'description' => $data['description'],
            'created_by' => $request->user()->id,
            'occurred_at' => now(),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'country_code' => isset($data['country_code']) ? strtoupper($data['country_code']) : null,
            'checkpoint_label' => $data['checkpoint_label'] ?? null,
            'warehouse_name' => $data['warehouse_name'] ?? null,
            'admin_notes' => $data['admin_notes'] ?? null,
        ]);
        $this->storeRoutePointFromTrackingData($shipment, $data);

        event(new ShipmentUpdated($shipment));

        ShipmentDraft::where('admin_user_id', $request->user()->id)->delete();

        return response()->json(['shipment' => $shipment->fresh(['payments', 'trackingEvents'])]);
    }

    public function approve(Request $request, Shipment $shipment, TrackingNumberService $tracking, PaymentStageService $payments, AdminAuditLogger $audit, NotificationDispatchService $notifications): JsonResponse
    {
        $this->authorizeAdminRole($request);

        $data = $request->validate([
            'tracking_format' => ['nullable', 'in:GEX,FDX,TRK,LOG'],
        ]);

        abort_if($shipment->tracking_number, 422, 'Shipment already has a tracking number.');
        abort_unless(in_array($shipment->status, ['shipment_requested', 'admin_review'], true), 422, 'Shipment is not awaiting approval.');

        $previousTracking = $shipment->tracking_number;
        $previousStatus = $shipment->status;
        $shipment->update([
            'status' => 'approved',
            'tracking_number' => $tracking->generate($data['tracking_format'] ?? null),
        ]);

        if (($shipment->metadata['workflow']['payment_enabled'] ?? true) !== false) {
            $payments->createStages($shipment);
        }

        $shipment->trackingEvents()->create([
            'status' => 'approved',
            'location' => $shipment->origin_address['city'] ?? null,
            'description' => 'Shipment approved by administrator and tracking number generated.',
            'created_by' => $request->user()->id,
            'occurred_at' => now(),
        ]);

        $audit->log($request, $request->user(), 'shipment.approved', [
            'target_type' => 'shipment',
            'target_id' => $shipment->id,
            'previous_values' => [
                'status' => $previousStatus,
                'tracking_number' => $previousTracking,
            ],
            'new_values' => [
                'status' => $shipment->status,
                'tracking_number' => $shipment->tracking_number,
            ],
        ]);

        $notificationStatuses = $this->dispatchTrackingNotifications($shipment->fresh(['user']), $notifications);

        event(new ShipmentUpdated($shipment));

        return response()->json([
            'shipment' => $shipment->fresh(['payments', 'trackingEvents']),
            'tracking_url' => $this->trackingUrl((string) $shipment->tracking_number),
            'notification_statuses' => $notificationStatuses,
        ]);
    }

    public function regenerateTracking(Request $request, Shipment $shipment, TrackingNumberService $tracking, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizeAdminRole($request);

        $data = $request->validate([
            'tracking_format' => ['nullable', 'in:GEX,FDX,TRK,LOG'],
        ]);

        abort_unless($shipment->tracking_number, 422, 'Shipment must already have a tracking number.');

        $previousTracking = $shipment->tracking_number;
        $shipment->update([
            'tracking_number' => $tracking->generate($data['tracking_format'] ?? null),
        ]);

        $shipment->trackingEvents()->create([
            'status' => $shipment->status,
            'location' => $shipment->origin_address['city'] ?? null,
            'description' => 'Tracking number regenerated by administrator.',
            'created_by' => $request->user()->id,
            'occurred_at' => now(),
        ]);

        $audit->log($request, $request->user(), 'shipment.tracking.regenerated', [
            'target_type' => 'shipment',
            'target_id' => $shipment->id,
            'previous_values' => [
                'tracking_number' => $previousTracking,
            ],
            'new_values' => [
                'tracking_number' => $shipment->tracking_number,
            ],
        ]);

        event(new ShipmentUpdated($shipment));

        return response()->json(['shipment' => $shipment->fresh(['payments', 'trackingEvents'])]);
    }

    public function reject(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorizeAdminRole($request);

        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $shipment->update([
            'status' => 'rejected',
            'metadata' => array_merge($shipment->metadata ?? [], ['rejection_reason' => $data['reason']]),
        ]);

        $shipment->trackingEvents()->create([
            'status' => 'rejected',
            'location' => $shipment->origin_address['city'] ?? null,
            'description' => $data['reason'],
            'created_by' => $request->user()->id,
            'occurred_at' => now(),
        ]);

        event(new ShipmentUpdated($shipment));

        return response()->json(['shipment' => $shipment->fresh(['payments', 'trackingEvents'])]);
    }

    public function addTrackingEvent(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorizeOperationsRole($request);

        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'checkpoint_label' => ['nullable', 'string', 'max:120'],
            'warehouse_name' => ['nullable', 'string', 'max:120'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $eventStatus = isset($data['status']) && $data['status'] !== ''
            ? ShipmentStatus::normalizeForDatabase($data['status'])
            : $shipment->status;
        abort_unless(in_array($eventStatus, ShipmentStatus::databaseStatuses(), true), 422, 'Unsupported shipment status.');

        $event = $shipment->trackingEvents()->create([
            'status' => $eventStatus,
            'location' => $data['location'] ?? null,
            'description' => $data['description'],
            'created_by' => $request->user()->id,
            'occurred_at' => $data['occurred_at'] ?? now(),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'country_code' => isset($data['country_code']) ? strtoupper($data['country_code']) : null,
            'checkpoint_label' => $data['checkpoint_label'] ?? null,
            'warehouse_name' => $data['warehouse_name'] ?? null,
            'admin_notes' => $data['admin_notes'] ?? null,
        ]);
        $this->storeRoutePointFromTrackingData($shipment, $data + ['status' => $eventStatus]);

        event(new ShipmentUpdated($shipment));

        return response()->json([
            'event' => $event,
            'shipment' => $shipment->fresh(['payments', 'trackingEvents']),
        ]);
    }

    public function destroy(Request $request, Shipment $shipment, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizeAdminRole($request);

        $audit->log($request, $request->user(), 'shipment.deleted', [
            'target_type' => 'shipment',
            'target_id' => $shipment->id,
            'previous_values' => [
                'tracking_number' => $shipment->tracking_number,
                'status' => $shipment->status,
                'recipient_name' => $shipment->recipient_name,
                'metadata' => $shipment->metadata,
            ],
        ]);

        $shipment->attachments()->get()->each(function ($attachment): void {
            if ($attachment->disk && $attachment->path) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
        });

        $shipment->delete();

        return response()->json(['message' => 'Shipment deleted.']);
    }

    public function resendNotifications(Request $request, Shipment $shipment, NotificationDispatchService $notifications, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizeAdminRole($request);

        $data = $request->validate([
            'channels' => ['nullable', 'array'],
            'channels.*' => ['in:in_app,email,sms'],
        ]);

        abort_unless($shipment->tracking_number, 422, 'Shipment must have a tracking number before notifications can be sent.');

        $channels = $data['channels'] ?? ['in_app', 'email', 'sms'];
        $statuses = $this->dispatchTrackingNotifications($shipment->fresh(['user']), $notifications, $channels);

        $audit->log($request, $request->user(), 'shipment.notifications.resent', [
            'target_type' => 'shipment',
            'target_id' => $shipment->id,
            'metadata' => [
                'channels' => $channels,
                'notification_statuses' => $statuses,
                'tracking_number' => $shipment->tracking_number,
            ],
        ]);

        return response()->json([
            'message' => 'Tracking notifications dispatched.',
            'tracking_url' => $this->trackingUrl((string) $shipment->tracking_number),
            'notification_statuses' => $statuses,
        ]);
    }

    public function pickup(Request $request, Shipment $shipment): JsonResponse
    {
        return $this->schedule($request, $shipment, 'pickup');
    }

    public function delivery(Request $request, Shipment $shipment): JsonResponse
    {
        return $this->schedule($request, $shipment, 'delivery');
    }

    private function schedule(Request $request, Shipment $shipment, string $type): JsonResponse
    {
        $this->authorizeOperationsRole($request);

        $data = $request->validate([
            'window_start' => ['required', 'date'],
            'window_end' => ['required', 'date', 'after:window_start'],
            'address' => ['required', 'array'],
            'instructions' => ['nullable', 'string'],
        ]);

        $schedule = ShipmentSchedule::create($data + ['shipment_id' => $shipment->id, 'type' => $type]);

        return response()->json(['schedule' => $schedule], 201);
    }

    private function authorizeShipmentAccess(Shipment $shipment): void
    {
        $user = request()->user();
        if ($user?->role === 'customer') {
            abort_unless($shipment->user_id === $user->id, 403);
        }
    }

    private function authorizeOperationsRole(Request $request): void
    {
        abort_unless(in_array($request->user()->role, ['super_admin', 'admin', 'manager', 'support', 'warehouse'], true), 403);
    }

    private function authorizeAdminRole(Request $request): void
    {
        abort_unless(in_array($request->user()->role, ['super_admin', 'admin', 'manager'], true), 403);
    }

    private function trackingUrl(string $trackingNumber): string
    {
        $baseUrl = rtrim(config('app.url'), '/');

        return $baseUrl.'/tracking/'.rawurlencode(strtoupper(trim($trackingNumber)));
    }

    private function storeRoutePointFromTrackingData(Shipment $shipment, array $data): void
    {
        $coords = isset($data['latitude'], $data['longitude'])
            ? ['latitude' => $data['latitude'], 'longitude' => $data['longitude']]
            : app(LocationCoordinateResolver::class)->resolveLocation((string) ($data['location'] ?? ''), $data['country_code'] ?? null);

        if (! $coords) {
            return;
        }

        $nextSortOrder = ((int) $shipment->routePoints()->max('sort_order')) + 10;

        $shipment->routePoints()->create([
            'sort_order' => max(10, min($nextSortOrder, 90)),
            'type' => ($data['status'] ?? null) === 'customs_clearance' ? 'customs' : 'checkpoint',
            'label' => $data['checkpoint_label'] ?? ShipmentStatus::toDisplay($data['status'] ?? $shipment->status),
            'location' => $data['location'] ?? 'Recorded checkpoint',
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'country_code' => isset($data['country_code']) ? strtoupper($data['country_code']) : null,
            'description' => $data['description'] ?? null,
            'arrived_at' => $data['occurred_at'] ?? now(),
            'created_by' => request()->user()?->id,
        ]);
    }

    private function publicShipmentLedgerUserId(): int
    {
        return User::firstOrCreate(
            ['email' => 'public-shipments@system.local'],
            [
                'name' => 'Public Shipment Ledger',
                'password' => Hash::make(Str::random(40)),
                'role' => 'customer',
                'email_verified_at' => now(),
            ]
        )->id;
    }

    private function dispatchTrackingNotifications(Shipment $shipment, NotificationDispatchService $notifications, ?array $channels = null): array
    {
        $user = $shipment->user;

        if (! $user || ! $shipment->tracking_number) {
            return [];
        }

        $trackingUrl = $this->trackingUrl((string) $shipment->tracking_number);
        $estimatedDelivery = optional($shipment->estimated_delivery_at)?->toDateTimeString() ?? 'Pending';
        $defaultEmailMessage = trim(implode("\n\n", [
            'Your shipment has been created.',
            'Tracking Number: '.$shipment->tracking_number,
            'Track your shipment here: '.$trackingUrl,
            'Estimated delivery: '.$estimatedDelivery,
            'Customer support is available if you need assistance.',
        ]));
        $defaultSmsMessage = trim(implode("\n\n", [
            'Your shipment has been created.',
            'Tracking Number: '.$shipment->tracking_number,
            'Track your shipment here:',
            $trackingUrl,
        ]));

        $emailBody = str_replace(
            ['{{tracking_number}}', '{{tracking_link}}'],
            [$shipment->tracking_number, $trackingUrl],
            (string) data_get($shipment->metadata, 'notifications.customerEmailMessage', $defaultEmailMessage)
        );
        $smsBody = str_replace(
            ['{{tracking_number}}', '{{tracking_link}}'],
            [$shipment->tracking_number, $trackingUrl],
            (string) data_get($shipment->metadata, 'notifications.customerTrackingMessage', $defaultSmsMessage)
        );

        $payload = [
            'type' => 'shipment_tracking',
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'tracking_url' => $trackingUrl,
            'estimated_delivery_at' => optional($shipment->estimated_delivery_at)?->toIso8601String(),
        ];
        $destinations = [
            'email' => data_get($shipment->metadata, 'receiver.email') ?: data_get($shipment->destination_address, 'email') ?: $user->email,
            'sms' => data_get($shipment->metadata, 'receiver.phone') ?: data_get($shipment->destination_address, 'phone') ?: $user->phone,
        ];
        $requestedChannels = $channels ? array_values(array_unique($channels)) : ['in_app', 'email', 'sms'];
        $statuses = [];

        if (in_array('in_app', $requestedChannels, true)) {
            $statuses['in_app'] = $notifications->send(
                $user,
                'Shipment tracking is now available',
                'Your shipment is active. Tracking Number: '.$shipment->tracking_number.' Track here: '.$trackingUrl,
                $payload,
                ['in_app'],
                $destinations,
            )['in_app'] ?? 'stored';
        }

        if (in_array('email', $requestedChannels, true)) {
            $statuses['email'] = $notifications->send(
                $user,
                'Your shipment tracking details',
                $emailBody,
                $payload,
                ['email'],
                $destinations,
            )['email'] ?? 'skipped';
        }

        if (in_array('sms', $requestedChannels, true)) {
            $statuses['sms'] = $notifications->send(
                $user,
                'Shipment tracking update',
                $smsBody,
                $payload,
                ['sms'],
                $destinations,
            )['sms'] ?? 'skipped';
        }

        return $statuses;
    }
}
