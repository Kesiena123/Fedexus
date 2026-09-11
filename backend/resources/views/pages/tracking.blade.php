@extends('layouts.app')
@section('title', $shipment ? 'Track ' . $shipment->tracking_number : 'Track a Shipment')
@if($shipment)
@push('head')
<style>
    [x-cloak] { display: none !important; }
    .tracking-fade-in { animation: trackingFadeIn 0.5s cubic-bezier(0.22,1,0.36,1) both; }
    .tracking-fade-in-delay { animation: trackingFadeIn 0.5s cubic-bezier(0.22,1,0.36,1) 0.1s both; }
    .tracking-fade-in-delay2 { animation: trackingFadeIn 0.5s cubic-bezier(0.22,1,0.36,1) 0.2s both; }
    .tracking-fade-in-delay3 { animation: trackingFadeIn 0.5s cubic-bezier(0.22,1,0.36,1) 0.3s both; }
    @keyframes trackingFadeIn {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .progress-progress-step-dot { transition: all 0.3s ease; }
    .progress-progress-step-dot.active { transform: scale(1.15); }
    @media print {
        .no-print { display: none !important; }
        body { background: white !important; }
        .print-break { page-break-before: always; }
    }
</style>
@endpush
@endif
@php
use App\Support\ShipmentStatus;
use App\Support\AppSettings;
use App\Services\LocationCoordinateResolver;
use Illuminate\Support\Str;

$coordinateResolver = app(LocationCoordinateResolver::class);
$displayStatus = $shipment ? ShipmentStatus::toCode($shipment->status) : null;
$statusLabel = $shipment ? ShipmentStatus::toDisplay($shipment->status) : null;
$statusColor = $shipment ? ShipmentStatus::color($displayStatus) : null;
$allEvents = $shipment ? $shipment->trackingEvents->sortByDesc('occurred_at') : collect();
$events = $allEvents->values();
$mapPoints = collect();

if ($shipment) {
    $senderMeta = $shipment->metadata['sender'] ?? [];
    $receiverMeta = $shipment->metadata['receiver'] ?? [];
    $costMeta = $shipment->metadata['cost'] ?? [];
    $shipmentDescription = $shipment->metadata['description'] ?? '';
    $shipmentPriority = $shipment->metadata['priority'] ?? '';
    $shipmentType = $shipment->metadata['shipment_type'] ?? $shipment->metadata['type'] ?? '';
    $shippingMethod = $shipment->metadata['shipping_method'] ?? $shipment->metadata['method'] ?? '';
    $photoAttachments = ($shipment->attachments ?? collect())->filter(fn($a) => $a->isVisibleOnTracking());

    $uniqueLocationEvents = $events->values()
        ->filter(function ($event) use ($events) {
            $idx = $events->search($event);
            if ($idx === 0) return true;
            $prev = $events[$idx - 1];
            $sameLocation = strtolower(trim($event->location ?? '')) === strtolower(trim($prev->location ?? ''));
            $sameStatus = strtolower($event->status) === strtolower($prev->status);
            return !($sameLocation && $sameStatus);
        })->values();

    $latestEvent = $events->first();
    $currentDbStatus = $shipment->status;
    $latestEventStatus = $latestEvent ? strtolower($latestEvent->status) : null;
    $currentStatusAlreadyShown = $latestEvent && strtolower($currentDbStatus) === $latestEventStatus;
    $latestLocation = $latestEvent->location ?? ($shipment->origin_address['city'] ?? $shipment->origin_address['address'] ?? '');
    $latestOccurredAt = $latestEvent ? $latestEvent->occurred_at : now();

    if (!$currentStatusAlreadyShown) {
        $currentEntry = (object)[
            'status' => $currentDbStatus,
            'location' => $latestLocation,
            'description' => 'Current shipment status.',
            'occurred_at' => $latestOccurredAt,
            'latitude' => $latestEvent->latitude ?? null,
            'longitude' => $latestEvent->longitude ?? null,
        ];
        $uniqueLocationEvents = collect([$currentEntry])->merge($uniqueLocationEvents)->values();
    }

    $routeMapPoints = ($shipment->routePoints ?? collect())
        ->map(function ($point) use ($coordinateResolver) {
            $coords = ($point->latitude !== null && $point->longitude !== null)
                ? ['latitude' => (float)$point->latitude, 'longitude' => (float)$point->longitude]
                : $coordinateResolver->resolveLocation((string)$point->location, $point->country_code);
            if (!$coords) return null;
            return ['label'=>$point->label,'location'=>$point->location,'latitude'=>$coords['latitude'],'longitude'=>$coords['longitude'],'type'=>$point->type];
        })->filter();

    $latestEventId = optional(($shipment->trackingEvents ?? collect())->first())->id;
    $eventMapPoints = ($shipment->trackingEvents ?? collect())->reverse()
        ->map(function ($event) use ($coordinateResolver, $latestEventId) {
            $coords = ($event->latitude !== null && $event->longitude !== null)
                ? ['latitude' => (float)$event->latitude, 'longitude' => (float)$event->longitude]
                : $coordinateResolver->resolveLocation((string)$event->location, $event->country_code);
            if (!$coords) return null;
            return ['label'=>$event->id===$latestEventId?'Current Location':ShipmentStatus::toDisplay($event->status),'location'=>$event->location,'latitude'=>$coords['latitude'],'longitude'=>$coords['longitude'],'type'=>$event->id===$latestEventId?'current':'transit'];
        })->filter();

    $mapPoints = collect($routeMapPoints)->merge($eventMapPoints)
        ->unique(fn($p)=>$p['latitude'].'|'.$p['longitude'].'|'.$p['label'])->values();

    if ($mapPoints->isEmpty()) {
        if ($shipment->origin_address) {
            $coords = $coordinateResolver->resolveAddress($shipment->origin_address);
            if ($coords) {
                $mapPoints->push([
                    'label' => 'Origin',
                    'location' => $shipment->origin_address['city'] ?? ($shipment->origin_address['address'] ?? ''),
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'type' => 'origin',
                ]);
            }
        }
        if ($shipment->destination_address) {
            $coords = $coordinateResolver->resolveAddress($shipment->destination_address);
            if ($coords) {
                $mapPoints->push([
                    'label' => 'Destination',
                    'location' => $shipment->destination_address['city'] ?? ($shipment->destination_address['address'] ?? ''),
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'type' => 'destination',
                ]);
            }
        }
    }
}
@endphp

@section('content')
<div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-6 sm:py-10" x-data="{ trackingInput:'', searching:false, copied:false }">

@if($shipment)
    @php
        $origin = $shipment->origin_address;
        $destination = $shipment->destination_address;
        $originCity = is_array($origin) ? ($origin['city'] ?? '') : '';
        $originState = is_array($origin) ? ($origin['state'] ?? '') : '';
        $originCountry = is_array($origin) ? ($origin['country'] ?? $origin['country_code'] ?? '') : '';
        $originAddress = is_array($origin) ? ($origin['address'] ?? '') : '';
        $destCity = is_array($destination) ? ($destination['city'] ?? '') : '';
        $destState = is_array($destination) ? ($destination['state'] ?? '') : '';
        $destCountry = is_array($destination) ? ($destination['country'] ?? $destination['country_code'] ?? '') : '';
        $destAddress = is_array($destination) ? ($destination['address'] ?? '') : '';
        $packages = $shipment->metadata['packages'] ?? [];
        $paymentRequests = ($shipment->paymentRequests ?? collect())->where('is_active', true)->where('is_archived', false)->whereIn('status', ['payment_required','payment_initiated','awaiting_verification']);
        $delivered = $shipment->status === 'delivered';
        $cancelled = in_array($shipment->status, ['cancelled','rejected']);
        $onHold = $shipment->status === 'paused';
        $timelineSteps = ShipmentStatus::timelineSteps();
        $currentStepIdx = $displayStatus ? array_search($displayStatus, $timelineSteps) : -1;
        if ($currentStepIdx === false) $currentStepIdx = -1;
    @endphp

    {{-- ============================================ --}}
    {{-- SECTION 3: STATUS HERO --}}
    {{-- ============================================ --}}
    <div class="tracking-fade-in overflow-hidden rounded-3xl @switch($statusColor) @case('emerald') bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-500 @break @case('red') bg-gradient-to-br from-red-600 via-red-500 to-rose-500 @break @case('amber') bg-gradient-to-br from-amber-500 via-amber-400 to-orange-400 @break @default bg-gradient-to-br from-navy-900 via-azure-700 to-azure-600 @endswitch">
        <div class="relative px-6 py-8 sm:px-10 sm:py-10">
            <div class="absolute inset-0 opacity-10" style="background-image:url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23fff' fill-opacity='.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)"></div>
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-5">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-sm">
                        @if($delivered)
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        @elseif($cancelled)
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
                        @elseif($onHold)
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="8" x2="8" y1="8" y2="16"/><line x1="16" x2="16" y1="8" y2="16"/></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        @endif
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-sm">
                                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>
                                {{ $statusLabel }}
                            </span>
                            @if($shipmentPriority)
                                <span class="inline-flex items-center rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-sm">{{ $shipmentPriority }}</span>
                            @endif
                        </div>
                        <p class="mt-2 text-xs font-medium text-white/70 uppercase tracking-widest">Tracking Number</p>
                        <h1 class="mt-1 font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ $shipment->tracking_number }}</h1>
                        @if(($shipment->trackingEvents->first())?->occurred_at)
                            <p class="mt-2 text-sm text-white/80">Last updated: {{ $shipment->trackingEvents->first()->occurred_at->format('F j, Y') }} at {{ $shipment->trackingEvents->first()->occurred_at->format('g:i A') }}</p>
                        @endif
                        @if($delivered && $shipment->delivered_at)
                            <p class="mt-1 text-sm text-white/80">Delivered {{ $shipment->delivered_at->format('F j, Y \a\t g:i A') }}</p>
                        @elseif($shipment->estimated_delivery_at)
                            <p class="mt-1 text-sm text-white/80">Estimated delivery: {{ $shipment->estimated_delivery_at->format('F j, Y') }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 no-print">
                    <button @click="navigator.clipboard.writeText('{{ $shipment->tracking_number }}'); copied=true; setTimeout(()=>copied=false, 2000)" class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20">
                        <template x-if="!copied"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></template>
                        <template x-if="copied"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-emerald-300"><polyline points="20 6 9 17 4 12"/></svg></template>
                        <span x-text="copied?'Copied!':'Copy'"></span>
                    </button>
                    <a href="{{ route('receipt', $shipment->tracking_number) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Receipt
                    </a>
                    <button onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                        Print
                    </button>
                    <a href="{{ route('tracking') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        New Search
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- SECTION 4: PROGRESS STEPPER --}}
    {{-- ============================================ --}}
    @if(!$cancelled)
        @php
            $progressSteps = ShipmentStatus::timelineSteps();
            $stepIndex = $displayStatus ? array_search($displayStatus, $progressSteps) : -1;
            if ($stepIndex === false) $stepIndex = -1;
            $totalSteps = count($progressSteps) - 1;
            $pct = $stepIndex >= 0 ? round(($stepIndex / $totalSteps) * 100) : 0;
        @endphp
        <div class="tracking-fade-in-d1 mt-6 rounded-2xl border border-navy-100 bg-white px-6 py-5 shadow-card">
            {{-- Desktop --}}
            <div class="hidden md:flex items-center w-full">
                @foreach($progressSteps as $i => $stepCode)
                    @php $stepLabel = ShipmentStatus::label($stepCode); @endphp
                    <div class="flex flex-col items-center flex-1">
                        <div class="progress-step-dot flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all
                            @if($i < $stepIndex) bg-emerald-500 border-emerald-500 text-white
                            @elseif($i == $stepIndex) bg-azure-500 border-azure-500 text-white shadow-lg shadow-azure-500/30 active
                            @else bg-white border-navy-200 text-navy-300 @endif">
                            @if($i < $stepIndex)
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            @elseif($i == $stepIndex)
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"/></svg>
                            @else
                                <div class="h-2 w-2 rounded-full bg-navy-300"></div>
                            @endif
                        </div>
                        <p class="mt-2 text-[11px] font-semibold text-center leading-tight @if($i <= $stepIndex) text-navy-900 @else text-navy-300 @endif">{{ $stepLabel }}</p>
                    </div>
                    @if(!$loop->last)
                        <div class="mx-0.5 h-0.5 flex-1 @if($i < $stepIndex) bg-emerald-400 @elseif($i == $stepIndex) bg-gradient-to-r from-azure-400 to-navy-200 @else bg-navy-100 @endif" style="margin-bottom:20px"></div>
                    @endif
                @endforeach
            </div>
            {{-- Tablet --}}
            <div class="hidden sm:flex md:hidden items-center w-full">
                @foreach($progressSteps as $i => $stepCode)
                    <div class="flex flex-col items-center flex-1">
                        <div class="progress-step-dot flex h-8 w-8 items-center justify-center rounded-full border-2 transition-all
                            @if($i < $stepIndex) bg-emerald-500 border-emerald-500 text-white
                            @elseif($i == $stepIndex) bg-azure-500 border-azure-500 text-white shadow-lg shadow-azure-500/30 active
                            @else bg-white border-navy-200 text-navy-300 @endif">
                            @if($i < $stepIndex)
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            @elseif($i == $stepIndex)
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"/></svg>
                            @else
                                <div class="h-1.5 w-1.5 rounded-full bg-navy-300"></div>
                            @endif
                        </div>
                    </div>
                    @if(!$loop->last)
                        <div class="mx-0.5 h-0.5 flex-1 @if($i < $stepIndex) bg-emerald-400 @elseif($i == $stepIndex) bg-gradient-to-r from-azure-400 to-navy-200 @else bg-navy-100 @endif" style="margin-bottom:18px"></div>
                    @endif
                @endforeach
            </div>
            {{-- Mobile --}}
            <div class="sm:hidden w-full">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-navy-900">{{ ShipmentStatus::label($stepIndex >= 0 ? $progressSteps[$stepIndex] : 'CREATED') }}</span>
                    <span class="text-sm font-bold text-azure-600">{{ $pct }}%</span>
                </div>
                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-navy-100">
                    <div class="h-full rounded-full bg-azure-500 transition-all duration-700" style="width:{{ $pct }}%"></div>
                </div>
                <div class="mt-2 flex justify-between text-[10px] text-slate-400">
                    <span>{{ ShipmentStatus::label($progressSteps[0]) }}</span>
                    <span>{{ ShipmentStatus::label(end($progressSteps)) }}</span>
                </div>
            </div>
        </div>
    @endif

    @if($onHold)
        <div class="tracking-fade-in-d1 mt-4 rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-amber-600"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div><p class="font-bold text-amber-900">Shipment On Hold</p><p class="text-sm text-amber-700">Your shipment is currently on hold. Updates will appear in the timeline below.</p></div>
            </div>
        </div>
    @endif
    @if($cancelled)
        <div class="tracking-fade-in-d1 mt-4 rounded-2xl border border-red-200 bg-gradient-to-r from-red-50 to-rose-50 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-red-600"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg></div>
                <div><p class="font-bold text-red-900">Shipment Cancelled</p><p class="text-sm text-red-700">This shipment has been cancelled. Please contact support for assistance.</p></div>
            </div>
        </div>
    @endif

    {{-- ORIGIN -> DESTINATION --}}
    <div class="tracking-fade-in-d1 mt-6 rounded-2xl border border-navy-100 bg-white px-6 py-5 shadow-card">
        <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
            <div class="flex-1 text-center sm:text-left">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Origin</p>
                <p class="mt-1 font-display text-lg font-bold text-navy-900">{{ $originCity ?: '—' }}{{ $originCountry ? ', ' . strtoupper($originCountry) : '' }}</p>
                @if($originAddress)
                    <p class="mt-0.5 text-sm text-slate-500">{{ $originAddress }}{{ $originState ? ', ' . $originState : '' }}{{ !empty($origin['zip']) ? ' ' . $origin['zip'] : '' }}</p>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <div class="h-px w-8 bg-navy-200 sm:w-16"></div>
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-azure-50"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-azure-600"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></div>
                <div class="h-px w-8 bg-navy-200 sm:w-16"></div>
            </div>
            <div class="flex-1 text-center sm:text-right">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Destination</p>
                <p class="mt-1 font-display text-lg font-bold text-navy-900">{{ $destCity ?: '—' }}{{ $destCountry ? ', ' . strtoupper($destCountry) : '' }}</p>
                @if($destAddress)
                    <p class="mt-0.5 text-sm text-slate-500">{{ $destAddress }}{{ $destState ? ', ' . $destState : '' }}{{ !empty($destination['zip']) ? ' ' . $destination['zip'] : '' }}</p>
                @endif
            </div>
        </div>
    </div>

