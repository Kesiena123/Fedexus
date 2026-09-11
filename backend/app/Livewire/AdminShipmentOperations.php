<?php

namespace App\Livewire;

use App\Events\ShipmentUpdated;
use App\Models\Shipment;
use App\Models\ShipmentAttachment;
use App\Models\TrackingEvent;
use App\Services\AdminAuditLogger;
use App\Services\LocationCoordinateResolver;
use App\Services\NotificationDispatchService;
use App\Services\PaymentStageService;
use App\Services\TrackingNumberService;
use App\Support\AdminPermissions;
use App\Support\ShipmentStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Shipment Operations - Admin')]
class AdminShipmentOperations extends Component
{
    use WithFileUploads;

    public ?int $selectedShipmentId = null;

    // Form fields for status update / tracking event
    public string $status = '';

    public string $location = '';

    public string $description = '';

    public string $latitude = '';

    public string $longitude = '';

    public string $checkpointLabel = '';

    public string $warehouseName = '';

    public string $countryCode = '';

    public string $adminNotes = '';

    // Shipment profile edit fields
    public string $profileSenderName = '';

    public string $profileRecipientName = '';

    public string $profileServiceLevel = '';

    public string $profileWeightKg = '';

    public string $profileDeclaredValue = '';

    public string $profileShipmentReference = '';

    public array $profilePackages = [];

    // Attachment management
    public $attachmentFile;

    public string $attachmentCategory = 'operations';

    public string $attachmentPackageId = '';

    public string $attachmentVisibleToCustomer = 'yes';

    #[Locked]
    public array $attachments = [];

    #[Locked]
    public bool $attachmentsLoading = false;

    // Tracking number regeneration
    public string $trackingFormat = 'GEX';

    #[Locked]
    public ?string $busyAction = null;

    protected PaymentStageService $paymentStageService;

    protected TrackingNumberService $trackingService;

    protected AdminAuditLogger $auditLogger;

    protected NotificationDispatchService $notificationService;

    protected LocationCoordinateResolver $coordinateResolver;

    public function boot(
        PaymentStageService $paymentStageService,
        TrackingNumberService $trackingService,
        AdminAuditLogger $auditLogger,
        NotificationDispatchService $notificationService,
        LocationCoordinateResolver $coordinateResolver,
    ): void {
        $this->paymentStageService = $paymentStageService;
        $this->trackingService = $trackingService;
        $this->auditLogger = $auditLogger;
        $this->notificationService = $notificationService;
        $this->coordinateResolver = $coordinateResolver;
    }

    public function mount(): void
    {
        $shipments = $this->shipments;
        if (! empty($shipments)) {
            $this->selectedShipmentId = $shipments[0]['id'];
            $this->loadShipmentData();
        }
    }

    #[Computed]
    public function shipments(): array
    {
        return Shipment::with(['trackingEvents', 'payments', 'driver', 'warehouse', 'user'])
            ->latest()
            ->get()
            ->toArray();
    }

    #[Computed]
    public function selectedShipment(): ?array
    {
        if (! $this->selectedShipmentId) {
            return null;
        }
        $shipment = Shipment::with(['trackingEvents', 'payments', 'driver', 'warehouse', 'user', 'attachments'])
            ->find($this->selectedShipmentId);

        return $shipment?->toArray();
    }

    #[On('shipmentSelected')]
    public function selectShipment(int $id): void
    {
        $this->selectedShipmentId = $id;
        $this->loadShipmentData();
        $this->loadAttachments();
    }

