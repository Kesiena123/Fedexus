@extends('layouts.app')
@section('title', 'Edit Shipment')
@php $hideHeaderFooter = true; @endphp
@section('content')
<x-dashboard-shell title="Edit Shipment">
    @if(session('success'))
        <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tracking Number</p>
            <h2 class="text-2xl font-bold text-slate-900">{{ $shipment->tracking_number ?? 'REQ-'.$shipment->id }}</h2>
            <p class="mt-1 text-sm capitalize text-slate-600">{{ str_replace('_', ' ', $shipment->status) }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.shipments.show', $shipment) }}" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">View Details</a>
            <a href="{{ route('admin.shipments') }}" class="rounded-md bg-[#0d5368] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0b4658]">Back to Shipments</a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.shipments.edit', $shipment) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Sender Information</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Full Name *</label>
                    <input type="text" name="sender_name" value="{{ old('sender_name', $shipment->sender_name) }}" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Company</label>
                    <input type="text" name="sender_company" value="{{ old('sender_company', $senderMeta['company'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Phone</label>
                    <input type="text" name="sender_phone" value="{{ old('sender_phone', $senderMeta['phone'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Email</label>
                    <input type="email" name="sender_email" value="{{ old('sender_email', $senderMeta['email'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Country</label>
                    <input type="text" name="sender_country" value="{{ old('sender_country', $originAddr['country'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">State/Province</label>
                    <input type="text" name="sender_state" value="{{ old('sender_state', $originAddr['state'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">City</label>
                    <input type="text" name="sender_city" value="{{ old('sender_city', $originAddr['city'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Address</label>
                    <input type="text" name="sender_address" value="{{ old('sender_address', $originAddr['address'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Postal Code</label>
                    <input type="text" name="sender_postal" value="{{ old('sender_postal', $originAddr['postal_code'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Receiver Information</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Full Name *</label>
                    <input type="text" name="recipient_name" value="{{ old('recipient_name', $shipment->recipient_name) }}" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Company</label>
                    <input type="text" name="recipient_company" value="{{ old('recipient_company', $receiverMeta['company'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Phone</label>
                    <input type="text" name="recipient_phone" value="{{ old('recipient_phone', $receiverMeta['phone'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Email</label>
                    <input type="email" name="recipient_email" value="{{ old('recipient_email', $receiverMeta['email'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Country</label>
                    <input type="text" name="recipient_country" value="{{ old('recipient_country', $destAddr['country'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">State/Province</label>
                    <input type="text" name="recipient_state" value="{{ old('recipient_state', $destAddr['state'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">City</label>
                    <input type="text" name="recipient_city" value="{{ old('recipient_city', $destAddr['city'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Address</label>
                    <input type="text" name="recipient_address" value="{{ old('recipient_address', $destAddr['address'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Postal Code</label>
                    <input type="text" name="recipient_postal" value="{{ old('recipient_postal', $destAddr['postal_code'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Shipment Information</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Service Level *</label>
                    <select name="service_level" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        <option value="domestic_express" @selected(old('service_level', $shipment->service_level) === 'domestic_express')>Domestic Express</option>
                        <option value="international_priority" @selected(old('service_level', $shipment->service_level) === 'international_priority')>International Priority</option>
                        <option value="freight" @selected(old('service_level', $shipment->service_level) === 'freight')>Freight</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Current Status *</label>
                    <select name="status" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $shipment->status) === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Priority</label>
                    <select name="priority" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        <option value="">Normal</option>
                        <option value="high" @selected(old('priority', $metadata['priority'] ?? '') === 'high')>High</option>
                        <option value="urgent" @selected(old('priority', $metadata['priority'] ?? '') === 'urgent')>Urgent</option>
                        <option value="low" @selected(old('priority', $metadata['priority'] ?? '') === 'low')>Low</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Estimated Delivery Date</label>
                    <input type="date" name="estimated_delivery_at" value="{{ old('estimated_delivery_at', $shipment->estimated_delivery_at?->format('Y-m-d')) }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-semibold text-slate-700">Description</label>
                    <textarea name="shipment_description" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none" rows="3">{{ old('shipment_description', $metadata['description'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Parcel Information</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Package Type</label>
                    <input type="text" name="package_type" value="{{ old('package_type', $firstPackage['package_type'] ?? '') }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Weight (kg)</label>
                    <input type="number" name="weight_kg" value="{{ old('weight_kg', $shipment->weight_kg) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Quantity</label>
                    <input type="number" name="quantity" value="{{ old('quantity', $firstPackage['quantity'] ?? 1) }}" min="1" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Length (cm)</label>
                    <input type="number" name="length_cm" value="{{ old('length_cm', $firstPackage['length_cm'] ?? '') }}" step="0.1" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Width (cm)</label>
                    <input type="number" name="width_cm" value="{{ old('width_cm', $firstPackage['width_cm'] ?? '') }}" step="0.1" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Height (cm)</label>
                    <input type="number" name="height_cm" value="{{ old('height_cm', $firstPackage['height_cm'] ?? '') }}" step="0.1" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div class="sm:col-span-2 lg:col-span-4">
                    <label class="block text-sm font-semibold text-slate-700">Package Description</label>
                    <textarea name="package_description" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none" rows="2">{{ old('package_description', $firstPackage['description'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Cost Breakdown</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Shipping Cost</label>
                    <input type="number" name="shipping_cost" value="{{ old('shipping_cost', $costMeta['shipping_cost'] ?? $shipment->quoted_amount ?? 0) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Handling Fee</label>
                    <input type="number" name="handling_fee" value="{{ old('handling_fee', $costMeta['handling_fee'] ?? 0) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Insurance</label>
                    <input type="number" name="insurance" value="{{ old('insurance', $costMeta['insurance'] ?? 0) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Customs Fee</label>
                    <input type="number" name="customs_fee" value="{{ old('customs_fee', $costMeta['customs_fee'] ?? 0) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Tax</label>
                    <input type="number" name="tax" value="{{ old('tax', $costMeta['tax'] ?? 0) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Discount</label>
                    <input type="number" name="discount" value="{{ old('discount', $costMeta['discount'] ?? 0) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Additional Charges</label>
                    <input type="number" name="additional_charges" value="{{ old('additional_charges', $costMeta['additional_charges'] ?? 0) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Location & Route</h3>
            <p class="mt-1 text-sm text-slate-500">Set origin and destination coordinates for the map.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="rounded-md bg-slate-50 p-4">
                    <h4 class="font-semibold text-slate-800">Origin Location</h4>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600">Latitude</label>
                            <input type="number" name="origin_latitude" value="{{ old('origin_latitude', $originAddr['latitude'] ?? '') }}" step="any" min="-90" max="90" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-azure-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600">Longitude</label>
                            <input type="number" name="origin_longitude" value="{{ old('origin_longitude', $originAddr['longitude'] ?? '') }}" step="any" min="-180" max="180" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-azure-500 focus:outline-none">
                        </div>
                    </div>
                </div>
                <div class="rounded-md bg-slate-50 p-4">
                    <h4 class="font-semibold text-slate-800">Destination Location</h4>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600">Latitude</label>
                            <input type="number" name="destination_latitude" value="{{ old('destination_latitude', $destAddr['latitude'] ?? '') }}" step="any" min="-90" max="90" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-azure-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600">Longitude</label>
                            <input type="number" name="destination_longitude" value="{{ old('destination_longitude', $destAddr['longitude'] ?? '') }}" step="any" min="-180" max="180" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-azure-500 focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            @if($shipment->routePoints->count())
                <div class="mt-4">
                    <h4 class="font-semibold text-slate-800">Existing Route Points</h4>
                    <div class="mt-2 space-y-2">
                        @foreach($shipment->routePoints as $point)
                            <div class="flex items-center gap-3 rounded-md bg-slate-50 px-4 py-2 text-sm">
                                <span class="inline-flex items-center rounded-full bg-{{ $point->type === 'destination' ? 'emerald' : ($point->type === 'origin' ? 'azure' : 'gold') }}-100 px-2.5 py-0.5 text-xs font-semibold text-{{ $point->type === 'destination' ? 'emerald' : ($point->type === 'origin' ? 'azure' : 'gold') }}-700">{{ ucfirst($point->type) }}</span>
                                <span class="text-slate-700">{{ $point->location ?: $point->label }}</span>
                                @if($point->latitude && $point->longitude)
                                    <span class="text-xs text-slate-400">({{ $point->latitude }}, {{ $point->longitude }})</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Shipment Photos</h3>
            <p class="mt-1 text-sm text-slate-500">Upload images via the attachments section on the shipment details page.</p>
            @if($shipment->attachments->count())
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($shipment->attachments as $attachment)
                        <div class="relative rounded-md border border-slate-200 p-2">
                            @if(in_array($attachment->mime_type, ['image/jpeg', 'image/png', 'image/webp', 'image/gif']))
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk($attachment->disk)->url($attachment->path) }}" alt="{{ $attachment->original_name }}" class="h-32 w-full rounded object-cover">
                            @endif
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $attachment->original_name }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-3 text-sm text-slate-500">No photos uploaded yet. Go to the shipment details page to manage attachments.</p>
            @endif
        </div>

        <div class="flex gap-4">
            <button type="submit" class="rounded-md bg-[#0d5368] px-8 py-3 text-sm font-bold text-white hover:bg-[#0b4658]">Save Changes</button>
            <a href="{{ route('admin.shipments.show', $shipment) }}" class="rounded-md border border-slate-300 px-8 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</a>
        </div>
    </form>
</x-dashboard-shell>
@endsection