</div>{{-- close container before full-width map --}}

    {{-- ============================================ --}}
    {{-- FULL-WIDTH LIVE TRACKING MAP --}}
    {{-- ============================================ --}}
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
                            @if($mapPoints->count())
                                {{ $statusLabel }} &middot; Last updated: {{ ($shipment->trackingEvents->first())?->occurred_at?->diffForHumans() ?? 'N/A' }}
                            @else
                                Tracking information will update when location data is available.
                            @endif
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

<div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">

    {{-- ============================================ --}}
    {{-- TWO COLUMN CONTENT --}}
    {{-- ============================================ --}}
    <div class="mt-8 grid gap-6 lg:grid-cols-5">

        {{-- LEFT COLUMN --}}
        <div class="space-y-6 lg:col-span-3">

            {{-- SECTION 6: SHIPMENT HISTORY --}}
            <div class="tracking-fade-in-d2 rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-azure-50 text-azure-600"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                    <h3 class="font-display text-lg font-bold text-navy-900">Shipment History</h3>
                    @if($uniqueLocationEvents->count())
                        <span class="ml-auto text-xs font-medium text-slate-400">{{ $uniqueLocationEvents->count() }} {{ Str::plural('update', $uniqueLocationEvents->count()) }}</span>
                    @endif
                </div>
                @if($uniqueLocationEvents->count())
                    <div class="mt-6 space-y-0">
                        @foreach($uniqueLocationEvents as $event)
                            @php
                                $eventDisplay = ShipmentStatus::toCode($event->status);
                                $eventLabel = ShipmentStatus::toDisplay($event->status);
                                $eventColor = ShipmentStatus::color($eventDisplay);
                                $isFirst = $loop->first;
                                $hasLocation = !empty($event->location);
                            @endphp
                            <div class="relative flex gap-4 pb-8 last:pb-0">
                                <div class="flex flex-col items-center">
                                    <div class="z-10 flex h-9 w-9 items-center justify-center rounded-full border-2
                                        @if($isFirst) @switch($eventColor) @case('emerald') bg-emerald-500 border-emerald-500 @break @case('red') bg-red-500 border-red-500 @break @case('amber') bg-amber-500 border-amber-500 @break @default bg-azure-500 border-azure-500 @endswitch @else bg-white @switch($eventColor) @case('emerald') border-emerald-300 @break @case('red') border-red-300 @break @case('amber') border-amber-300 @break @default border-azure-300 @endswitch @endif">
                                        @if($isFirst)
                                            <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        @else
                                            <div class="h-2.5 w-2.5 rounded-full @switch($eventColor) @case('emerald') bg-emerald-300 @break @case('red') bg-red-300 @break @case('amber') bg-amber-300 @break @default bg-azure-300 @endswitch"></div>
                                        @endif
                                    </div>
                                    @if(!$loop->last)<div class="h-full w-0.5 bg-navy-100"></div>@endif
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-navy-900">{{ $eventLabel }}</h4>
                                            @if($isFirst)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-azure-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-azure-600">
                                                    <span class="h-1 w-1 animate-pulse rounded-full bg-azure-500"></span>
                                                    Current
                                                </span>
                                            @endif
                                        </div>
                                        @if($event->occurred_at)
                                            <span class="text-xs text-slate-400">{{ $event->occurred_at->format('M d, Y \a\t g:i A') }}</span>
                                        @endif
                                    </div>
                                    @if($hasLocation)
                                        <div class="mt-1 flex items-center gap-1.5 text-sm text-slate-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-slate-400"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                            <span class="font-medium">{{ $event->location }}</span>
                                        </div>
                                    @else
                                        <div class="mt-1 flex items-center gap-1.5 text-sm text-slate-400 italic">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                            <span>Status update</span>
                                        </div>
                                    @endif
                                    @if($event->description)
                                        <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $event->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-xl bg-slate-50 p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <p class="mt-3 font-semibold text-navy-900">Awaiting tracking updates</p>
                        <p class="mt-1 text-sm text-slate-500">Status updates and location changes will appear here as the shipment progresses.</p>
                    </div>
                @endif
            </div>

            {{-- SECTION 8: SHIPMENT PHOTOS --}}
            @if($photoAttachments->count())
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card" x-data="{ lightboxOpen:false, lightboxSrc:'', lightboxAlt:'' }">
                    <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-azure-50 text-azure-600"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg></span>
                    <h3 class="font-display text-lg font-bold text-navy-900">Shipment Photos</h3>
                        <span class="ml-auto text-xs font-medium text-slate-400">{{ $photoAttachments->count() }} {{ Str::plural('photo', $photoAttachments->count()) }}</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        @foreach($photoAttachments as $photo)
                            @php
                                $photoUrl = $photo->disk === 'local' ? asset('storage/' . $photo->path) : Storage::disk($photo->disk)->url($photo->path);
                            @endphp
                            <div class="group relative cursor-pointer overflow-hidden rounded-xl border border-navy-100 bg-slate-50 transition-shadow hover:shadow-md"
                                 @click="lightboxSrc='{{ $photoUrl }}'; lightboxAlt='{{ addslashes($photo->original_name ?? 'Shipment photo') }}'; lightboxOpen=true">
                                <img src="{{ $photoUrl }}" alt="{{ $photo->original_name ?? 'Shipment photo' }}" class="aspect-square w-full object-cover transition-transform group-hover:scale-105" loading="lazy" />
                                <div class="absolute inset-0 bg-black/0 transition-colors group-hover:bg-black/10"></div>
                                <div class="absolute inset-x-0 bottom-0 flex items-center justify-center bg-gradient-to-t from-black/50 to-transparent pb-3 pt-8 opacity-0 transition-opacity group-hover:opacity-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    {{-- Lightbox --}}
                    <div x-show="lightboxOpen" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm" @click.self="lightboxOpen=false" @keydown.escape.window="lightboxOpen=false">
                        <button @click="lightboxOpen=false" class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur-sm hover:bg-white/30"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg></button>
                        <img :src="lightboxSrc" :alt="lightboxAlt" class="max-h-[85vh] max-w-full rounded-xl object-contain shadow-2xl" @click.stop />
                        <p x-text="lightboxAlt" class="mt-3 text-sm font-medium text-white/80 text-center" x-show="lightboxAlt"></p>
                    </div>
                </div>
            @endif

            {{-- ROUTE CHECKPOINTS --}}
            @if(($shipment->routePoints ?? collect())->count())
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-azure-50 text-azure-600"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 8 14"/></svg></span>
                        <h3 class="font-display text-lg font-bold text-navy-900">Route Checkpoints</h3>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach($shipment->routePoints as $point)
                            <div class="flex gap-3">
                                <div class="mt-1.5 size-2.5 shrink-0 rounded-full {{ $point->type === 'destination' ? 'bg-emerald-500' : ($point->type === 'origin' ? 'bg-azure-500' : 'bg-gold-500') }}"></div>
                                <div>
                                    <p class="font-semibold text-navy-900">{{ $point->label }}</p>
                                    <p class="text-sm text-slate-600">{{ $point->location }}</p>
                                    @if($point->description)<p class="text-xs text-slate-500 mt-0.5">{{ $point->description }}</p>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- SECTION 9: PAYMENT REQUESTS --}}
            @if($paymentRequests->count())
                <div class="rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 p-6 shadow-card">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg></span>
                        <h3 class="font-display text-lg font-bold text-navy-900">Payment Required</h3>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach($paymentRequests as $request)
                            <div class="rounded-xl border border-amber-200 bg-white p-5 text-sm">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-bold text-navy-900">{{ $request->title }}</p>
                                        <p class="mt-1 text-slate-600">{{ $request->reason }}</p>
                                        <p class="mt-2 font-display text-xl font-bold text-navy-900">{{ number_format((float)$request->amount, 2) }} {{ $request->currency }}</p>
                                    </div>
                                    <a href="{{ route('payment-request.show', $request->secure_token) }}" class="inline-flex min-h-11 items-center rounded-xl bg-azure-500 px-6 text-sm font-bold text-white shadow-glow transition-all hover:bg-azure-600">Pay Securely</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- SECTION 7: SHIPMENT INFO --}}
            <div class="tracking-fade-in-d3 rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-azure-50 text-azure-600"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg></span>
                    <h3 class="font-display text-lg font-bold text-navy-900">Shipment Details</h3>
                </div>
                <div class="mt-4 space-y-3.5 text-sm">
                    <div class="flex items-center justify-between border-b border-navy-50 pb-3">
                        <span class="text-slate-500">Tracking Number</span>
                        <span class="font-bold text-navy-900">{{ $shipment->tracking_number }}</span>
                    </div>
                    @if($shipmentType)
                        <div class="flex items-center justify-between border-b border-navy-50 pb-3">
                            <span class="text-slate-500">Shipment Type</span>
                            <span class="font-semibold text-navy-900">{{ $shipmentType }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between border-b border-navy-50 pb-3">
                        <span class="text-slate-500">Service Type</span>
                        <span class="font-semibold text-navy-900">{{ str_replace('_', ' ', ucwords($shipment->service_level, '_')) }}</span>
                    </div>
                    @if($shippingMethod)
                        <div class="flex items-center justify-between border-b border-navy-50 pb-3">
                            <span class="text-slate-500">Shipping Method</span>
                            <span class="font-semibold text-navy-900">{{ $shippingMethod }}</span>
                        </div>
                    @endif
                    @if($shipmentDescription)
                        <div class="border-b border-navy-50 pb-3">
                            <span class="text-slate-500">Description</span>
                            <p class="mt-1 font-medium text-navy-900">{{ $shipmentDescription }}</p>
                        </div>
                    @endif
                    <div class="flex items-center justify-between border-b border-navy-50 pb-3">
                        <span class="text-slate-500">Created</span>
                        <span class="font-medium text-navy-900">{{ $shipment->created_at->format('M d, Y') }}</span>
                    </div>
                    @if($shipment->estimated_delivery_at)
                        <div class="flex items-center justify-between border-b border-navy-50 pb-3">
                            <span class="text-slate-500">Est. Delivery</span>
                            <span class="font-semibold text-navy-900">{{ $shipment->estimated_delivery_at->format('M d, Y') }}</span>
                        </div>
                    @endif
                    @if($shipment->delivered_at)
                        <div class="flex items-center justify-between border-b border-navy-50 pb-3">
                            <span class="text-slate-500">Delivered</span>
                            <span class="font-semibold text-emerald-600">{{ $shipment->delivered_at->format('M d, Y') }}</span>
                        </div>
                    @endif
                    @if($shipment->weight_kg)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Weight</span>
                            <span class="font-medium text-navy-900">{{ $shipment->weight_kg }} kg</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- SENDER --}}
            <div class="tracking-fade-in-d3 rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-azure-50 text-azure-600"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    <h3 class="font-display text-lg font-bold text-navy-900">Sender</h3>
                </div>
                <div class="mt-4 space-y-3 text-sm">
                    @if($shipment->sender_name)
                        <div class="font-semibold text-navy-900">{{ $shipment->sender_name }}</div>
                    @endif
                    @if(!empty($senderMeta['company']))
                        <div class="flex items-center gap-2 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-slate-400"><rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                            <span>{{ $senderMeta['company'] }}</span>
                        </div>
                    @endif
                    @if(!empty($senderMeta['email']))
                        <div class="flex items-center gap-2 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-slate-400"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <span>{{ $senderMeta['email'] }}</span>
                        </div>
                    @endif
                    @if(!empty($senderMeta['phone']))
                        <div class="flex items-center gap-2 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-slate-400"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/></svg>
                            <span>{{ $senderMeta['phone'] }}</span>
                        </div>
                    @endif
                    @if(!empty($originAddress))
                        <div class="flex items-start gap-2 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 shrink-0 text-slate-400"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>{{ $originAddress }}{{ $originState ? ', ' . $originState : '' }}{{ !empty($origin['postal_code']) ? ' ' . $origin['postal_code'] : '' }}</span>
                        </div>
                    @endif
                    @if(!empty($originCity))
                        <div class="flex items-center gap-2 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-slate-400"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>{{ $originCity }}{{ $originState ? ', ' . $originState : '' }}{{ $originCountry ? ', ' . strtoupper($originCountry) : '' }}{{ !empty($origin['postal_code']) ? ' ' . $origin['postal_code'] : '' }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- RECEIVER --}}
            <div class="tracking-fade-in-d3 rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    <h3 class="font-display text-lg font-bold text-navy-900">Receiver</h3>
                </div>
                <div class="mt-4 space-y-3 text-sm">
                    @if($shipment->recipient_name)
                        <div class="font-semibold text-navy-900">{{ $shipment->recipient_name }}</div>
                    @endif
                    @if(!empty($receiverMeta['company']))
                        <div class="flex items-center gap-2 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-slate-400"><rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                            <span>{{ $receiverMeta['company'] }}</span>
                        </div>
                    @endif
                    @if(!empty($receiverMeta['email']))
                        <div class="flex items-center gap-2 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-slate-400"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <span>{{ $receiverMeta['email'] }}</span>
                        </div>
                    @endif
                    @if(!empty($receiverMeta['phone']))
                        <div class="flex items-center gap-2 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-slate-400"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/></svg>
                            <span>{{ $receiverMeta['phone'] }}</span>
                        </div>
                    @endif
                    @if(!empty($destAddress))
                        <div class="flex items-start gap-2 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 shrink-0 text-slate-400"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>{{ $destAddress }}{{ $destState ? ', ' . $destState : '' }}{{ !empty($destination['postal_code']) ? ' ' . $destination['postal_code'] : '' }}</span>
                        </div>
                    @endif
                    @if(!empty($destCity))
                        <div class="flex items-center gap-2 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-slate-400"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>{{ $destCity }}{{ $destState ? ', ' . $destState : '' }}{{ $destCountry ? ', ' . strtoupper($destCountry) : '' }}{{ !empty($destination['postal_code']) ? ' ' . $destination['postal_code'] : '' }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- COST SUMMARY --}}
            @if(!empty($costMeta['shipping_cost']) || $shipment->declared_value)
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gold-300/20 text-gold-600"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                        <h3 class="font-display text-lg font-bold text-navy-900">Cost Summary</h3>
                    </div>
                    <div class="mt-4 space-y-3 text-sm">
                        @if(!empty($costMeta['shipping_cost']))
                            <div class="flex items-center justify-between"><span class="text-slate-500">Shipping Cost</span><span class="font-bold text-navy-900">{{ number_format((float)$costMeta['shipping_cost'], 2) }}</span></div>
                        @endif
                        @if(!empty($costMeta['handling_fee']) && (float)$costMeta['handling_fee'] > 0)
                            <div class="flex items-center justify-between"><span class="text-slate-500">Handling Fee</span><span class="font-medium text-navy-900">{{ number_format((float)$costMeta['handling_fee'], 2) }}</span></div>
                        @endif
                        @if(!empty($costMeta['insurance']) && (float)$costMeta['insurance'] > 0)
                            <div class="flex items-center justify-between"><span class="text-slate-500">Insurance</span><span class="font-medium text-navy-900">{{ number_format((float)$costMeta['insurance'], 2) }}</span></div>
                        @endif
                        @if(!empty($costMeta['customs_fee']) && (float)$costMeta['customs_fee'] > 0)
                            <div class="flex items-center justify-between"><span class="text-slate-500">Customs Fee</span><span class="font-medium text-navy-900">{{ number_format((float)$costMeta['customs_fee'], 2) }}</span></div>
                        @endif
                        @if(!empty($costMeta['tax']) && (float)$costMeta['tax'] > 0)
                            <div class="flex items-center justify-between"><span class="text-slate-500">Tax</span><span class="font-medium text-navy-900">{{ number_format((float)$costMeta['tax'], 2) }}</span></div>
                        @endif
                        @if(!empty($costMeta['discount']) && (float)$costMeta['discount'] > 0)
                            <div class="flex items-center justify-between"><span class="text-slate-500">Discount</span><span class="font-medium text-emerald-600">-{{ number_format((float)$costMeta['discount'], 2) }}</span></div>
                        @endif
                        @if(!empty($costMeta['additional_charges']) && (float)$costMeta['additional_charges'] > 0)
                            <div class="flex items-center justify-between"><span class="text-slate-500">Additional Charges</span><span class="font-medium text-navy-900">{{ number_format((float)$costMeta['additional_charges'], 2) }}</span></div>
                        @endif
                        @if($shipment->declared_value)
                            <div class="flex items-center justify-between border-t border-navy-100 pt-3 mt-1">
                                <span class="font-bold text-navy-900">Total Declared Value</span>
                                <span class="font-display text-lg font-bold text-navy-900">{{ number_format((float)$shipment->declared_value, 2) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- PAYMENT HISTORY --}}
            @php
                $allPaymentRequests = $shipment->paymentRequests ?? collect();
                $visiblePaymentRequests = $allPaymentRequests->filter(fn ($pr) => ($pr->is_active && !$pr->is_archived) || in_array($pr->status, ['paid', 'verified']));
                $completedPayments = $visiblePaymentRequests->filter(fn ($pr) => $pr->status === 'paid');
                $pendingPayments = $visiblePaymentRequests->filter(fn ($pr) => $pr->status !== 'paid');
            @endphp
            @if($visiblePaymentRequests->isNotEmpty())
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg></span>
                        <h3 class="font-display text-lg font-bold text-navy-900">
                            Payment History
                            <span class="ml-2 text-sm font-normal text-slate-400">({{ $completedPayments->count() }} completed, {{ $pendingPayments->count() }} unpaid)</span>
                        </h3>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse($visiblePaymentRequests as $pr)
                            @php
                                $isPaid = $pr->status === 'paid';
                                $prBg = $isPaid ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200';
                                $badgeBg = $isPaid ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700';
                                $statusLabels = [
                                    'draft' => 'Draft',
                                    'payment_required' => 'Payment Required',
                                    'payment_initiated' => 'Payment Initiated',
                                    'awaiting_verification' => 'Awaiting Verification',
                                    'processing' => 'Processing',
                                    'paid' => 'Paid',
                                    'verified' => 'Verified',
                                    'failed' => 'Failed',
                                    'cancelled' => 'Cancelled',
                                    'expired' => 'Expired',
                                    'refunded' => 'Refunded',
                                ];
                                $badgeText = $statusLabels[$pr->status] ?? ucfirst($pr->status);
                                $transaction = $pr->transactions->first();
                                $prMethod = $pr->metadata['allowed_methods'][0] ?? $pr->requested_method ?? 'N/A';
                            @endphp
                            <div class="rounded-xl border {{ $prBg }} p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-navy-900">{{ $pr->title }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $pr->reason }}</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $badgeBg }}">{{ $badgeText }}</span>
                                </div>
                                <div class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600">
                                    <span>Amount: <strong class="text-navy-900">{{ $pr->currency }} {{ number_format((float)$pr->amount, 2) }}</strong></span>
                                    <span>Method: <strong class="text-navy-900">{{ ucfirst(str_replace('_', ' ', $prMethod)) }}</strong></span>
                                    @if($pr->paid_at)
                                        <span>Paid: <strong class="text-navy-900">{{ $pr->paid_at->format('M d, Y H:i') }}</strong></span>
                                    @endif
                                    @if($pr->due_at && !$isPaid)
                                        <span>Due: <strong class="text-navy-900">{{ $pr->due_at->format('M d, Y') }}</strong></span>
                                    @endif
                                    @if($transaction && $transaction->provider_reference)
                                        <span>Ref: <strong class="font-mono text-navy-900">{{ $transaction->provider_reference }}</strong></span>
                                    @endif
                                </div>
                                @if(!$isPaid && $pr->secure_token)
                                    <div class="mt-3">
                                        <a href="{{ route('payment-request.show', $pr->secure_token) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-[#0d5368] hover:underline">
                                            Pay Now
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-slate-400">No payment requests found.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- PARCEL INFO --}}
            @if(count($packages))
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-azure-50 text-azure-600"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg></span>
                        <h3 class="font-display text-lg font-bold text-navy-900">Packages ({{ count($packages) }})</h3>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach($packages as $i => $pkg)
                            @php
                                $packageName = $pkg['package_name'] ?? $pkg['packageName'] ?? 'Package ' . ($i + 1);
                                $packageType = $pkg['package_type'] ?? $pkg['packageType'] ?? null;
                                $weightKg = $pkg['weight_kg'] ?? $pkg['weightKg'] ?? null;
                                $dimensions = $pkg['dimensions_cm'] ?? [];
                                $lengthCm = $dimensions['length'] ?? $pkg['lengthCm'] ?? null;
                                $widthCm = $dimensions['width'] ?? $pkg['widthCm'] ?? null;
                                $heightCm = $dimensions['height'] ?? $pkg['heightCm'] ?? null;
                            @endphp
                            <div class="rounded-xl border border-navy-100 bg-slate-50 p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-azure-100 text-xs font-bold text-azure-700">{{ $i + 1 }}</span>
                                        <p class="font-semibold text-navy-900">{{ $packageName }}</p>
                                    </div>
                                    @if($packageType)
                                        <span class="inline-flex items-center rounded-full bg-navy-100 px-2.5 py-0.5 text-xs font-semibold text-navy-600">{{ $packageType }}</span>
                                    @endif
                                </div>
                                @if(!empty($pkg['description']))
                                    <p class="mt-1 text-sm text-slate-500">{{ $pkg['description'] }}</p>
                                @endif
                                <div class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600">
                                    @if(!empty($pkg['quantity']))<span>Qty: <strong class="text-navy-900">{{ $pkg['quantity'] }}</strong></span>@endif
                                    @if($weightKg)<span>Weight: <strong class="text-navy-900">{{ $weightKg }} kg</strong></span>@endif
                                    @if($lengthCm && $widthCm && $heightCm)<span>Size: <strong class="text-navy-900">{{ $lengthCm }} x {{ $widthCm }} x {{ $heightCm }} cm</strong></span>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>