    public function loadShipmentData(): void
    {
        $shipment = $this->selectedShipment;
        if (! $shipment) {
            return;
        }

        $latestEvent = $shipment['tracking_events'][0] ?? null;

        $this->status = $shipment['status'];
        $this->location = $latestEvent['location'] ?? '';
        $this->description = $latestEvent['description'] ?? 'Operational update recorded from admin control center.';
        $this->latitude = isset($latestEvent['latitude']) ? (string) $latestEvent['latitude'] : '';
        $this->longitude = isset($latestEvent['longitude']) ? (string) $latestEvent['longitude'] : '';
        $this->checkpointLabel = $latestEvent['checkpoint_label'] ?? '';
        $this->warehouseName = $latestEvent['warehouse_name'] ?? '';
        $this->countryCode = $latestEvent['country_code'] ?? '';
        $this->adminNotes = $latestEvent['admin_notes'] ?? '';

        $packages = $shipment['metadata']['packages'] ?? [];
        $this->profileSenderName = $shipment['sender_name'];
        $this->profileRecipientName = $shipment['recipient_name'];
        $this->profileServiceLevel = $shipment['service_level'];
        $this->profileWeightKg = (string) ($shipment['weight_kg'] ?? '0');
        $this->profileDeclaredValue = (string) ($shipment['declared_value'] ?? '0');
        $this->profileShipmentReference = (string) ($shipment['metadata']['shipment_reference'] ?? '');
        $this->profilePackages = ! empty($packages)
            ? array_map(fn ($pkg) => $this->toEditablePackage($pkg), $packages)
            : [$this->createEditablePackage()];
    }

    protected function toEditablePackage(array $pkg): array
    {
        $dimensions = $pkg['dimensions_cm'] ?? [];

        return [
            'id' => $pkg['id'] ?? 'pkg-'.bin2hex(random_bytes(4)),
            'packageName' => $pkg['package_name'] ?? 'Main shipment package',
            'description' => $pkg['description'] ?? '',
            'category' => $pkg['category'] ?? 'General cargo',
            'packageType' => $pkg['package_type'] ?? 'Carton',
            'quantity' => (string) ($pkg['quantity'] ?? '1'),
            'weight' => (string) ($pkg['weight_kg'] ?? '1'),
            'length' => (string) ($dimensions['length'] ?? '20'),
            'width' => (string) ($dimensions['width'] ?? '20'),
            'height' => (string) ($dimensions['height'] ?? '20'),
            'declaredValue' => (string) ($pkg['declared_value'] ?? '0'),
            'currency' => $pkg['currency'] ?? \App\Support\AppSettings::currency(),
            'insuranceValue' => (string) ($pkg['insurance_value'] ?? '0'),
            'showImagesToCustomer' => ($pkg['show_images_to_customer'] ?? true) ? 'yes' : 'no',
            'imageCount' => (string) ($pkg['image_count'] ?? '0'),
        ];
    }

    protected function createEditablePackage(): array
    {
        return [
            'id' => 'pkg-'.bin2hex(random_bytes(4)),
            'packageName' => 'Main shipment package',
            'description' => '',
            'category' => 'General cargo',
            'packageType' => 'Carton',
            'quantity' => '1',
            'weight' => '1',
            'length' => '20',
            'width' => '20',
            'height' => '20',
            'declaredValue' => '0',
            'currency' => \App\Support\AppSettings::currency(),
            'insuranceValue' => '0',
            'showImagesToCustomer' => 'yes',
            'imageCount' => '0',
        ];
    }

    public function loadAttachments(): void
    {
        if (! $this->selectedShipmentId) {
            $this->attachments = [];

            return;
        }

        $this->attachmentsLoading = true;
        $this->attachments = ShipmentAttachment::where('shipment_id', $this->selectedShipmentId)
            ->latest()
            ->get()
            ->toArray();
        $this->attachmentsLoading = false;
    }

