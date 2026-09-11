@extends('layouts.app')
@section('title', 'FAQ')
@section('content')
    <main class="bg-slate-50">
        <section class="bg-navy-gradient text-center text-white">
            <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-20">
                <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-gold-400"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                <h1 class="font-display text-4xl font-bold tracking-tight md:text-5xl">Frequently asked questions</h1>
                <p class="mx-auto mt-4 max-w-2xl text-navy-100">Answers for shipping, tracking, payments, support, and account security.</p>
            </div>
        </section>
        <section class="py-16 sm:py-20 lg:py-24">
            <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mx-auto text-center">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-azure-600">Help center</p>
                    <h2 class="mt-3 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Common questions, clear answers</h2>
                </div>
                <div class="mx-auto mt-10 grid max-w-5xl gap-4">
                    @foreach([
                        ['Who can generate tracking numbers?', 'Only authorized administrators can generate tracking numbers after reviewing and approving a shipment request.'],
                        ['How do payment stages work?', 'Payment stages unlock one at a time as administrators approve shipment milestones. Customers cannot skip ahead or pay hidden future stages.'],
                        ['Can I update my delivery address?', 'Customers can request delivery changes from the dashboard. Operations teams review changes before they are applied to active shipments.'],
                        ['Does tracking include maps?', 'The tracking experience is designed for route history, current location, origin, destination, and live checkpoint visibility.'],
                        ['Which payment providers are supported?', 'Stripe, PayPal, Flutterwave, Paystack, and bank transfer are modeled in the payment workflow.'],
                        ['What roles are supported?', 'Super admin, admin, manager, support, warehouse staff, driver, and customer roles have separate permissions.'],
                    ] as [$question, $answer])
                        <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                            <h2 class="font-display text-xl font-bold text-navy-900">{{ $question }}</h2>
                            <p class="mt-3 leading-7 text-slate-600">{{ $answer }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </main>
@endsection