@else
    {{-- ============================================ --}}
    {{-- SECTION 2: TRACKING SEARCH HERO --}}
    {{-- ============================================ --}}
    <div class="tracking-fade-in">
        <div class="rounded-3xl border border-slate-200 bg-white px-6 py-12 sm:px-12 sm:py-16 text-center shadow-card">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-azure-200 bg-azure-50 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-azure-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"/><polygon points="12 15 17 21 7 21 12 15"/></svg>
                Real-time shipment intelligence
            </span>
            <h1 class="mt-5 font-display text-3xl font-bold text-navy-900 sm:text-5xl">Track Your Shipment</h1>
            <p class="mx-auto mt-3 max-w-lg text-slate-500">Enter your tracking number below to view live status, route updates, and delivery progress.</p>

            <form method="POST" action="{{ route('tracking') }}" @submit="searching=true" class="mx-auto mt-8 flex flex-col gap-3 sm:flex-row sm:max-w-xl">
                @csrf
                <div class="relative flex-1">
                    <input type="text" name="tracking_number" x-model="trackingInput" placeholder="e.g. FDX-2026-ABC12345" required
                        class="h-14 w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 pr-12 text-navy-900 placeholder:text-slate-400 focus:border-azure-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-azure-400/20" />
                    <svg class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <button type="submit" class="inline-flex h-14 items-center justify-center gap-2 rounded-2xl bg-azure-500 px-8 text-sm font-bold text-white shadow-glow transition-all hover:bg-azure-600 hover:shadow-lift">
                    <template x-if="!searching"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></template>
                    <template x-if="searching"><svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg></template>
                    <span x-text="searching?'Searching...':'Track Shipment'"></span>
                </button>
            </form>
        </div>

        @if($error)
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-6 py-4 text-sm text-red-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-red-500"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg></div>
                    <div><p class="font-bold text-red-800">Shipment not found</p><p class="text-red-600">No shipment matches that tracking number. Please check and try again.</p></div>
                </div>
            </div>
        @endif

        @if(!$trackingNumber && !$error)
            <div class="mt-12 grid gap-6 sm:grid-cols-3">
                <div class="rounded-2xl border border-navy-100 bg-white p-6 text-center shadow-card">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-azure-50 text-azure-600"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></div>
                    <h3 class="mt-4 font-display text-base font-bold text-navy-900">Real-Time Tracking</h3>
                    <p class="mt-1.5 text-sm text-slate-500">See your shipment's live location and route on an interactive map.</p>
                </div>
                <div class="rounded-2xl border border-navy-100 bg-white p-6 text-center shadow-card">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                    <h3 class="mt-4 font-display text-base font-bold text-navy-900">Status Updates</h3>
                    <p class="mt-1.5 text-sm text-slate-500">Get detailed status updates at every milestone of your shipment.</p>
                </div>
                <div class="rounded-2xl border border-navy-100 bg-white p-6 text-center shadow-card">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-gold-300/20 text-gold-600"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg></div>
                    <h3 class="mt-4 font-display text-base font-bold text-navy-900">Secure Payments</h3>
                    <p class="mt-1.5 text-sm text-slate-500">Pay duties, taxes, and fees securely directly from the tracking page.</p>
                </div>
            </div>
        @endif
    </div>