    #[Computed]
    public function routePoints(): array
    {
        $shipment = $this->selectedShipment;
        if (! $shipment) {
            return [];
        }

        $origin = $shipment['origin_address'] ?? [];
        $destination = $shipment['destination_address'] ?? [];
        $events = $shipment['tracking_events'] ?? [];

        $points = [];
        $points[] = [
            'label' => 'Origin',
            'location' => $origin['city'] ?? $origin['country'] ?? '',
            'latitude' => $origin['latitude'] ?? null,
            'longitude' => $origin['longitude'] ?? null,
            'kind' => 'origin',
        ];

        foreach (array_reverse($events) as $event) {
            $points[] = [
                'label' => $event['checkpoint_label'] ?? $event['status'],
                'location' => $event['location'] ?? '',
                'latitude' => $event['latitude'] ?? null,
                'longitude' => $event['longitude'] ?? null,
                'kind' => isset($events[0]) && $event['id'] === $events[0]['id'] ? 'current' : 'checkpoint',
            ];
        }

        $points[] = [
            'label' => 'Destination',
            'location' => $destination['city'] ?? $destination['country'] ?? '',
            'latitude' => $destination['latitude'] ?? null,
            'longitude' => $destination['longitude'] ?? null,
            'kind' => 'destination',
        ];

        return $points;
    }

    public function updatePackageField(int $index, string $field, string $value): void
    {
        if (isset($this->profilePackages[$index])) {
            $this->profilePackages[$index][$field] = $value;
        }
    }

    public function addPackage(): void
    {
        $this->profilePackages[] = $this->createEditablePackage();
    }

    public function removePackage(int $index): void
    {
        if (count($this->profilePackages) <= 1) {
            return;
        }
        unset($this->profilePackages[$index]);
        $this->profilePackages = array_values($this->profilePackages);
    }

    public function saveShipmentUpdate(): void
    {
        if (! $this->selectedShipmentId) {
            return;
        }

        AdminPermissions::authorize(Auth::user(), AdminPermissions::SHIPMENTS_UPDATE);

        $this->busyAction = 'save-shipment-status';

        try {
            $shipment = Shipment::findOrFail($this->selectedShipmentId);
            $previousStatus = $shipment->status;
            $normalizedStatus = ShipmentStatus::normalizeForDatabase($this->status);
            abort_unless(in_array($normalizedStatus, ShipmentStatus::databaseStatuses(), true), 422, 'Unsupported shipment status.');

            $shipment->update(['status' => $normalizedStatus]);
            $resolvedCoords = ($this->latitude !== '' && $this->longitude !== '')
                ? ['latitude' => (float) $this->latitude, 'longitude' => (float) $this->longitude]
                : $this->coordinateResolver->resolveLocation($this->location, $this->countryCode ?: null);

            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'status' => $normalizedStatus,
                'location' => $this->location ?: null,
                'description' => $this->description,
                'created_by' => Auth::id(),
                'occurred_at' => now(),
                'latitude' => $resolvedCoords['latitude'] ?? null,
                'longitude' => $resolvedCoords['longitude'] ?? null,
                'country_code' => $this->countryCode ?: null,
                'checkpoint_label' => $this->checkpointLabel ?: 'Current Location',
                'warehouse_name' => $this->warehouseName ?: null,
                'admin_notes' => $this->adminNotes ?: null,
            ]);

            if ($resolvedCoords) {
                $shipment->routePoints()->create([
                    'sort_order' => max(10, min(((int) $shipment->routePoints()->max('sort_order')) + 10, 90)),
                    'type' => $normalizedStatus === 'international_processing' ? 'checkpoint' : 'checkpoint',
                    'label' => $this->checkpointLabel ?: ShipmentStatus::toDisplay($normalizedStatus),
                    'location' => $this->location ?: 'Recorded checkpoint',
                    'latitude' => $resolvedCoords['latitude'],
                    'longitude' => $resolvedCoords['longitude'],
                    'country_code' => $this->countryCode ?: null,
                    'description' => $this->description,
                    'arrived_at' => now(),
                    'created_by' => Auth::id(),
                ]);
            }

            ShipmentUpdated::dispatch($shipment);

            $this->auditLogger->log(
                request(),
                Auth::user(),
                'shipment.status.updated',
                [
                    'target_type' => 'shipment',
                    'target_id' => $shipment->id,
                    'previous_values' => ['status' => $previousStatus],
                    'new_values' => ['status' => $normalizedStatus, 'location' => $this->location],
                ]
            );

