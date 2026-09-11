@extends('layouts.app')
@section('title', 'Tracking Management')
@php
$hideHeaderFooter = true;
function cityAddr($addr) { return ($addr['city'] ?? '') . ', ' . ($addr['state'] ?? $addr['country'] ?? ''); }
function money($val) { return '$'.number_format((float)($val ?? 0), 2); }
@endphp
@section('content')
    <x-dashboard-shell title="Tracking Management">
        <div x-data="{
            openDetails: {},
            search: '{{ $search ?? '' }}',
            statusFilter: '{{ $status ?? '' }}',
            dateFrom: '',
            dateTo: '',
            showEventModal: false,
            eventShipmentId: null,
            eventShipmentTracking: '',
            eventForm: {
                status: '',
                location: '',
                description: '',
                occurred_at: '',
                checkpoint_label: '',
                warehouse_name: '',
                admin_notes: '',
                latitude: '',
                longitude: '',
                country_code: '',
            },
            submitting: false,
            submitError: '',
            submitSuccess: '',
            toggleDetails(key) {
                this.openDetails[key] = !this.openDetails[key];
            },
            openEventModal(id, tracking) {
                this.eventShipmentId = id;
                this.eventShipmentTracking = tracking;
                this.eventForm = { status: '', location: '', description: '', occurred_at: '', checkpoint_label: '', warehouse_name: '', admin_notes: '', latitude: '', longitude: '', country_code: '' };
                this.submitError = '';
                this.submitSuccess = '';
                this.showEventModal = true;
            },
            submitEvent() {
                this.submitting = true;
                this.submitError = '';
                this.submitSuccess = '';
                const payload = {};
                for (const [k, v] of Object.entries(this.eventForm)) {
                    if (v !== '') payload[k] = v;
                }
                fetch('/api/admin/shipments/' + this.eventShipmentId + '/tracking-events', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
                    body: JSON.stringify(payload),
                })
                .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
                .then(({ ok, data }) => {
                    if (!ok) { this.submitError = data.message || JSON.stringify(data.errors || data); this.submitting = false; return; }
                    this.submitSuccess = 'Tracking event added successfully';
                    this.submitting = false;
                    setTimeout(() => { this.showEventModal = false; location.reload(); }, 800);
                })
                .catch(() => { this.submitError = 'Network error'; this.submitting = false; });
            },
            applyFilters() {
                const params = new URLSearchParams();
                if (this.search) params.set('search', this.search);
                if (this.statusFilter) params.set('status', this.statusFilter);
                if (this.dateFrom) params.set('date_from', this.dateFrom);
                if (this.dateTo) params.set('date_to', this.dateTo);
                window.location.search = params.toString();
            },
            clearFilters() {
                window.location.search = '';
            }
        }">
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-white/60 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-500">Active Shipments</p>
                        <div class="rounded-xl bg-azure-100 p-2.5 text-azure-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                        </div>
                    </div>
                    <p class="mt-3 font-display text-3xl font-bold text-navy-950">{{ $trackedShipments->total() }}</p>
                    <p class="mt-1 text-xs text-slate-400">Total shipments with tracking</p>
                </div>
                <div class="rounded-2xl border border-white/60 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-500">In Transit</p>
                        <div class="rounded-xl bg-gold-100 p-2.5 text-gold-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"/><polygon points="6 9 12 3 18 9 10 9"/></svg>
                        </div>
                    </div>
                    <p class="mt-3 font-display text-3xl font-bold text-navy-950">{{ $trackedShipments->whereIn('status', ['picked_up', 'warehouse_processing', 'international_processing', 'destination_hub', 'out_for_delivery'])->count() }}</p>
                    <p class="mt-1 text-xs text-slate-400">Currently moving through network</p>
                </div>
                <div class="rounded-2xl border border-white/60 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-500">Delayed / Exception</p>
                        <div class="rounded-xl bg-red-100 p-2.5 text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                        </div>
                    </div>
                    <p class="mt-3 font-display text-3xl font-bold text-navy-950">{{ $trackedShipments->whereIn('status', ['paused', 'exception'])->count() }}</p>
                    <p class="mt-1 text-xs text-slate-400">Requires attention</p>
                </div>
                <div class="rounded-2xl border border-white/60 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-500">Delivered</p>
                        <div class="rounded-xl bg-emerald-100 p-2.5 text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                    </div>
                    <p class="mt-3 font-display text-3xl font-bold text-navy-950">{{ $trackedShipments->where('status', 'delivered')->count() }}</p>
                    <p class="mt-1 text-xs text-slate-400">Completed deliveries</p>
                </div>
            </div>

            <div class="mb-6 rounded-2xl border border-white/60 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[200px] flex-1">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Search</label>
                        <input type="text" x-model="search" @@keydown.enter="applyFilters" placeholder="Tracking number, sender, recipient..." class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900 placeholder:text-slate-400">
                    </div>
                    <div class="w-full sm:w-44">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Status</label>
                        <select x-model="statusFilter" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $st)
                                <option value="{{ $st }}">{{ str_replace('_', ' ', ucwords($st, '_')) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full sm:w-40">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">From</label>
                        <input type="date" x-model="dateFrom" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900">
                    </div>
                    <div class="w-full sm:w-40">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">To</label>
                        <input type="date" x-model="dateTo" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900">
                    </div>
                    <div class="flex gap-2">
                        <button @@click="applyFilters" class="focus-ring inline-flex items-center gap-2 rounded-full bg-azure-500 px-5 py-2.5 text-sm font-semibold text-white shadow-glow transition-all hover:bg-azure-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            Search
                        </button>
                        <button @@click="clearFilters" class="focus-ring inline-flex items-center gap-2 rounded-full border border-navy-200 bg-white px-5 py-2.5 text-sm font-semibold text-navy-700 transition-all hover:border-navy-300">
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800">{{ session('error') }}</div>
            @endif

            <div class="overflow-x-auto rounded-2xl border border-white/60 bg-white shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="p-4">Tracking #</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Sender</th>
                            <th class="p-4">Recipient</th>
                            <th class="p-4 hidden md:table-cell">Route</th>
                            <th class="p-4 hidden lg:table-cell">Service</th>
                            <th class="p-4 hidden lg:table-cell">Latest Event</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trackedShipments as $shipment)
                            <tr class="border-b border-slate-100 transition-colors hover:bg-slate-50/50">
                                <td class="p-4">
                                    <button @@click="toggleDetails('{{ $shipment->id }}')" class="flex items-center gap-2 font-semibold text-navy-900 hover:text-azure-600">
                                        <span x-show="!openDetails['{{ $shipment->id }}']"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400"><polyline points="9 18 15 12 9 6"/></svg></span>
                                        <span x-show="openDetails['{{ $shipment->id }}']"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-azure-500"><polyline points="6 9 12 15 18 9"/></svg></span>
                                        <span class="font-mono text-sm">{{ $shipment->tracking_number }}</span>
                                    </button>
                                    <div class="mt-1 flex flex-wrap gap-1.5 md:hidden">
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">{{ cityAddr($shipment->origin_address) }} → {{ cityAddr($shipment->destination_address) }}</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    @php
                                        $displayCode = \App\Support\ShipmentStatus::toCode($shipment->status);
                                        $color = \App\Support\ShipmentStatus::color($displayCode);
                                        $label = \App\Support\ShipmentStatus::toDisplay($shipment->status);
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold
                                        @switch($color)
                                            @case('emerald') bg-emerald-100 text-emerald-700 @break
                                            @case('red') bg-red-100 text-red-700 @break
                                            @case('amber') bg-amber-100 text-amber-700 @break
                                            @case('gold') bg-yellow-100 text-yellow-700 @break
                                            @case('navy') bg-navy-100 text-navy-700 @break
                                            @default bg-azure-100 text-azure-700
                                        @endswitch">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <p class="font-medium text-navy-900">{{ $shipment->sender_name }}</p>
                                </td>
                                <td class="p-4">
                                    <p class="font-medium text-navy-900">{{ $shipment->recipient_name }}</p>
                                </td>
                                <td class="p-4 hidden md:table-cell">
                                    <p class="text-sm text-slate-600">{{ cityAddr($shipment->origin_address) }} → {{ cityAddr($shipment->destination_address) }}</p>
                                </td>
                                <td class="p-4 hidden lg:table-cell">
                                    <span class="text-sm text-slate-600">{{ str_replace('_', ' ', $shipment->service_level) }}</span>
                                </td>
                                <td class="p-4 hidden lg:table-cell max-w-[200px]">
                                    <p class="truncate text-sm text-slate-600" title="{{ $shipment->trackingEvents?->first()?->description ?? '' }}">
                                        {{ $shipment->trackingEvents?->first()?->description ?? 'No events yet' }}
                                    </p>
                                    @if($shipment->trackingEvents?->first()?->occurred_at)
                                        <p class="mt-0.5 text-xs text-slate-400">{{ \Carbon\Carbon::parse($shipment->trackingEvents->first()->occurred_at)->format('M d, H:i') }}</p>
                                    @endif
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @@click="openEventModal({{ $shipment->id }}, '{{ $shipment->tracking_number }}')" class="focus-ring inline-flex items-center gap-1.5 rounded-full border border-navy-200 bg-white px-3.5 py-2 text-xs font-semibold text-navy-700 transition-colors hover:border-azure-400 hover:text-azure-700" title="Add tracking event">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                            <span class="hidden sm:inline">Event</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="openDetails['{{ $shipment->id }}']" x-cloak>
                                <td colspan="8" class="bg-slate-50/70 p-0">
                                    <div class="border-t border-slate-100 px-6 py-6">
                                        <div class="grid gap-8 lg:grid-cols-3">
                                            <div class="lg:col-span-2">
                                                <h4 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-slate-500">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                    Tracking Timeline
                                                    <span class="text-xs font-normal normal-case text-slate-400">({{ $shipment->trackingEvents->count() }} events)</span>
                                                </h4>
                                                @if($shipment->trackingEvents->isNotEmpty())
                                                    <div class="relative pl-8">
                                                        <div class="absolute left-[11px] top-2 h-[calc(100%-16px)] w-0.5 bg-slate-200"></div>
                                                        @foreach($shipment->trackingEvents as $event)
                                                            @php
                                                                $eventDisplayCode = \App\Support\ShipmentStatus::toCode($event->status);
                                                                $eventColor = \App\Support\ShipmentStatus::color($eventDisplayCode);
                                                            @endphp
                                                            <div class="relative mb-5 last:mb-0">
                                                                <div class="absolute -left-[23px] top-1 flex size-[22px] items-center justify-center rounded-full border-2 border-white bg-white shadow-sm
                                                                    @switch($eventColor)
                                                                        @case('emerald') ring-2 ring-emerald-400 @break
                                                                        @case('red') ring-2 ring-red-400 @break
                                                                        @case('amber') ring-2 ring-amber-400 @break
                                                                        @case('gold') ring-2 ring-yellow-400 @break
                                                                        @case('navy') ring-2 ring-navy-400 @break
                                                                        @default ring-2 ring-azure-400
                                                                    @endswitch">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                                                                        @switch($eventColor)
                                                                            @case('emerald') class="text-emerald-500" @break
                                                                            @case('red') class="text-red-500" @break
                                                                            @case('amber') class="text-amber-500" @break
                                                                            @case('gold') class="text-yellow-500" @break
                                                                            @case('navy') class="text-navy-500" @break
                                                                            @default class="text-azure-500"
                                                                        @endswitch>
                                                                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                                                    </svg>
                                                                </div>
                                                                <div class="rounded-xl border border-white/70 bg-white p-4 shadow-sm">
                                                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                                                        <div>
                                                                            <p class="text-sm font-semibold text-navy-900">{{ $event->description }}</p>
                                                                            <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                                                                @if($event->location)
                                                                                    <span class="inline-flex items-center gap-1">
                                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                                                                        {{ $event->location }}
                                                                                    </span>
                                                                                @endif
                                                                                @if($event->occurred_at)
                                                                                    <span class="inline-flex items-center gap-1">
                                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                                                        {{ $event->occurred_at->format('M d, Y H:i') }}
                                                                                    </span>
                                                                                @endif
                                                                                @if($event->checkpoint_label)
                                                                                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600">{{ $event->checkpoint_label }}</span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider
                                                                            @switch($eventColor)
                                                                                @case('emerald') bg-emerald-100 text-emerald-700 @break
                                                                                @case('red') bg-red-100 text-red-700 @break
                                                                                @case('amber') bg-amber-100 text-amber-700 @break
                                                                                @case('gold') bg-yellow-100 text-yellow-700 @break
                                                                                @case('navy') bg-navy-100 text-navy-700 @break
                                                                                @default bg-azure-100 text-azure-700
                                                                            @endswitch">
                                                                            {{ \App\Support\ShipmentStatus::label($eventDisplayCode) }}
                                                                        </span>
                                                                    </div>
                                                                    @if($event->admin_notes)
                                                                        <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50/50 px-3 py-2">
                                                                            <p class="text-xs font-medium text-amber-700">Admin notes:</p>
                                                                            <p class="mt-0.5 text-xs text-amber-600">{{ $event->admin_notes }}</p>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="flex items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white/50 py-8">
                                                        <p class="text-sm text-slate-500">No tracking events recorded yet.</p>
                                                    </div>
                                                @endif
                                                <div class="mt-4">
                                                    <button @@click="openEventModal({{ $shipment->id }}, '{{ $shipment->tracking_number }}')" class="focus-ring inline-flex items-center gap-2 rounded-full bg-azure-500 px-5 py-2.5 text-sm font-semibold text-white shadow-glow transition-all hover:bg-azure-600 hover:-translate-y-0.5">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                                        Add Tracking Event
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="space-y-4">
                                                <div class="rounded-xl border border-white/70 bg-white p-4 shadow-sm">
                                                    <h5 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Shipment Details</h5>
                                                    <dl class="mt-3 space-y-2 text-sm">
                                                        <div class="flex justify-between">
                                                            <dt class="text-slate-500">Weight</dt>
                                                            <dd class="font-medium text-navy-900">{{ $shipment->weight_kg }} kg</dd>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <dt class="text-slate-500">Declared Value</dt>
                                                            <dd class="font-medium text-navy-900">{{ money($shipment->declared_value) }}</dd>
                                                        </div>
                                                        @if($shipment->estimated_delivery_at)
                                                            <div class="flex justify-between">
                                                                <dt class="text-slate-500">Est. Delivery</dt>
                                                                <dd class="font-medium text-navy-900">{{ $shipment->estimated_delivery_at->format('M d, Y') }}</dd>
                                                            </div>
                                                        @endif
                                                        <div class="flex justify-between">
                                                            <dt class="text-slate-500">Created</dt>
                                                            <dd class="font-medium text-navy-900">{{ $shipment->created_at->format('M d, Y') }}</dd>
                                                        </div>
                                                    </dl>
                                                </div>
                                                <div class="rounded-xl border border-white/70 bg-white p-4 shadow-sm">
                                                    <h5 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Addresses</h5>
                                                    <div class="mt-3 space-y-3 text-sm">
                                                        <div>
                                                            <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Origin</p>
                                                            <p class="mt-1 text-navy-900">{{ implode(', ', array_filter([
                                                                $shipment->origin_address['address_line1'] ?? '',
                                                                $shipment->origin_address['city'] ?? '',
                                                                $shipment->origin_address['state'] ?? '',
                                                                $shipment->origin_address['postal_code'] ?? '',
                                                                $shipment->origin_address['country'] ?? '',
                                                            ])) }}</p>
                                                        </div>
                                                        <div>
                                                            <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Destination</p>
                                                            <p class="mt-1 text-navy-900">{{ implode(', ', array_filter([
                                                                $shipment->destination_address['address_line1'] ?? '',
                                                                $shipment->destination_address['city'] ?? '',
                                                                $shipment->destination_address['state'] ?? '',
                                                                $shipment->destination_address['postal_code'] ?? '',
                                                                $shipment->destination_address['country'] ?? '',
                                                            ])) }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-300"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                                        <h3 class="mt-3 text-base font-semibold text-navy-900">No shipments found</h3>
                                        <p class="mt-1 text-sm text-slate-500">Try adjusting your search or filter criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($trackedShipments->hasPages())
                <div class="mt-6">
                    {{ $trackedShipments->links() }}
                </div>
            @endif

            <div x-cloak x-show="showEventModal" class="fixed inset-0 z-50 flex items-center justify-center bg-navy-950/40 backdrop-blur-sm p-4" @@keydown.escape.window="showEventModal = false">
                <div @@click.outside="showEventModal = false" class="w-full max-w-2xl rounded-3xl border border-white/60 bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                        <div>
                            <h3 class="text-lg font-semibold text-navy-950">Add Tracking Event</h3>
                            <p class="mt-0.5 text-sm text-slate-500">Shipment: <span class="font-mono font-semibold text-navy-700" x-text="eventShipmentTracking"></span></p>
                        </div>
                        <button @@click="showEventModal = false" class="rounded-full border border-slate-200 p-2 text-slate-400 hover:border-slate-300 hover:text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
                        </button>
                    </div>
                    <div class="space-y-5 px-6 py-6">
                        <template x-if="submitSuccess">
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" x-text="submitSuccess"></div>
                        </template>
                        <template x-if="submitError">
                            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" x-text="submitError"></div>
                        </template>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Description <span class="text-red-400">*</span></label>
                            <textarea x-model="eventForm.description" rows="2" placeholder="e.g., Package arrived at regional sorting facility" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900 placeholder:text-slate-400"></textarea>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Location</label>
                                <input type="text" x-model="eventForm.location" placeholder="e.g., Memphis, TN" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900 placeholder:text-slate-400">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Status Code</label>
                                <select x-model="eventForm.status" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900">
                                    <option value="">Same as current</option>
                                    @foreach($statuses as $st)
                                        <option value="{{ $st }}">{{ str_replace('_', ' ', ucwords($st, '_')) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Checkpoint Label</label>
                                <input type="text" x-model="eventForm.checkpoint_label" placeholder="e.g., SCAN, ARR, DEP" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900 placeholder:text-slate-400">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Warehouse / Facility</label>
                                <input type="text" x-model="eventForm.warehouse_name" placeholder="e.g., Memphis Super Hub" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900 placeholder:text-slate-400">
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Date/Time</label>
                                <input type="datetime-local" x-model="eventForm.occurred_at" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Latitude</label>
                                <input type="number" step="any" x-model="eventForm.latitude" placeholder="35.1495" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900 placeholder:text-slate-400">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Longitude</label>
                                <input type="number" step="any" x-model="eventForm.longitude" placeholder="-90.0490" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900 placeholder:text-slate-400">
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Country Code</label>
                                <input type="text" x-model="eventForm.country_code" maxlength="2" placeholder="US" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900 placeholder:text-slate-400">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Admin Notes</label>
                            <textarea x-model="eventForm.admin_notes" rows="2" placeholder="Internal notes about this event (optional)" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-2.5 text-sm text-navy-900 placeholder:text-slate-400"></textarea>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4">
                        <button @@click="showEventModal = false" class="focus-ring rounded-full border border-navy-200 px-5 py-2.5 text-sm font-semibold text-navy-700 transition-colors hover:border-navy-300">Cancel</button>
                        <button @@click="submitEvent" ::disabled="submitting || !eventForm.description" class="focus-ring inline-flex items-center gap-2 rounded-full bg-azure-500 px-6 py-2.5 text-sm font-semibold text-white shadow-glow transition-all hover:bg-azure-600 disabled:opacity-60">
                            <svg x-show="submitting" class="animate-spin" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                            <span x-text="submitting ? 'Adding...' : 'Add Event'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-dashboard-shell>
@endsection