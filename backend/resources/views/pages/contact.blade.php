@extends('layouts.app')

@section('title', 'Contact')

@section('content')
    <section class="bg-navy-gradient text-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-20">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-300">Contact</p>
            <h1 class="mt-3 max-w-3xl font-display text-4xl font-bold tracking-tight md:text-5xl">Reach the right logistics team</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-navy-100">Sales, support, operations, billing, and shipment exceptions are routed to teams that can act quickly.</p>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid gap-6 lg:grid-cols-[1fr_420px]">
            <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                <h2 class="font-display text-xl font-bold text-navy-900">Send a message</h2>
                <form method="POST" action="{{ route('contact') }}" class="mt-4 grid gap-4 md:grid-cols-2">
                    @csrf
                    <x-ui.input label="Name" name="name" placeholder="Full name" />
                    <x-ui.input label="Email" name="email" type="email" placeholder="name@example.com" />
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Topic</label>
                        <select name="topic" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-3 text-sm text-navy-900">
                            <option value="sales">Sales and accounts</option>
                            <option value="tracking">Tracking support</option>
                            <option value="billing">Billing and payments</option>
                            <option value="operations">Operations escalation</option>
                        </select>
                    </div>
                    <x-ui.input label="Tracking number" name="tracking" placeholder="Optional" />
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Message</label>
                        <textarea name="message" rows="4" placeholder="How can we help?" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-3 text-sm text-navy-900 placeholder:text-slate-400"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <x-ui.button variant="primary" size="md">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            Send message
                        </x-ui.button>
                    </div>
                </form>
            </div>

            <div class="grid gap-4">
                @php
                    $contactEmail = \App\Support\AppSettings::companyEmail() ?: 'ops@freightflow.example';
                    $contactPhone = \App\Support\AppSettings::companyPhone() ?: '1-800-555-FLOW';
                @endphp
                @foreach([
                    ['Customer service', $contactPhone, 'phone'],
                    ['Operations control', $contactEmail, 'map-pin'],
                    ['Corporate accounts', $contactEmail, 'building2'],
                ] as [$title, $body, $icon])
                    <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                        @if($icon === 'phone')
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        @elseif($icon === 'map-pin')
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        @elseif($icon === 'building2')
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
                        @endif
                        <h2 class="font-display text-xl font-bold text-navy-900">{{ $title }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Locations</p>
                <h2 class="mt-3 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Operations hubs and support coverage</h2>
            </div>
            <div class="mt-10 grid gap-4 md:grid-cols-3">
                @foreach(['Seattle control tower', 'Memphis sort center', 'Louisville international hub'] as $location)
                    <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-gold-600"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <h3 class="font-bold text-navy-900">{{ $location }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Customer support, dispatch, customs coordination, and exception management.</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