            $this->dispatch('shipmentMutated', message: 'Shipment tracking and location updated.');
        } finally {
            $this->busyAction = null;
        }
    }

    public function saveShipmentProfile(): void
    {
        if (! $this->selectedShipmentId) {
            return;
        }

        AdminPermissions::authorize(Auth::user(), AdminPermissions::SHIPMENTS_UPDATE);

        $this->busyAction = 'save-shipment-profile';

        try {
            $shipment = Shipment::findOrFail($this->selectedShipmentId);
            $metadata = $shipment->metadata ?? [];

            $metadata['shipment_reference'] = $this->profileShipmentReference;
            $metadata['packages'] = array_map(function ($pkg) {
                return [
                    'id' => $pkg['id'],
                    'package_name' => $pkg['packageName'],
                    'description' => $pkg['description'],
                    'category' => $pkg['category'],
                    'package_type' => $pkg['packageType'],
                    'quantity' => (int) $pkg['quantity'],
                    'weight_kg' => (float) $pkg['weight'],
                    'dimensions_cm' => [
                        'length' => (float) $pkg['length'],
                        'width' => (float) $pkg['width'],
                        'height' => (float) $pkg['height'],
                    ],
                    'declared_value' => (float) $pkg['declaredValue'],
                    'currency' => strtoupper($pkg['currency']),
                    'insurance_value' => (float) $pkg['insuranceValue'],
                    'show_images_to_customer' => $pkg['showImagesToCustomer'] === 'yes',
                    'image_count' => (int) $pkg['imageCount'],
                ];
            }, $this->profilePackages);

            $shipment->update([
                'sender_name' => $this->profileSenderName,
                'recipient_name' => $this->profileRecipientName,
                'service_level' => $this->profileServiceLevel,
                'weight_kg' => (float) $this->profileWeightKg,
                'declared_value' => (float) $this->profileDeclaredValue,
                'metadata' => $metadata,
            ]);

            ShipmentUpdated::dispatch($shipment);

            $this->auditLogger->log(
                request(),
                Auth::user(),
                'shipment.profile.updated',
                [
                    'target_type' => 'shipment',
                    'target_id' => $shipment->id,
                ]
            );

            $this->dispatch('shipmentMutated', message: 'Shipment profile updated.');
        } finally {
            $this->busyAction = null;
        }
    }

    public function regenerateTracking(): void
    {
        if (! $this->selectedShipmentId) {
            return;
        }

        AdminPermissions::authorize(Auth::user(), AdminPermissions::SHIPMENTS_UPDATE);

        $this->busyAction = 'regenerate-tracking';

        try {
            $shipment = Shipment::findOrFail($this->selectedShipmentId);
            $newTracking = $this->trackingService->generate($this->trackingFormat);

            $shipment->update(['tracking_number' => $newTracking]);

            ShipmentUpdated::dispatch($shipment);

            $this->auditLogger->log(
                request(),
                Auth::user(),
                'shipment.tracking.regenerated',
                [
                    'target_type' => 'shipment',
                    'target_id' => $shipment->id,
                    'new_values' => ['tracking_number' => $newTracking],
                ]
            );

            $this->dispatch('shipmentMutated', message: 'Tracking number regenerated.');
        } finally {
            $this->busyAction = null;
        }
    }

    public function unlockNextPaymentStage(): void
    {
        if (! $this->selectedShipmentId) {
            return;
        }

        AdminPermissions::authorize(Auth::user(), AdminPermissions::PAYMENTS_MANAGE);

        $this->busyAction = 'unlock-payment-stage';

        try {
            $shipment = Shipment::findOrFail($this->selectedShipmentId);
            $unlocked = $this->paymentStageService->unlockNextStage($shipment);

            if ($unlocked) {
                ShipmentUpdated::dispatch($shipment);

                $this->auditLogger->log(
                    request(),
                    Auth::user(),
                    'payment.stage.unlocked',
                    [
                        'target_type' => 'payment',
                        'target_id' => $unlocked->id,
                        'new_values' => ['stage' => $unlocked->stage, 'status' => 'pending'],
                    ]
                );

                $this->dispatch('shipmentMutated', message: 'Next payment stage unlocked.');
            } else {
                $this->dispatch('shipmentMutated', message: 'No locked payment stages available to unlock.');
            }
        } finally {
            $this->busyAction = null;
        }
    }

    public function uploadAttachment(): void
    {
        if (! $this->selectedShipmentId || ! $this->attachmentFile) {
            return;
        }

        AdminPermissions::authorize(Auth::user(), AdminPermissions::SHIPMENTS_ATTACHMENTS_MANAGE);

        $this->busyAction = 'upload-attachment';

        try {
            $this->validate([
                'attachmentFile' => 'file|max:10240',
                'attachmentCategory' => 'required|string|max:100',
            ]);

            $file = $this->attachmentFile;
            $path = $file->store('shipment-attachments/'.$this->selectedShipmentId, 'public');

            $metadata = [];
            if ($this->attachmentCategory === 'package_image') {
                $metadata = [
                    'package_id' => $this->attachmentPackageId,
                    'visible_to_customer' => $this->attachmentVisibleToCustomer === 'yes',
                ];
            }

            ShipmentAttachment::create([
                'shipment_id' => $this->selectedShipmentId,
                'uploaded_by' => Auth::id(),
                'category' => $this->attachmentCategory,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $file->hashName(),
                'disk' => 'public',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'metadata' => $metadata,
            ]);

            $this->attachmentFile = null;
            $this->loadAttachments();

            $this->dispatch('shipmentMutated', message: 'Attachment uploaded.');
        } finally {
            $this->busyAction = null;
        }
    }

    public function deleteAttachment(int $attachmentId): void
    {
        AdminPermissions::authorize(Auth::user(), AdminPermissions::SHIPMENTS_ATTACHMENTS_MANAGE);

        $this->busyAction = 'delete-attachment-'.$attachmentId;

        try {
            $attachment = ShipmentAttachment::findOrFail($attachmentId);
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();

            $this->loadAttachments();
            $this->dispatch('shipmentMutated', message: 'Attachment deleted.');
        } finally {
            $this->busyAction = null;
        }
    }

    public function deleteShipment(): void
    {
        if (! $this->selectedShipmentId) {
            return;
        }

        AdminPermissions::authorize(Auth::user(), AdminPermissions::SHIPMENTS_UPDATE);

        $this->busyAction = 'delete-shipment';

        try {
            $shipment = Shipment::findOrFail($this->selectedShipmentId);
            $shipment->delete();

            $this->selectedShipmentId = null;
            $this->dispatch('shipmentMutated', message: 'Shipment deleted.');
        } finally {
            $this->busyAction = null;
        }
    }

    public function refreshShipmentData(): void
    {
        $this->loadShipmentData();
        $this->loadAttachments();
        $this->dispatch('shipmentMutated', message: 'Shipment data refreshed.');
    }

    public function render()
    {
        return view('livewire.admin-shipment-operations', [
            'shipments' => $this->shipments,
            'selectedShipment' => $this->selectedShipment,
            'routePoints' => $this->routePoints,
            'canUpdateShipments' => Auth::user() && AdminPermissions::can(Auth::user(), AdminPermissions::SHIPMENTS_UPDATE),
            'canManagePayments' => Auth::user() && AdminPermissions::can(Auth::user(), AdminPermissions::PAYMENTS_MANAGE),
            'canManageAttachments' => Auth::user() && AdminPermissions::can(Auth::user(), AdminPermissions::SHIPMENTS_ATTACHMENTS_MANAGE),
        ]);
    }
}
