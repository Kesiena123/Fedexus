@extends('layouts.app')

@section('title', 'FreightFlow — Global Logistics & Courier Platform')

@section('content')
    <section id="home" aria-label="Homepage hero" class="relative overflow-hidden bg-navy-gradient text-white">
        <div class="absolute inset-0 bg-hero-radial"></div>
        <div class="absolute inset-0 bg-grid-faint bg-[size:40px_40px] opacity-20"></div>
        <div class="relative mx-auto w-full max-w-7xl grid items-center gap-10 px-4 sm:px-6 lg:px-8 py-16 lg:grid-cols-[1.05fr_0.95fr] lg:py-24">
            <div x-data="{ visible: false }" x-init="$nextTick(() => { setTimeout(() => { visible = true; }, 0) })" x-show="visible" x-transition:enter-start="opacity-0 translate-y-6" x-transition:enter-end="opacity-100 translate-y-0" x-transition:enter="transition-all duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/5 px-3 py-1 text-xs font-semibold text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-400"><circle cx="12" cy="12" r="10"/><line x1="2" x2="22" y1="12" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    Enterprise logistics platform
                </span>
                <h1 class="mt-5 font-display text-4xl font-bold leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">
                    Ship globally with confidence,
                    <span class="block bg-gradient-to-r from-azure-400 via-azure-200 to-gold-300 bg-clip-text text-transparent">track every milestone in real time</span>
                </h1>
                <p class="mt-5 max-w-xl text-lg leading-8 text-navy-100">
                    Premium shipping infrastructure for domestic parcels, international freight, and warehouse-led fulfillment.
                </p>
                <div class="mt-8 rounded-2xl bg-white/10 p-2 ring-1 ring-white/20 backdrop-blur">
                    <div class="rounded-xl bg-white p-3 shadow-lift">
                        <x-tracking-search compact />
                    </div>
                </div>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ url('/rates') }}"><x-ui.button variant="primary" size="lg">Shipping Quote</x-ui.button></a>
                    <a href="{{ url('/contact') }}"><x-ui.button variant="outline" size="lg" class="border-white/30 text-white hover:bg-white/10">Contact Sales</x-ui.button></a>
                    <a href="{{ url('/dashboard') }}"><x-ui.button variant="outline" size="lg" class="border-white/30 text-white hover:bg-white/10">Customer Dashboard</x-ui.button></a>
                </div>
            </div>
            <div x-data="{ visible: false }" x-init="$nextTick(() => { setTimeout(() => { visible = true; }, 100) })" x-show="visible" x-transition:enter-start="opacity-0 translate-y-6" x-transition:enter-end="opacity-100 translate-y-0" x-transition:enter="transition-all duration-700 delay-100 ease-[cubic-bezier(0.22,1,0.36,1)]">
                <div class="relative overflow-hidden rounded-3xl border border-white/15 shadow-lift">
                    <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1200&q=80" alt="Container yard with logistics vehicles" width="1200" height="900" class="h-[440px] w-full object-cover" />
                    <div class="absolute bottom-4 left-4 rounded-2xl bg-white/95 px-4 py-3 text-navy-900 shadow-card">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Live route</p>
                        <p class="text-sm font-semibold">Frankfurt Hub → Amsterdam Gateway</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section aria-label="Network statistics" class="border-y border-navy-100 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid grid-cols-2 gap-6 py-8 sm:grid-cols-4">
            @foreach([['220+', 'Countries and territories'], ['38', 'International gateways'], ['99.4%', 'On-time performance'], ['18M', 'Parcels handled monthly']] as [$value, $label])
                <div class="text-center">
                    <p class="font-display text-3xl font-bold text-navy-900">{{ $value }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section id="services" class="py-16 sm:py-20 lg:py-24 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Featured Services</p>
                <h2 class="mt-3 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Featured services for global logistics operations</h2>
                <p class="mt-4 text-base text-slate-600">Configure a delivery program for each shipment class with visibility from dispatch to doorstep.</p>
            </div>
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['Express Domestic', 'Guaranteed time-window deliveries with live scans from pickup to proof of delivery.', 'truck', url('/services')],
                    ['International Priority', 'Cross-border shipping with customs documentation support and proactive exception handling.', 'plane', url('/services/international')],
                    ['Freight & Pallet', 'Palletized and heavyweight logistics with consolidated lanes and hub-level visibility.', 'boxes', url('/services')],
                    ['Warehouse Solutions', 'Inbound receiving, staging, sortation, and outbound orchestration from one network.', 'warehouse', url('/warehouse')],
                ] as [$title, $body, $icon, $href])
                    <a href="{{ $href }}" class="group block rounded-2xl border border-navy-100 bg-white p-6 shadow-card transition-all hover:-translate-y-1 hover:shadow-lift">
                        @if($icon === 'truck')
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-azure-600"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                        @elseif($icon === 'plane')
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-azure-600"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
                        @elseif($icon === 'boxes')
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-azure-600"><path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l3 1.8a2 2 0 0 0 2.06 0L12 19v-5.5l-5-3-4.03 2.42Z"/><path d="m7 16.5-4.74-2.87"/><path d="m7 16.5 4-2.5"/><path d="M7 16.5V21"/><path d="M12 13.5V19l3.97 2.3a2 2 0 0 0 2.06 0l3-1.8a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L17 10.5l-5 3Z"/><path d="m17 16.5-4.74-2.87"/><path d="m17 16.5 4-2.5"/><path d="M17 16.5V21"/><path d="M7 5 2 7.5l5 3 5-3-5-3Z"/><path d="M7 5v6"/><path d="M12 7.5v3.5l5 3V10.5l-5-3Z"/></svg>
                        @elseif($icon === 'warehouse')
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-azure-600"><path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/><path d="M6 18h12"/><path d="M6 14h12"/><rect width="12" height="12" x="6" y="10"/></svg>
                        @endif
                        <h3 class="mt-4 font-display text-lg font-bold text-navy-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $body }}</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-azure-700">
                            Explore
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section id="about" class="py-16 sm:py-20 lg:py-24 bg-slate-50">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Why Choose Us</p>
                <h2 class="mt-3 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Why global businesses choose FreightFlow</h2>
                <p class="mt-4 text-base text-slate-600">Operational certainty, audit-ready visibility, and enterprise-grade support at every stage.</p>
            </div>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach([
                    ['Real-time tracking', 'Live timeline updates for status, checkpoints, and delivery estimates.'],
                    ['Secure workflows', 'Admin-controlled tracking number generation and stage-based shipment progression.'],
                    ['24/7 support', 'Dedicated support teams for exceptions, customs, and customer escalations.'],
                ] as [$title, $body])
                    <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-600"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <h3 class="mt-4 font-display text-lg font-bold text-navy-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="ship" class="py-16 sm:py-20 lg:py-24 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-navy-100 bg-slate-50 p-8 shadow-card">
                <h2 class="font-display text-2xl font-bold text-navy-900">International Shipping</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Time-definite international delivery with customs readiness, transshipment visibility, and proactive compliance support.
                </p>
                <ul class="mt-4 space-y-2 text-sm text-slate-700">
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-emerald-600"><path d="M20 6 9 17l-5-5"/></svg>
                        Cross-border checkpoints and customs clearance events
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-emerald-600"><path d="M20 6 9 17l-5-5"/></svg>
                        Global route optimization through major logistics hubs
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-emerald-600"><path d="M20 6 9 17l-5-5"/></svg>
                        Priority handling for urgent international parcels
                    </li>
                </ul>
            </div>
            <div class="rounded-3xl border border-navy-100 bg-slate-50 p-8 shadow-card">
                <h2 class="font-display text-2xl font-bold text-navy-900">Domestic Shipping</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Reliable national lane coverage with same-day and next-day programs, warehouse synchronization, and last-mile tracking.
                </p>
                <ul class="mt-4 space-y-2 text-sm text-slate-700">
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-emerald-600"><path d="M20 6 9 17l-5-5"/></svg>
                        Urban, regional, and nationwide delivery lanes
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-emerald-600"><path d="M20 6 9 17l-5-5"/></svg>
                        Pickup scheduling and doorstep proof-of-delivery
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-emerald-600"><path d="M20 6 9 17l-5-5"/></svg>
                        Route updates with real-time customer visibility
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <section class="bg-white pb-16 sm:pb-20 lg:pb-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl bg-navy-950 p-8 text-white shadow-lift">
                <h2 class="font-display text-2xl font-bold">Freight Services</h2>
                <p class="mt-3 text-sm leading-7 text-navy-100">
                    Plan palletized and heavyweight cargo through consolidated linehaul and hub transfer checkpoints.
                </p>
            </div>
            <div class="rounded-3xl bg-gradient-to-br from-azure-600 to-azure-800 p-8 text-white shadow-lift">
                <h2 class="font-display text-2xl font-bold">Warehouse Solutions</h2>
                <p class="mt-3 text-sm leading-7 text-azure-100">
                    Integrate inbound receiving, sort, storage, and outbound dispatch within a single operations model.
                </p>
            </div>
        </div>
    </section>

    <section class="relative overflow-hidden bg-navy-950 text-white py-16 sm:py-20 lg:py-24">
        <div class="absolute inset-0 bg-hero-radial opacity-70"></div>
        <div class="relative mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid items-center gap-10 lg:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-300">Logistics Network</p>
                <h2 class="mt-3 font-display text-3xl font-bold sm:text-4xl">A connected logistics network built for enterprise scale</h2>
                <p class="mt-4 text-navy-100">Dynamic routing across airports, ports, and regional hubs keeps shipments moving with fewer blind spots.</p>
                <div class="mt-8 grid grid-cols-2 gap-6 text-sm">
                    <div>
                        <p class="font-display text-3xl font-bold text-gold-400">120+</p>
                        <p class="text-navy-100">Regional logistics hubs</p>
                    </div>
                    <div>
                        <p class="font-display text-3xl font-bold text-gold-400">6</p>
                        <p class="text-navy-100">Continents covered</p>
                    </div>
                </div>
            </div>
            <div class="overflow-hidden rounded-3xl border border-white/10 shadow-lift">
                <img src="https://images.unsplash.com/photo-1494412519320-aa613dfb7738?auto=format&fit=crop&w=1200&q=80" alt="Port logistics operations at dusk" width="1200" height="900" class="h-[380px] w-full object-cover" />
            </div>
        </div>
    </section>

    <section aria-label="Global coverage regions" class="py-16 sm:py-20 lg:py-24 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Global Coverage</p>
                <h2 class="mt-3 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Global coverage with local execution quality</h2>
                <p class="mt-4 text-base text-slate-600">Regional expertise, local compliance, and coordinated last-mile delivery in every major market.</p>
            </div>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach([
                    ['North America', 'High-frequency domestic and cross-border lanes with advanced hub sequencing.'],
                    ['Europe', 'Customs-ready multi-country transit with resilient gateway routing.'],
                    ['Asia-Pacific', 'Port-to-door supply chain orchestration across priority trade corridors.'],
                ] as [$title, $body])
                    <div class="rounded-2xl border border-navy-100 bg-slate-50 p-6 shadow-card">
                        <h3 class="font-display text-lg font-bold text-navy-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24 bg-slate-50">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Company Statistics</p>
                <h2 class="mt-3 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Measured performance across the delivery lifecycle</h2>
                <p class="mt-4 text-base text-slate-600">Enterprise-grade KPIs with transparent execution benchmarks.</p>
            </div>
            <div class="mt-10 grid gap-6 md:grid-cols-4">
                @foreach([['98.7%', 'Checkpoint scan accuracy'], ['1.3h', 'Average exception response'], ['27K', 'Daily active customers'], ['4.9/5', 'Support satisfaction']] as [$value, $label])
                    <div class="rounded-2xl border border-navy-100 bg-white p-6 text-center shadow-card">
                        <p class="font-display text-3xl font-bold text-navy-900">{{ $value }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section aria-label="Customer testimonials" class="py-16 sm:py-20 lg:py-24 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Customer Testimonials</p>
                <h2 class="mt-3 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Trusted by operations teams worldwide</h2>
            </div>
            <div class="mt-10 grid gap-6 lg:grid-cols-3">
                @foreach([
                    ['We reduced customs delays by nearly a third after moving our lanes to FreightFlow.', 'Harper Collins', 'Operations Director, Meridian Retail'],
                    ['The tracking page is clear, fast, and trusted by our enterprise customers globally.', 'Andre Gomez', 'VP Logistics, NorthBridge Tech'],
                    ['Warehouse and driver coordination improved instantly with the real-time timeline.', 'Lina Okafor', 'Head of Fulfillment, CloudCart'],
                ] as [$quote, $name, $role])
                    <div class="rounded-2xl border border-navy-100 bg-slate-50 p-6 shadow-card">
                        <div class="flex gap-1 text-gold-500">
                            @for($i = 0; $i < 5; $i++)
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            @endfor
                        </div>
                        <p class="mt-3 text-sm leading-7 text-slate-700">&ldquo;{{ $quote }}&rdquo;</p>
                        <p class="mt-4 font-semibold text-navy-900">{{ $name }}</p>
                        <p class="text-xs text-slate-500">{{ $role }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section aria-label="Trusted partners" class="bg-white pb-16 sm:pb-20 lg:pb-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Trusted Partners</p>
                <h2 class="mt-3 font-display text-2xl font-bold text-navy-900">Partner ecosystem</h2>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-4 md:grid-cols-5">
                @foreach(['NordPort', 'AeroLink', 'Veridian', 'CargoWorks', 'BlueLane'] as $partner)
                    <div class="rounded-xl border border-navy-100 bg-slate-50 px-4 py-3 text-center text-sm font-semibold text-slate-700">
                        {{ $partner }}
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section aria-label="Latest logistics news" class="py-16 sm:py-20 lg:py-24 bg-slate-50">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Latest News</p>
                <h2 class="mt-3 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Logistics insights and operational updates</h2>
            </div>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach([
                    ['How AI-assisted routing improves on-time delivery', 'https://images.unsplash.com/photo-1474631245212-32dc3c8310c6?auto=format&fit=crop&w=900&q=80'],
                    ['Preparing documentation for smoother customs clearance', 'https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=900&q=80'],
                    ['Warehouse orchestration best practices for peak season', 'https://images.unsplash.com/photo-1553413077-190dd305871c?auto=format&fit=crop&w=900&q=80'],
                ] as [$title, $image])
                    <article class="overflow-hidden rounded-2xl border border-navy-100 bg-white shadow-card">
                        <img src="{{ $image }}" alt="{{ $title }}" width="900" height="640" class="h-44 w-full object-cover" loading="lazy" />
                        <div class="p-5">
                            <h3 class="font-display text-lg font-bold text-navy-900">{{ $title }}</h3>
                            <a href="{{ url('/blog') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-azure-700">
                                Read article
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="support" class="py-16 sm:py-20 lg:py-24 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Frequently Asked Questions</p>
                <h2 class="mt-3 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Frequently asked questions</h2>
            </div>
            <div class="mx-auto mt-10 grid max-w-4xl gap-4">
                @foreach([
                    ['Who can create tracking numbers?', 'Tracking numbers are generated exclusively by authorized administrators during shipment creation and approval.'],
                    ['How often does shipment status update?', 'Status and timeline updates publish in real time through WebSockets, with fallback polling for reliability.'],
                    ['Can I view route checkpoints on a map?', 'Yes. The tracking page shows origin, checkpoints, current location, and destination on an interactive map.'],
                    ['What happens if my tracking number is invalid?', 'You receive a professional validation message without exposing internal system details.'],
                ] as [$q, $a])
                    <details class="rounded-2xl border border-navy-100 bg-slate-50 p-5">
                        <summary class="cursor-pointer list-none font-semibold text-navy-900">{{ $q }}</summary>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <section aria-label="Newsletter subscription" class="bg-navy-950 text-white py-16 sm:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 rounded-3xl border border-white/10 bg-white/5 p-8 grid gap-6 lg:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-300">Newsletter Subscription</p>
                <h2 class="mt-3 font-display text-2xl font-bold">Stay informed on routes, rates, and network updates</h2>
                <p class="mt-2 text-navy-100">Receive curated logistics insights and service announcements in your inbox.</p>
            </div>
            <form class="grid gap-3 self-center sm:grid-cols-[1fr_auto]" aria-label="Newsletter subscription form">
                <input type="email" required placeholder="Work email address" class="min-h-12 rounded-full border border-white/20 bg-white/10 px-5 text-sm text-white placeholder:text-white/70 focus:outline-none focus:ring-2 focus:ring-azure-400" />
                <x-ui.button variant="primary" size="lg">Subscribe</x-ui.button>
            </form>
        </div>
    </section>

    <section id="contact" class="py-16 sm:py-20 lg:py-24 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Contact Information</p>
                <h2 class="mt-3 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Speak with our logistics experts</h2>
            </div>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                @php
                    $contactEmail = \App\Support\AppSettings::companyEmail() ?: 'enterprise@freightflow.example';
                    $contactPhone = \App\Support\AppSettings::companyPhone() ?: '+1 (800) 555-0199';
                @endphp
                @foreach([
                    ['Sales', $contactEmail, 'building2'],
                    ['Support', $contactEmail, 'headset'],
                    ['Operations', $contactPhone, 'package-check'],
                ] as [$label, $value, $icon])
                    <div class="rounded-2xl border border-navy-100 bg-slate-50 p-6 shadow-card">
                        @if($icon === 'building2')
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-azure-600"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
                        @elseif($icon === 'headset')
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-azure-600"><path d="M3 11h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Zm0 0a9 9 0 1 1 18 0m0 0v5a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3Z"/><path d="M21 16v2a4 4 0 0 1-4 4h-5"/></svg>
                        @elseif($icon === 'package-check')
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-azure-600"><path d="m16 16 2 2 4-4"/><path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"/><path d="m7.5 4.27 9 5.15"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" x2="12" y1="22" y2="12"/></svg>
                        @endif
                        <p class="mt-3 font-semibold text-navy-900">{{ $label }}</p>
                        <p class="text-sm text-slate-600">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
