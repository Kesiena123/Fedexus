@extends('layouts.app')

@section('title', 'Support')

@section('content')
    <section class="bg-navy-gradient text-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-20">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-300">Support</p>
            <h1 class="mt-3 max-w-3xl font-display text-4xl font-bold tracking-tight md:text-5xl">Customer support and live operations help</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-navy-100">Open a ticket and keep every support exchange connected to the shipment record.</p>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid gap-6 lg:grid-cols-[1fr_360px]">
            <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                @if(session('ticket_success'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 text-emerald-600 shrink-0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <div>
                                <h4 class="font-semibold text-emerald-900">Ticket submitted successfully</h4>
                                <p class="mt-1 text-sm text-emerald-700">Our team will review your request and get back to you shortly.</p>
                            </div>
                        </div>
                    </div>
                @endif

                <h2 class="flex items-center gap-2 font-display text-xl font-bold text-navy-900">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 3-3h1a1 1 0 0 0 1-1V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1h-2"/><path d="M9 22a3 3 0 0 1-3-3v-1a1 1 0 0 0-1-1H4a1 1 0 0 1-1-1V9"/><circle cx="15" cy="16" r="2"/></svg>
                    Open a support ticket
                </h2>
                <form method="POST" action="{{ route('support.store') }}" class="mt-4 grid gap-4">
                    @csrf
                    <x-ui.input name="tracking" placeholder="Tracking number (optional)" />
                    <div>
                        <select name="category" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-3 text-sm text-navy-900">
                            <option value="delivery">Delivery issue</option>
                            <option value="billing">Billing and payment</option>
                            <option value="customs">Customs and documents</option>
                            <option value="damage">Damage claim</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <x-ui.input name="subject" placeholder="Subject" />
                    <div>
                        <textarea name="message" rows="4" placeholder="How can we help?" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-3 text-sm text-navy-900 placeholder:text-slate-400">{{ old('message') }}</textarea>
                        @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <x-ui.button variant="primary" size="md">Submit ticket</x-ui.button>
                </form>
            </div>

            <div class="grid gap-4">
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <h3 class="font-display text-lg font-bold text-navy-900">Response time</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">We respond to all tickets within 24 hours during business days.</p>
                </div>
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-gold-600"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    <h3 class="font-display text-lg font-bold text-navy-900">Track your ticket</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Use your tracking number to check the status of any shipment-related inquiry.</p>
                </div>
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>
                    <h3 class="font-display text-lg font-bold text-navy-900">Emergency support</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">For urgent issues with active shipments, select "Urgent" priority above.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
