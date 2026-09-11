<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ShipmentAttachment extends Model
{
    protected $fillable = [
        'shipment_id',
        'uploaded_by',
        'category',
        'original_name',
        'stored_name',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $hidden = [
        'stored_name',
        'disk',
        'path',
        'uploaded_by',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isVisibleOnTracking(): bool
    {
        if (in_array($this->category, ['tracking_qr', 'tracking_barcode'], true)) {
            return true;
        }

        if ($this->category !== 'package_image') {
            return false;
        }

        return (bool) data_get($this->metadata, 'visible_to_customer', false);
    }

    public function publicPayload(): array
    {
        return [
            'id' => $this->id,
            'shipment_id' => $this->shipment_id,
            'category' => $this->category,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'url' => Storage::disk($this->disk)->url($this->path),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }
}
