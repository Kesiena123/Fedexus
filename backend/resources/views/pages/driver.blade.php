@extends('layouts.app')
@section('title', 'Driver Portal')
@php($hideHeaderFooter = true)
@section('content')
    <x-dashboard-shell title="Driver portal" audience="operations">
        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <div class="rounded-2xl border border-navy-100 bg-white shadow-card overflow-hidden">
                <div class="p-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 font-display text-xl font-bold text-navy-900">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                            Today&apos;s route
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">Assigned pickups, deliveries, navigation, scan actions, and proof capture.</p>
                    </div>
                    <x-ui.button variant="primary" size="sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                        Start route
                    </x-ui.button>
                </div>
                <div class="px-6 pb-6 grid gap-4">
                    @foreach([
                        ['Pickup', 'Austin Warehouse', 'Ready 09:00', '3 parcels'],
                        ['Delivery', '440 Market Street', 'Window 11:00-13:00', 'Signature required'],
                        ['Delivery', '1200 Lakeview Ave', 'Window 14:00-16:00', 'Photo proof'],
                    ] as [$type, $address, $window, $note])
                        <div class="rounded-2xl border border-navy-100 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <x-ui.badge variant="{{ $type === 'Pickup' ? 'emerald' : 'azure' }}">{{ $type }}</x-ui.badge>
                                    <h3 class="mt-2 font-bold text-navy-900">{{ $address }}</h3>
                                    <p class="mt-1 text-sm text-slate-600">{{ $window }} - {{ $note }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.button variant="outline" size="sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12h.01"/><path d="M12 21v-1"/></svg>
                                        QR
                                    </x-ui.button>
                                    <x-ui.button variant="outline" size="sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><line x1="7" x2="17" y1="12" y2="12"/></svg>
                                        Barcode
                                    </x-ui.button>
                                    <x-ui.button size="sm">{{ $loop->first ? 'Confirm pickup' : 'Complete stop' }}</x-ui.button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4">
                @foreach([
                    ['map', 'Route map', 'Optimized stop sequence, live GPS marker, geofenced scans, and exception capture.'],
                    ['camera', 'Delivery photos', 'Capture package condition, doorstep evidence, and location timestamp.'],
                    ['signature', 'Digital signature', 'Recipient release with signature, timestamp, and final delivery event.'],
                    ['package-check', 'Proof of delivery', 'Photo, signature, GPS, and driver notes are attached to the shipment.'],
                ] as [$icon, $title, $body])
                    <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                        @if($icon === 'map')
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M10 18h4"/><path d="M10 14h4"/></svg>
                        @elseif($icon === 'camera')
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                        @elseif($icon === 'signature')
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M11 21H3"/><path d="m9 13-2 2 2 2"/><path d="M13 8V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3"/><path d="M19 3 9 13"/></svg>
                        @elseif($icon === 'package-check')
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="m16 16 2 2 4-4"/><path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"/><path d="m7.5 4.27 9 5.15"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" x2="12" y1="22" y2="12"/></svg>
                        @endif
                        <h3 class="font-display text-lg font-bold text-navy-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </x-dashboard-shell>
@endsection