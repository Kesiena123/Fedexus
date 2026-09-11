@extends('layouts.app')

@section('title', 'International Shipping')

@section('content')
    <section class="relative overflow-hidden bg-navy-gradient text-white">
        <div class="absolute inset-0 bg-hero-radial"></div>
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid items-center gap-10 py-16 lg:grid-cols-[0.95fr_1.05fr] lg:py-24">
            <div>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/5 px-3 py-1 text-xs font-semibold text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-400"><circle cx="12" cy="12" r="10"/><line x1="2" x2="22" y1="12" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    Cross-border shipping
                </span>
                <h1 class="mt-5 font-display text-4xl font-bold leading-tight tracking-tight md:text-5xl">International shipping with customs-ready visibility</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-navy-100">Move parcels and freight across borders with document capture, duty support, export scans, destination hub milestones, and final-mile delivery.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ url('/contact') }}"><x-ui.button variant="accent" size="lg">Contact operations</x-ui.button></a>
                    <a href="{{ url('/rates') }}"><x-ui.button variant="outline" size="lg" class="border-white/30 bg-transparent text-white hover:bg-white/10">Estimate duties</x-ui.button></a>
                </div>
            </div>
            <div class="overflow-hidden rounded-3xl border border-white/15 shadow-lift">
                <img src="https://images.unsplash.com/photo-1524522173746-f628baad3644?auto=format&fit=crop&w=1200&q=80" alt="Cargo aircraft loading international freight" width="1200" height="360" class="h-[360px] w-full object-cover" />
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Customs workflow</p>
                <h2 class="mt-3 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Built for regulated movement</h2>
                <p class="mt-4 text-base text-slate-600">Structured data and audit history keep cross-border shipments moving with fewer surprises.</p>
            </div>
            <div class="mt-10 grid gap-4 md:grid-cols-3">
                @foreach([['Classify goods', 1], ['Collect documents', 2], ['Process duties', 3], ['Scan export', 4], ['Clear destination', 5], ['Release delivery', 6]] as [$step, $index])
                    <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                        <p class="text-xs font-bold uppercase tracking-wider text-azure-600">Step {{ $index }}</p>
                        <h2 class="mt-2 font-display text-xl font-bold text-navy-900">{{ $step }}</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Operations can review documents, update checkpoints, and notify customers at each milestone.</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid gap-6 lg:grid-cols-4">
            @foreach([
                ['file-text', 'Commercial invoice', 'Capture item values, HS codes, and export notes.'],
                ['shield', 'Admin review', 'Tracking numbers are generated after approval only.'],
                ['plane', 'Transit routing', 'Monitor export gateways, air legs, and destination hubs.'],
                ['scan', 'Scan custody', 'QR and barcode scans keep warehouse events searchable.'],
            ] as [$icon, $title, $body])
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    @if($icon === 'file-text')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    @elseif($icon === 'shield')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    @elseif($icon === 'plane')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
                    @elseif($icon === 'scan')
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><line x1="7" x2="17" y1="12" y2="12"/></svg>
                    @endif
                    <h3 class="font-bold text-navy-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                <div class="grid gap-5 md:grid-cols-[auto_1fr_auto] md:items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-600"><path d="M20 6 9 17l-5-5"/></svg>
                    <div>
                        <h2 class="font-display text-xl font-bold text-navy-900">Ready for staged international payments</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Booking, collection, processing, destination, and final delivery installments unlock sequentially as administrators verify each milestone.</p>
                    </div>
                    <a href="{{ url('/tracking') }}" class="inline-flex items-center gap-2 font-semibold text-azure-600 hover:text-azure-700">
                        View tracking experience
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