@endif
</div>

@if($shipment)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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

            var arrowDecorator = [];
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

{{-- LIVE CHAT WIDGET --}}
@if($shipment)
@php
    $chatSession = session('guest_chat_' . $shipment->id);
    $trackingForJs = e($shipment->tracking_number);
@endphp
<div x-data="chatWidget('{{ $trackingForJs }}')" x-init="initWidget()" x-cloak>
    {{-- Floating Button --}}
    <button @click="open = true; if(!started && !loading) checkExisting()" x-show="!open" class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-[#0d5368] text-white shadow-lg transition-all hover:bg-[#0b4658] hover:scale-105">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    </button>

    {{-- Chat Panel --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" class="fixed bottom-6 right-6 z-50 flex w-96 flex-col rounded-2xl border border-slate-200 bg-white shadow-2xl" style="height: 600px; max-height: 80vh;">

        {{-- Header --}}
        <div class="flex items-center justify-between rounded-t-2xl bg-[#0d5368] px-5 py-3 text-white">
            <div>
                <h3 class="text-sm font-bold">Live Chat Support</h3>
                <p class="text-xs text-white/70" x-text="tracking"></p>
            </div>
            <button @click="open = false" class="rounded-full p-1 hover:bg-white/20">&times;</button>
        </div>

        {{-- Guest Info Form --}}
        <div x-show="!started" class="flex flex-1 flex-col justify-center p-5">
            <h4 class="text-lg font-bold text-slate-900">Need Help?</h4>
            <p class="mt-1 text-sm text-slate-500">Our support team is ready to assist you with this shipment.</p>
            <form @submit.prevent="startChat" class="mt-5 space-y-3">
                <input x-model="guestName" required class="min-h-11 w-full rounded-lg border border-slate-200 px-3 text-sm" placeholder="Your full name">
                <input x-model="guestEmail" required type="email" class="min-h-11 w-full rounded-lg border border-slate-200 px-3 text-sm" placeholder="Your email address">
                <input x-model="guestPhone" class="min-h-11 w-full rounded-lg border border-slate-200 px-3 text-sm" placeholder="Phone (optional)">
                <textarea x-model="firstMessage" required rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="How can we help you?"></textarea>
                <button type="submit" :disabled="sending" class="w-full rounded-lg bg-[#0d5368] py-2.5 text-sm font-bold text-white hover:bg-[#0b4658] disabled:opacity-40" x-text="sending ? 'Starting...' : 'Start Chat'"></button>
            </form>
            <div x-show="error" x-text="error" class="mt-3 text-sm text-red-600"></div>
        </div>

        {{-- Chat Messages --}}
        <div x-show="started" class="flex min-h-0 flex-1 flex-col overflow-hidden">
            <div x-ref="chatMessages" class="flex-1 space-y-3 overflow-y-auto p-4">
                <template x-if="welcomeMessage">
                    <div class="rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-700">
                        <p class="text-xs font-semibold text-[#0d5368]">System</p>
                        <p class="mt-0.5" x-text="welcomeMessage"></p>
                    </div>
                </template>
                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.sender_type === 'guest' ? 'ml-12' : 'mr-12'">
                        <div :class="msg.sender_type === 'guest' ? 'bg-[#0d5368] text-white' : 'bg-slate-100 text-slate-800'" class="rounded-xl px-4 py-2.5 shadow-sm max-w-[85%]">
                            <p class="text-xs font-semibold opacity-70" x-text="msg.sender_type === 'guest' ? 'You' : 'Support Agent'"></p>
                            <p class="mt-0.5 whitespace-pre-wrap text-sm" x-text="msg.body"></p>
                            <div x-show="msg.attachments && msg.attachments.length" class="mt-2 space-y-1">
                                <template x-for="att in (msg.attachments || [])" :key="att.name">
                                    <a :href="att.url" target="_blank" :class="msg.sender_type === 'guest' ? 'bg-white/20 text-white hover:bg-white/30' : 'bg-black/10 text-slate-800 hover:bg-black/20'" class="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-semibold">
                                        <span x-text="att.name"></span>
                                    </a>
                                </template>
                            </div>
                            <div class="mt-1 flex items-center gap-2 text-[10px] opacity-60">
                                <span x-text="formatTime(msg.created_at)"></span>
                                <span x-show="msg.sender_type === 'guest'" x-text="msg.read_at ? 'Read' : msg.delivery_status === 'delivered' ? 'Delivered' : 'Sent'"></span>
                            </div>
                        </div>
                    </div>
                </template>
                <div x-show="isTyping" class="flex items-center gap-2 text-sm text-slate-400">
                    <span class="flex gap-0.5"><span class="size-1.5 animate-bounce rounded-full bg-slate-400"></span><span class="size-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay:0.1s"></span><span class="size-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay:0.2s"></span></span>
                    Support is typing...
                </div>
            </div>

            {{-- Closed Notice --}}
            <div x-show="isClosed" class="border-t border-slate-200 p-4 text-center text-sm text-slate-400">
                This conversation is closed.
                <button @click="reopenChat" class="ml-1 font-semibold text-[#0d5368] hover:underline">Reopen</button>
            </div>

            {{-- Reply Box --}}
            <div x-show="!isClosed" class="border-t border-slate-200 p-3">
                <form @submit.prevent="sendMessage" class="flex gap-2">
                    <div class="relative flex-1">
                        <textarea x-model="replyText" @keydown.enter.prevent="sendMessage" rows="1" class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Type a message..." style="min-height: 38px;"></textarea>
                        <div class="mt-1 flex items-center gap-2">
                            <label class="flex cursor-pointer items-center gap-1 text-xs text-slate-400 hover:text-[#0d5368]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                Attach
                                <input type="file" @change="addFile" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt" class="hidden">
                            </label>
                            <span x-show="replyFiles.length" class="text-xs text-slate-500" x-text="replyFiles.length + ' file(s)'"></span>
                        </div>
                    </div>
                    <button type="submit" :disabled="!replyText.trim() && !replyFiles.length" class="shrink-0 rounded-lg bg-[#0d5368] px-4 py-2 text-sm font-bold text-white hover:bg-[#0b4658] disabled:opacity-40">Send</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function chatWidget(tracking) {
        return {
            tracking: tracking,
            open: false,
            started: false,
            loading: false,
            sending: false,
            error: '',
            token: null,
            conversationId: null,

            guestName: '{{ $chatSession['name'] ?? '' }}',
            guestEmail: '{{ $chatSession['email'] ?? '' }}',
            guestPhone: '{{ $chatSession['phone'] ?? '' }}',
            firstMessage: '',
            replyText: '',
            replyFiles: [],
            messages: [],
            welcomeMessage: '',
            isTyping: false,
            pollTimer: null,

            get isClosed() {
                if (!this.messages.length && !this.conversationId) return false;
                return this.conversationStatus === 'closed' || this.conversationStatus === 'archived';
            },

            conversationStatus: 'open',

            initWidget() {
                @if($chatSession && $chatSession['token'])
                    this.token = '{{ $chatSession['token'] }}';
                    this.open = true;
                    this.resumeChat();
                @endif
            },

            async checkExisting() {
                this.loading = true;
                try {
                    const res = await fetch('{{ route('chat.info') }}?tracking=' + this.tracking);
                    if (!res.ok) { this.loading = false; return; }
                    const data = await res.json();
                    if (data.existing_conversation && data.existing_conversation.token) {
                        this.token = data.existing_conversation.token;
                        this.open = true;
                        this.resumeChat();
                    } else {
                        this.loading = false;
                    }
                } catch (e) { this.loading = false; }
            },

            async startChat() {
                if (!this.guestName.trim() || !this.guestEmail.trim() || !this.firstMessage.trim()) return;
                this.sending = true;
                this.error = '';
                try {
                    const res = await fetch('{{ route('chat.start') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({
                            tracking_number: this.tracking,
                            guest_name: this.guestName,
                            guest_email: this.guestEmail,
                            guest_phone: this.guestPhone,
                            message: this.firstMessage
                        })
                    });
                    if (!res.ok) {
                        const err = await res.json();
                        this.error = err.errors?.message?.[0] || err.error || 'Failed to start chat.';
                        this.sending = false;
                        return;
                    }
                    const data = await res.json();
                    this.token = data.token;
                    this.conversationId = data.conversation?.id;
                    this.messages = data.conversation?.messages || [];
                    this.welcomeMessage = data.welcome_message || '';
                    this.started = true;
                    this.startPolling();
                    this.$nextTick(() => this.scrollChat());
                } catch (e) {
                    this.error = 'Connection error. Please try again.';
                }
                this.sending = false;
                this.loading = false;
            },

            async resumeChat() {
                try {
                    const res = await fetch('{{ route('chat.resume') }}?token=' + this.token);
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data.conversation) {
                        this.conversationId = data.conversation.id;
                        this.conversationStatus = data.conversation.chat_status;
                        this.messages = data.conversation.messages || [];
                        this.started = true;
                        this.open = true;
                        this.startPolling();
                        this.$nextTick(() => this.scrollChat());
                    }
                } catch (e) {}
                this.loading = false;
            },

            startPolling() {
                if (this.pollTimer) clearInterval(this.pollTimer);
                this.pollTimer = setInterval(() => this.poll(), 4000);
            },

            async poll() {
                if (!this.token) return;
                try {
                    const res = await fetch('{{ route('chat.resume') }}?token=' + this.token);
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data.conversation) {
                        const oldLen = this.messages.length;
                        this.conversationStatus = data.conversation.chat_status;
                        this.messages = data.conversation.messages || [];
                        if (this.messages.length > oldLen) {
                            this.$nextTick(() => this.scrollChat());
                        }
                    }
                } catch (e) {}
            },

            addFile(e) {
                this.replyFiles = [...e.target.files];
                e.target.value = '';
            },

            async sendMessage() {
                if (!this.replyText.trim() && !this.replyFiles.length) return;
                const formData = new FormData();
                formData.append('token', this.token);
                formData.append('message', this.replyText);
                for (const file of this.replyFiles) formData.append('attachments[]', file);
                try {
                    const res = await fetch('{{ route('chat.reply') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: formData
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.messages.push(data.message);
                        this.replyText = '';
                        this.replyFiles = [];
                        this.$nextTick(() => this.scrollChat());
                    }
                } catch (e) {}
            },

            async reopenChat() {
                try {
                    const res = await fetch('{{ route('chat.reply') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ token: this.token, message: 'I would like to reopen this conversation.' })
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.conversationStatus = 'waiting_for_admin';
                        this.messages.push(data.message);
                        this.$nextTick(() => this.scrollChat());
                    }
                } catch (e) {}
            },

            scrollChat() {
                const el = this.$refs.chatMessages;
                if (el) el.scrollTop = el.scrollHeight;
            },

            formatTime(t) {
                if (!t) return '';
                const d = new Date(t);
                const now = new Date();
                const isToday = d.toDateString() === now.toDateString();
                const time = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                if (isToday) return time;
                const yesterday = new Date(now); yesterday.setDate(yesterday.getDate() - 1);
                if (d.toDateString() === yesterday.toDateString()) return 'Yesterday ' + time;
                return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' ' + time;
            },

            formatSize(bytes) {
                if (!bytes) return '';
                if (bytes < 1024) return bytes + 'B';
                return (bytes / 1024).toFixed(1) + 'KB';
            }
        };
    }
</script>
@endif
@endsection
