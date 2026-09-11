@extends('layouts.app')

@section('title', 'Services')

@section('content')
    <section class="relative overflow-hidden bg-navy-gradient text-white">
        <div class="absolute inset-0 bg-hero-radial"></div>
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid items-center gap-10 py-16 lg:grid-cols-[0.9fr_1.1fr] lg:py-24">
            <div>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/5 px-3 py-1 text-xs font-semibold text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-400"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                    Enterprise services
                </span>
                <h1 class="mt-5 font-display text-4xl font-bold leading-tight tracking-tight md:text-5xl">Shipping and logistics services for every mile</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-navy-100">Courier, freight, warehousing, international clearance, returns, and delivery operations in one premium workflow.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ url('/admin/login') }}"><x-ui.button variant="accent" size="lg">Admin shipment control</x-ui.button></a>
                    <a href="{{ url('/rates') }}"><x-ui.button variant="outline" size="lg" class="border-white/30 bg-transparent text-white hover:bg-white/10">Compare rates</x-ui.button></a>
                </div>
            </div>
            <div class="overflow-hidden rounded-3xl border border-white/15 shadow-lift">
                <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1200&q=80" alt="Warehouse team moving packed shipments" width="1200" height="360" class="h-[360px] w-full object-cover" />
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Service portfolio</p>
                <h2 class="mt-3 font-display text-3xl font-bold text-navy-900 sm:text-4xl">A connected operating model</h2>
                <p class="mt-4 text-base text-slate-600">Each service shares the same approval, tracking, payment, notification, and support foundation.</p>
            </div>
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach([
                    ['Domestic courier', 'Same-day, overnight, and economy delivery with driver dispatch.', url('/rates'), 'package'],
                    ['International shipping', 'Customs, duty estimates, trade docs, and destination hub visibility.', url('/services/international'), 'globe'],
                    ['Freight', 'Pallet, air, ocean, and cross-border freight workflows.', url('/contact'), 'ship'],
                    ['Warehouse management', 'Inbound receiving, bin locations, sorting, and outbound staging.', url('/warehouse'), 'warehouse'],
                    ['Returns', 'Customer returns, labels, claims, and reverse logistics.', url('/support'), 'refresh'],
                    ['Bulk logistics', 'High-volume imports, manifests, and account-level analytics.', url('/contact'), 'boxes'],
                ] as [$title, $body, $href, $icon])
                    <a href="{{ $href }}" class="group block h-full">
                        <div class="h-full rounded-2xl border border-navy-100 bg-white p-6 shadow-card transition-all hover:-translate-y-1 hover:border-azure-200 hover:shadow-lift">
                            <span class="grid h-12 w-12 place-items-center rounded-xl bg-azure-50 text-azure-600 transition-colors group-hover:bg-azure-500 group-hover:text-white">
                                @if($icon === 'package')
                                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/><path d="M12 22V12"/><polyline points="3.29 7 12 12 20.71 7"/><path d="m7.5 4.27 9 5.15"/></svg>
                                @elseif($icon === 'globe')
                                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" x2="22" y1="12" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                @elseif($icon === 'ship')
                                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 21c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1 .6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M19.38 20A11.6 11.6 0 0 0 21 14l-9-4-9 4c0 2.9.94 5.34 2.81 7.76"/><path d="M19 13V7a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v6"/><path d="M12 10v4"/><path d="M12 2v3"/></svg>
                                @elseif($icon === 'warehouse')
                                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/><path d="M6 18h12"/><path d="M6 14h12"/><rect width="12" height="12" x="6" y="10"/></svg>
                                @elseif($icon === 'refresh')
                                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
                                @elseif($icon === 'boxes')
                                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l3 1.8a2 2 0 0 0 2.06 0L12 19v-5.5l-5-3-4.03 2.42Z"/><path d="m7 16.5-4.74-2.87"/><path d="m7 16.5 4-2.5"/><path d="M7 16.5V21"/><path d="M12 13.5V19l3.97 2.3a2 2 0 0 0 2.06 0l3-1.8a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L17 10.5l-5 3Z"/><path d="m17 16.5-4.74-2.87"/><path d="m17 16.5 4-2.5"/><path d="M17 16.5V21"/><path d="M7 5 2 7.5l5 3 5-3-5-3Z"/><path d="M7 5v6"/><path d="M12 7.5v3.5l5 3V10.5l-5-3Z"/></svg>
                                @endif
                            </span>
                            <h2 class="mt-5 font-display text-xl font-bold text-navy-900">{{ $title }}</h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $body }}</p>
                            <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-azure-700">
                                Explore service
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-center">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Lifecycle controls</p>
                <h2 class="mt-3 font-display text-3xl font-bold leading-tight text-navy-900 sm:text-4xl">Operations stay controlled from request to delivery</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">Administrators create shipments, generate tracking numbers, unlock payments, update locations, and manage exceptions. Customers view progress after admin setup.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach(['Admin approval before activation', 'Unique QR and barcode tracking', 'Google Maps route visibility', 'Sequential milestone payments', 'Email, in-app, and chat notifications', 'Audit logged custody events'] as $item)
                    <div class="flex items-center gap-3 rounded-2xl border border-navy-100 bg-slate-50 p-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-emerald-600"><path d="M20 6 9 17l-5-5"/></svg>
                        <span class="text-sm font-semibold text-navy-800">{{ $item }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-navy-100 bg-navy-gradient text-white p-8 shadow-card">
                <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4 text-gold-400"><circle cx="6" cy="19" r="3"/><path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"/><circle cx="18" cy="5" r="3"/></svg>
                        <h2 class="font-display text-2xl font-bold">Need a custom enterprise route?</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-navy-100">Configure hub routing, warehouse transfers, driver assignments, payment milestones, and support escalation for complex lanes.</p>
                    </div>
                    <a href="{{ url('/contact') }}"><x-ui.button variant="accent" size="lg">Talk to logistics team</x-ui.button></a>
                </div>
            </div>
        </div>
    </section>
@endsection
