@extends('layouts.app')
@section('title', 'Payment Cancelled')
@section('content')
    <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100">
                <svg class="h-8 w-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>

            <h1 class="mt-4 text-2xl font-bold text-slate-900">Payment Cancelled</h1>
            <p class="mt-2 text-sm text-slate-500">Your payment was not completed. No charges have been made.</p>

            @if($paymentRequest)
                <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-5 text-left">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Payment Details</h3>
                    <div class="mt-3 grid gap-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Title</span>
                            <span class="font-semibold text-slate-900">{{ $paymentRequest->title }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Amount</span>
                            <span class="font-bold text-slate-900">{{ number_format((float)$paymentRequest->amount, 2) }} {{ $paymentRequest->currency }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Tracking</span>
                            <span class="font-mono font-bold text-slate-900">{{ $paymentRequest->shipment->tracking_number }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap justify-center gap-3">
                @if($paymentRequest && in_array($paymentRequest->status, ['payment_required', 'pending']))
                    <a href="{{ route('payment-request.show', $paymentRequest->secure_token) }}" class="focus-ring min-h-11 rounded-md bg-[#0d5368] px-6 text-sm font-bold text-white hover:bg-[#0b4658]">
                        Try Again
                    </a>
                @endif
                <a href="{{ route('home') }}" class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md border border-slate-200 px-5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                    Back to Home
                </a>
            </div>
        </section>
    </div>
@endsection
