@extends('layouts.app')

@section('title', 'Warehouse Management')

@php($hideHeaderFooter = true)
@section('content')
    <x-dashboard-shell title="Warehouse management" audience="operations">
        <div class="grid gap-4 md:grid-cols-3">
            @foreach([
                ['warehouse', 'Inbound receiving', 'Scan arrivals, assign bins, and flag damage.'],
                ['boxes', 'Sort lanes', 'Group shipments by hub, driver route, service level, and cutoff time.'],
                ['scan', 'Outbound staging', 'Confirm container, manifest, and handoff event.'],
            ] as [$icon, $title, $body])
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    @if($icon === 'warehouse')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/><path d="M6 18h12"/><path d="M6 14h12"/><rect width="12" height="12" x="6" y="10"/></svg>
                    @elseif($icon === 'boxes')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l3 1.8a2 2 0 0 0 2.06 0L12 19v-5.5l-5-3-4.03 2.42Z"/><path d="m7 16.5-4.74-2.87"/><path d="m7 16.5 4-2.5"/><path d="M7 16.5V21"/><path d="M12 13.5V19l3.97 2.3a2 2 0 0 0 2.06 0l3-1.8a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L17 10.5l-5 3Z"/><path d="m17 16.5-4.74-2.87"/><path d="m17 16.5 4-2.5"/><path d="M17 16.5V21"/><path d="M7 5 2 7.5l5 3 5-3-5-3Z"/><path d="M7 5v6"/><path d="M12 7.5v3.5l5 3V10.5l-5-3Z"/></svg>
                    @elseif($icon === 'scan')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><line x1="7" x2="17" y1="12" y2="12"/></svg>
                    @endif
                    <h2 class="font-display text-xl font-bold text-navy-900">{{ $title }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $body }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
            <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                <h2 class="font-display text-xl font-bold text-navy-900">Active sort lanes</h2>
                <div class="mt-4 grid gap-4">
                    @foreach([
                        ['International Priority', '42', 'Cutoff 18:00', 'Heathrow Hub'],
                        ['Domestic Express', '118', 'Cutoff 16:30', 'Dallas Sort'],
                        ['Freight Pallets', '16', 'Cutoff 21:00', 'Memphis Linehaul'],
                    ] as [$lane, $count, $cutoff, $destination])
                        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-navy-100 bg-slate-50 p-4">
                            <div>
                                <x-ui.badge variant="navy">{{ $destination }}</x-ui.badge>
                                <h3 class="mt-2 font-bold text-navy-900">{{ $lane }}</h3>
                                <p class="mt-1 text-sm text-slate-600">{{ $count }} shipments - {{ $cutoff }}</p>
                            </div>
                            <x-ui.button variant="outline" size="sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                Transfer
                            </x-ui.button>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-navy-100 bg-navy-gradient text-white p-6 shadow-card">
                <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4 text-gold-400"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><line x1="7" x2="17" y1="12" y2="12"/></svg>
                <h2 class="font-display text-xl font-bold">Scan station</h2>
                <p class="mt-3 text-sm leading-6 text-navy-100">Barcode and QR scans update incoming, outgoing, inventory, and transfer events with warehouse custody.</p>
                <x-ui.button variant="accent" class="mt-6 w-full">Open scanner</x-ui.button>
            </div>
        </div>
    </x-dashboard-shell>
@endsection