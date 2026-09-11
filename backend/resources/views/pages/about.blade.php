@extends('layouts.app')

@section('title', 'About Us')

@section('content')
    <section class="relative overflow-hidden bg-navy-gradient text-white">
        <div class="absolute inset-0 bg-hero-radial"></div>
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid items-center gap-10 py-16 lg:grid-cols-[0.95fr_1.05fr] lg:py-24">
            <div>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/5 px-3 py-1 text-xs font-semibold text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-400"><circle cx="12" cy="12" r="10"/><line x1="2" x2="22" y1="12" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    About FreightFlow
                </span>
                <h1 class="mt-5 font-display text-4xl font-bold leading-tight tracking-tight md:text-5xl">A modern logistics platform for complex delivery networks</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-navy-100">FreightFlow connects customers, administrators, warehouse teams, support agents, and drivers through one controlled shipment lifecycle.</p>
            </div>
            <div class="overflow-hidden rounded-3xl border border-white/15 shadow-lift">
                <img src="https://images.unsplash.com/photo-1494412519320-aa613dfb7738?auto=format&fit=crop&w=1200&q=80" alt="Global container port logistics network" width="1200" height="360" class="h-[360px] w-full object-cover" />
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([['120+', 'countries supported'], ['99.9%', 'tracking event availability'], ['24/7', 'operations monitoring'], ['5', 'sequential payment stages']] as [$value, $label])
                    <div class="rounded-2xl border border-navy-100 bg-white p-6 text-center shadow-card">
                        <p class="font-display text-4xl font-bold text-azure-600">{{ $value }}</p>
                        <p class="mt-2 text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Operating principles</p>
                <h2 class="mt-3 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Visibility, control, and trust at every handoff</h2>
                <p class="mt-4 text-base text-slate-600">The platform is designed around clear roles, auditable movement, and customer confidence.</p>
            </div>
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach([
                    ['globe', 'Global reach', 'Domestic courier, cross-border freight, customs processing, and destination hub workflows.'],
                    ['shield', 'Controlled operations', 'RBAC, admin approvals, milestone locks, and audit-oriented workflows protect every shipment.'],
                    ['truck', 'Last-mile clarity', 'Driver assignments, pickup tasks, delivery confirmation, and proof-of-delivery records keep teams aligned.'],
                ] as [$icon, $title, $body])
                    <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                        @if($icon === 'globe')
                            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4 text-azure-600"><circle cx="12" cy="12" r="10"/><line x1="2" x2="22" y1="12" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        @elseif($icon === 'shield')
                            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4 text-azure-600"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        @elseif($icon === 'truck')
                            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4 text-azure-600"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                        @endif
                        <h2 class="font-display text-xl font-bold text-navy-900">{{ $title }}</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-navy-100 bg-navy-gradient text-white p-8 shadow-card">
                <div class="grid gap-5 md:grid-cols-[auto_1fr] md:items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-400"><path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/><path d="M6 18h12"/><path d="M6 14h12"/><rect width="12" height="12" x="6" y="10"/></svg>
                    <div>
                        <h2 class="font-display text-2xl font-bold">Built for enterprise teams</h2>
                        <p class="mt-2 text-sm leading-6 text-navy-100">Super admins, managers, support agents, warehouse staff, drivers, and customers each get focused controls for their part of the shipment lifecycle.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
