@extends('layouts.app')
@section('title', 'Shipment Details')
@php
    $hideHeaderFooter = true;
    $money = fn ($value) => '$'.number_format((float) ($value ?? 0), 2);
    $address = fn ($addr) => collect([$addr['address'] ?? null, $addr['city'] ?? null, $addr['state'] ?? null, $addr['country'] ?? null])->filter()->implode(', ') ?: 'Not provided';
    $timelineStatuses = [
        'shipment_requested' => 'Shipment Created',
        'approved' => 'Package Received',
        'warehouse_processing' => 'Processing',
        'international_processing' => 'In Transit',
        'destination_hub' => 'Arrived at Facility',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
    ];
@endphp
@section('content')
    <x-dashboard-shell title="Shipment Details">
        @if(session('success'))
            <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tracking Number</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $shipment->tracking_number ?? 'REQ-'.$shipment->id }}</h2>
                        <p class="mt-1 text-sm capitalize text-slate-600">{{ str_replace('_', ' ', $shipment->status) }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($shipment->tracking_number)
                            <a href="{{ route('tracking.number', $shipment->tracking_number) }}" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Public Tracking</a>
                        @endif
                        <a href="{{ route('admin.shipments.edit', $shipment) }}" class="rounded-md border border-amber-200 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-50">Edit Shipment</a>
                        @if($shipment->tracking_number)
                            <a href="{{ route('admin.shipments.receipt', $shipment) }}" class="rounded-md border border-green-200 px-4 py-2 text-sm font-semibold text-green-700 hover:bg-green-50">Download Receipt</a>
                        @endif
                        <a href="{{ route('admin.shipments') }}" class="rounded-md bg-[#0d5368] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0b4658]">Back to Shipments</a>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-md bg-slate-50 p-4">
                        <h3 class="font-semibold text-slate-900">Sender Information</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $shipment->sender_name }}</p>
                        <p class="text-sm text-slate-600">{{ data_get($shipment->metadata, 'sender.email', 'Email unavailable') }}</p>
                        <p class="text-sm text-slate-600">{{ data_get($shipment->metadata, 'sender.phone', 'Phone unavailable') }}</p>
                        <p class="mt-2 text-sm text-slate-600">{{ $address($shipment->origin_address) }}</p>
                    </div>
                    <div class="rounded-md bg-slate-50 p-4">
                        <h3 class="font-semibold text-slate-900">Receiver Information</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $shipment->recipient_name }}</p>
                        <p class="text-sm text-slate-600">{{ data_get($shipment->metadata, 'receiver.email', 'Email unavailable') }}</p>
                        <p class="text-sm text-slate-600">{{ data_get($shipment->metadata, 'receiver.phone', 'Phone unavailable') }}</p>
                        <p class="mt-2 text-sm text-slate-600">{{ $address($shipment->destination_address) }}</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-4">
                    <div class="rounded-md border border-slate-200 p-4">
                        <p class="text-xs text-slate-500">Shipment Type</p>
                        <p class="mt-1 font-semibold capitalize">{{ str_replace('_', ' ', $shipment->service_level) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 p-4">
                        <p class="text-xs text-slate-500">Weight</p>
                        <p class="mt-1 font-semibold">{{ $shipment->weight_kg }} kg</p>
                    </div>
                    <div class="rounded-md border border-slate-200 p-4">
                        <p class="text-xs text-slate-500">Estimated Delivery</p>
                        <p class="mt-1 font-semibold">{{ $shipment->estimated_delivery_at?->format('M d, Y') ?? '-' }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 p-4">
                        <p class="text-xs text-slate-500">Amount</p>
                        <p class="mt-1 font-semibold">{{ $money($shipment->quoted_amount) }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900">Add Tracking Update</h3>
                <form method="POST" action="{{ route('admin.shipments.show', $shipment) }}" class="mt-4 grid gap-4">
                    @csrf
                    <select name="status" class="min-h-11 rounded-md border border-slate-200 px-3 text-sm" required>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $shipment->status) === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                        @endforeach
                    </select>
                    <input name="location" value="{{ old('location') }}" class="min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="Location" required>
                    <input name="occurred_at" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" type="datetime-local" class="min-h-11 rounded-md border border-slate-200 px-3 text-sm" required>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input name="latitude" value="{{ old('latitude') }}" type="number" step="any" min="-90" max="90" class="min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="Latitude">
                        <input name="longitude" value="{{ old('longitude') }}" type="number" step="any" min="-180" max="180" class="min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="Longitude">
                    </div>
                    <textarea name="description" class="min-h-28 rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Description" required>{{ old('description') }}</textarea>
                    @if($errors->any())
                        <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>
                    @endif
                    <button class="min-h-11 rounded-md bg-[#0d5368] px-5 text-sm font-bold text-white hover:bg-[#0b4658]">Save Tracking Update</button>
                </form>
            </section>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_0.8fr]">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900">Shipment Timeline</h3>
                <div class="mt-5 space-y-4">
                    @foreach($timelineStatuses as $status => $label)
                        @php($event = $shipment->trackingEvents->firstWhere('status', $status))
                        <div class="flex gap-4">
                            <div class="mt-1 size-3 shrink-0 rounded-full {{ $event ? 'bg-[#0d5368]' : 'bg-slate-300' }}"></div>
                            <div>
                                <p class="font-semibold text-slate-900">{{ $label }}</p>
                                <p class="text-sm text-slate-600">{{ $event?->location ?? 'Waiting for update' }}</p>
                                <p class="text-sm text-slate-500">{{ $event?->description ?? '' }}</p>
                                <p class="text-xs text-slate-400">{{ $event?->occurred_at?->format('M d, Y H:i') ?? '' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900">Deposits & Attachments</h3>
                <div class="mt-4 space-y-3">
                    @forelse($shipment->payments as $payment)
                        <div class="rounded-md bg-slate-50 p-3 text-sm">
                            <p class="font-semibold">Stage {{ $payment->stage }} - {{ $payment->label }}</p>
                            <p class="text-slate-600">{{ $money($payment->amount) }} - {{ str_replace('_', ' ', $payment->status) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No deposit records have been created for this shipment.</p>
                    @endforelse
                </div>
                <div class="mt-5 space-y-3">
                    @forelse($shipment->attachments as $attachment)
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk($attachment->disk)->url($attachment->path) }}" class="block rounded-md border border-slate-200 p-3 text-sm font-semibold text-[#0d5368] hover:bg-slate-50" target="_blank">{{ $attachment->original_name }}</a>
                    @empty
                        <p class="text-sm text-slate-500">No shipment attachments uploaded yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        @if($mapPoints->count())
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <div class="tracking-fade-in-delay2 shipment-map-wrapper">
            <div class="shipment-map-container">
                <div class="shipment-map-header">
                    <div class="shipment-map-header-left">
                        <span class="shipment-map-header-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        </span>
                        <div>
                            <h3 class="shipment-map-title">Live Tracking Map</h3>
                            <p class="shipment-map-subtitle" id="live-status-text">
                                {{ str_replace('_', ' ', ucfirst($shipment->status)) }} &middot; Last updated: {{ ($shipment->trackingEvents->first())?->occurred_at?->diffForHumans() ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                    <div class="shipment-map-header-right">
                        <span class="shipment-map-live-badge">
                            <span class="shipment-map-live-dot"></span>
                            LIVE
                        </span>
                        <span class="shipment-map-refresh-text" id="live-refresh-text">Auto-refreshing</span>
                    </div>
                </div>
                <div class="shipment-map-body">
                    <div id="shipment-route-map"></div>
                    <div id="map-unavailable" class="shipment-map-unavailable" style="display: {{ $mapPoints->count() ? 'none' : 'flex' }}">
                        <svg class="shipment-map-unavailable-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/></svg>
                        <p class="shipment-map-unavailable-title">Live location unavailable</p>
                        <p class="shipment-map-unavailable-desc">Live location is currently unavailable. Tracking information will be updated when a new location is available from the operations team.</p>
                    </div>
                </div>
            </div>
        </div>
        <script>
        (function() {
            var trackingNumber = @json($shipment->tracking_number);
            var initialPoints = @json($mapPoints);
            var map = null;
            var markers = [];
            var routeLine = null;
            var isUserInteracting = false;
            var interactionTimeout = null;

            function showUnavailable(show) {
                var el = document.getElementById('map-unavailable');
                if (el) el.style.display = show ? 'flex' : 'none';
            }

            function createMarkerIcon(type) {
                var colors = {
                    origin: { bg: '#2563eb', border: '#1d4ed8', icon: 'O' },
                    destination: { bg: '#059669', border: '#047857', icon: 'D' },
                    current: { bg: '#dc2626', border: '#b91c1c', icon: '' },
                    transit: { bg: '#7c3aed', border: '#6d28d9', icon: '' }
                };
                var c = colors[type] || colors.transit;
                var size = type === 'current' ? 36 : 28;
                var html;
                if (type === 'current') {
                    html = '<div class="spm-marker-current">' +
                        '<div class="spm-marker-pulse"></div>' +
                        '<div class="spm-marker-dot" style="background:' + c.bg + ';border-color:' + c.border + '"></div>' +
                        '</div>';
                } else {
                    html = '<div class="spm-marker" style="background:' + c.bg + ';border-color:' + c.border + '">' +
                        '<span style="color:#fff;font-weight:700;font-size:' + (type === 'origin' || type === 'destination' ? '11px' : '0') + '">' + c.icon + '</span>' +
                        '</div>';
                }
                return L.divIcon({
                    html: html,
                    className: 'spm-marker-wrapper',
                    iconSize: [size, size],
                    iconAnchor: [size / 2, size / 2],
                    popupAnchor: [0, -size / 2 - 4]
                });
            }

            function createPopupContent(point) {
                var typeLabel = point.type === 'origin' ? 'Origin' :
                                point.type === 'destination' ? 'Destination' :
                                point.type === 'current' ? 'Current Location' : 'Transit Point';
                var typeColor = point.type === 'origin' ? '#2563eb' :
                                point.type === 'destination' ? '#059669' :
                                point.type === 'current' ? '#dc2626' : '#7c3aed';
                return '<div class="spm-popup">' +
                    '<div class="spm-popup-badge" style="background:' + typeColor + '">' + typeLabel + '</div>' +
                    '<div class="spm-popup-title">' + (point.label || 'Checkpoint') + '</div>' +
                    '<div class="spm-popup-location">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg> ' +
                        (point.location || 'Unknown location') +
                    '</div>' +
                    '<div class="spm-popup-coords">' + point.latitude.toFixed(4) + ', ' + point.longitude.toFixed(4) + '</div>' +
                    '</div>';
            }

            function initMap(points) {
                var el = document.getElementById('shipment-route-map');
                if (!el || !window.L || !points || !points.length) { showUnavailable(true); return; }

                showUnavailable(false);
                var latLngs = points.map(function(p) { return [p.latitude, p.longitude]; });

                var mapSettings = @json($mapSettings);
                map = L.map(el, {
                    center: [mapSettings.defaultLatitude, mapSettings.defaultLongitude],
                    zoom: mapSettings.zoom,
                    zoomControl: true,
                    scrollWheelZoom: true
                });

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                points.forEach(function(point) {
                    var marker = L.marker([point.latitude, point.longitude], {
                        icon: createMarkerIcon(point.type)
                    }).addTo(map).bindPopup(createPopupContent(point), { maxWidth: 260, className: 'spm-popup-wrapper' });
                    markers.push(marker);
                });

                if (latLngs.length > 1) {
                    routeLine = L.polyline(latLngs, {
                        color: '#1e40af',
                        weight: 4,
                        opacity: 0.85,
                        dashArray: '10, 8',
                        lineCap: 'round',
                        lineJoin: 'round'
                    }).addTo(map);

                    for (var i = 0; i < latLngs.length - 1; i++) {
                        var midLat = (latLngs[i][0] + latLngs[i + 1][0]) / 2;
                        var midLng = (latLngs[i][1] + latLngs[i + 1][1]) / 2;
                        var angle = Math.atan2(latLngs[i + 1][1] - latLngs[i][1], latLngs[i + 1][0] - latLngs[i][0]) * 180 / Math.PI;
                        var arrowIcon = L.divIcon({
                            html: '<div class="spm-arrow" style="transform:rotate(' + (-angle + 90) + 'deg)">&#9650;</div>',
                            className: 'spm-arrow-wrapper',
                            iconSize: [16, 16],
                            iconAnchor: [8, 8]
                        });
                        L.marker([midLat, midLng], { icon: arrowIcon, interactive: false }).addTo(map);
                    }

                    map.fitBounds(routeLine.getBounds(), { padding: [40, 40] });
                } else {
                    map.setView(latLngs[0], 13);
                }

                map.on('mousedown', function() { isUserInteracting = true; clearTimeout(interactionTimeout); });
                map.on('mouseup', function() { interactionTimeout = setTimeout(function() { isUserInteracting = false; }, 5000); });
                map.on('zoomstart', function() { isUserInteracting = true; clearTimeout(interactionTimeout); });
                map.on('zoomend', function() { interactionTimeout = setTimeout(function() { isUserInteracting = false; }, 5000); });
            }

            function updateMapData(newPoints) {
                if (!map || !newPoints || !newPoints.length) return;

                markers.forEach(function(m) { map.removeLayer(m); });
                markers = [];
                if (routeLine) { map.removeLayer(routeLine); routeLine = null; }

                var latLngs = newPoints.map(function(p) { return [p.latitude, p.longitude]; });

                newPoints.forEach(function(point) {
                    var marker = L.marker([point.latitude, point.longitude], {
                        icon: createMarkerIcon(point.type)
                    }).addTo(map).bindPopup(createPopupContent(point), { maxWidth: 260, className: 'spm-popup-wrapper' });
                    markers.push(marker);
                });

                if (latLngs.length > 1) {
                    routeLine = L.polyline(latLngs, {
                        color: '#1e40af',
                        weight: 4,
                        opacity: 0.85,
                        dashArray: '10, 8',
                        lineCap: 'round',
                        lineJoin: 'round'
                    }).addTo(map);

                    for (var i = 0; i < latLngs.length - 1; i++) {
                        var midLat = (latLngs[i][0] + latLngs[i + 1][0]) / 2;
                        var midLng = (latLngs[i][1] + latLngs[i + 1][1]) / 2;
                        var angle = Math.atan2(latLngs[i + 1][1] - latLngs[i][1], latLngs[i + 1][0] - latLngs[i][0]) * 180 / Math.PI;
                        var arrowIcon = L.divIcon({
                            html: '<div class="spm-arrow" style="transform:rotate(' + (-angle + 90) + 'deg)">&#9650;</div>',
                            className: 'spm-arrow-wrapper',
                            iconSize: [16, 16],
                            iconAnchor: [8, 8]
                        });
                        L.marker([midLat, midLng], { icon: arrowIcon, interactive: false }).addTo(map);
                    }

                    if (!isUserInteracting) {
                        map.fitBounds(routeLine.getBounds(), { padding: [40, 40], animate: true, duration: 0.8 });
                    }
                } else {
                    if (!isUserInteracting) {
                        map.setView(latLngs[0], 13, { animate: true, duration: 0.8 });
                    }
                }
            }

            function pollTrackingData() {
                fetch('/tracking/' + encodeURIComponent(trackingNumber) + '/live')
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.error) return;
                        var statusEl = document.getElementById('live-status-text');
                        if (statusEl) statusEl.textContent = data.status + ' \u00B7 Last updated: ' + data.last_updated;
                        var refreshEl = document.getElementById('live-refresh-text');
                        if (refreshEl) refreshEl.textContent = 'Updated ' + data.last_updated;
                        if (!map && data.has_points && data.points) {
                            initMap(data.points);
                        } else if (map && data.has_points && data.points) {
                            updateMapData(data.points);
                        }
                    })
                    .catch(function() {});
            }

            document.addEventListener('DOMContentLoaded', function() {
                if (initialPoints.length) {
                    initMap(initialPoints);
                } else {
                    showUnavailable(true);
                }
                setInterval(pollTrackingData, 15000);

                var resizeTimer;
                window.addEventListener('resize', function() {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(function() {
                        if (map) map.invalidateSize();
                    }, 200);
                });
            });
        })();
        </script>
        @endif
    </x-dashboard-shell>
@endsection