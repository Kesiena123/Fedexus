@extends('layouts.app')

@section('title', 'Staff Operations')

@php($hideHeaderFooter = true)
@section('content')
    <x-dashboard-shell title="Staff operations" audience="operations">
        <div class="grid gap-4 md:grid-cols-3">
            @foreach([
                ['package-search', 'Tracking management', 'Review scans, exceptions, route changes, and recipient requests.'],
                ['warehouse', 'Warehouse assignments', 'Receive, sort, stage, and hand off packages by route and hub.'],
                ['clipboard', 'Processing checks', 'Approve customs, pickup confirmation, destination hub, and delivery release.'],
            ] as [$icon, $title, $body])
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    @if($icon === 'package-search')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/><path d="M12 22V12"/><path d="m9.5 4.27 9 5.15"/><circle cx="17.5" cy="17.5" r="2.5"/><path d="M19.5 19.5 22 22"/></svg>
                    @elseif($icon === 'warehouse')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/><path d="M6 18h12"/><path d="M6 14h12"/><rect width="12" height="12" x="6" y="10"/></svg>
                    @elseif($icon === 'clipboard')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg>
                    @endif
                    <h2 class="font-display text-xl font-bold text-navy-900">{{ $title }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $body }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
            <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                <h2 class="font-display text-xl font-bold text-navy-900">Processing queue</h2>
                <div class="mt-4 grid gap-4">
                    @foreach([
                        ['Customs document review', 'FDX-2026-8K3P91QZ', 'International Processing'],
                        ['Pickup confirmation', 'TRK-94QK2081VL', 'Pickup Scheduled'],
                        ['Destination scan exception', 'LOG-20KQ9XTR88', 'Destination Hub'],
                    ] as [$task, $tracking, $status])
                        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-navy-100 bg-slate-50 p-4">
                            <div>
                                <x-ui.badge variant="{{ $loop->first ? 'gold' : ($loop->last ? 'outline' : 'navy') }}">{{ $status }}</x-ui.badge>
                                <h3 class="mt-2 font-bold text-navy-900">{{ $task }}</h3>
                                <p class="mt-1 text-sm text-slate-600">{{ $tracking }}</p>
                            </div>
                            <x-ui.button variant="outline" size="sm">Review</x-ui.button>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4">
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-emerald-600"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="m9 15 2 2 4-4"/></svg>
                    <h3 class="font-bold text-navy-900">Document checks</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Commercial invoice, customs values, proof uploads, and release records.</p>
                </div>
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-gold-600"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <h3 class="font-bold text-navy-900">Location updates</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Manual checkpoint updates, hub changes, route exceptions, and GPS event corrections.</p>
                </div>
            </div>
        </div>
    </x-dashboard-shell>
@endsection