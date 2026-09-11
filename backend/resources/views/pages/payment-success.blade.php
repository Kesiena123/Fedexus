@extends('layouts.app')
@section('title', 'Payment Success')
@section('content')
    <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                <svg class="h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>

            <h1 class="mt-4 text-2xl font-bold text-slate-900">{{ $message ?? 'Payment Successful!' }}</h1>
            <p class="mt-2 text-sm text-slate-500">Thank you for your payment. Your transaction has been recorded.</p>

            @if($transaction)
                <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-5 text-left">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Transaction Details</h3>
                    <div class="mt-3 grid gap-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Reference</span>
                            <span class="font-mono font-bold text-slate-900">{{ $transaction->provider_reference }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Amount</span>
                            <span class="font-bold text-slate-900">{{ number_format((float)$transaction->amount, 2) }} {{ $transaction->currency }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Gateway</span>
                            <span class="font-semibold text-slate-900">{{ ucfirst(str_replace('_', ' ', $provider ?? $transaction->provider)) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Status</span>
                            @if($transaction->status === 'verified' || $transaction->status === 'paid')
                                <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">Verified</span>
                            @else
                                <span class="rounded bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Processing</span>
                            @endif
                        </div>
                        @if($transaction->fee_amount)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Processing Fee</span>
                                <span class="text-slate-700">{{ number_format((float)$transaction->fee_amount, 2) }} {{ $transaction->currency }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-slate-500">Date</span>
                            <span class="text-slate-900">{{ $transaction->created_at->format('M d, Y H:i:s') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap justify-center gap-3">
                @if($paymentRequest)
                    <a href="{{ route('tracking.number', $paymentRequest->shipment->tracking_number) }}" class="focus-ring min-h-11 rounded-md bg-[#0d5368] px-6 text-sm font-bold text-white hover:bg-[#0b4658]">
                        Track Shipment
                    </a>
                    <a href="{{ route('receipt', $paymentRequest->shipment->tracking_number) }}" class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md border border-slate-200 px-5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        View Receipt
                    </a>
                @endif
                <a href="{{ route('home') }}" class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md border border-slate-200 px-5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                    Back to Home
                </a>
            </div>
        </section>
    </div>
@endsection
