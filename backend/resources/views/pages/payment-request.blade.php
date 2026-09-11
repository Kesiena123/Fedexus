@extends('layouts.app')
@section('title', 'Shipment Payment Request')
@section('content')
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d8fa3]">Secure payment request</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $paymentRequest->title }}</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $paymentRequest->reason }}</p>

            @if(session('success'))
                <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                    @if(session('transaction_reference'))
                        <span class="block font-mono text-xs">Reference: {{ session('transaction_reference') }}</span>
                    @endif
                </div>
            @endif

            @if(session('error'))
                <div class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-md bg-slate-50 p-4">
                    <p class="text-sm text-slate-500">Tracking Number</p>
                    <p class="mt-1 font-mono text-lg font-bold text-slate-900">{{ $paymentRequest->shipment->tracking_number }}</p>
                </div>
                <div class="rounded-md bg-slate-50 p-4">
                    <p class="text-sm text-slate-500">Amount Due</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ number_format((float) $paymentRequest->amount, 2) }} {{ $paymentRequest->currency }}</p>
                </div>
                <div class="rounded-md bg-slate-50 p-4">
                    <p class="text-sm text-slate-500">Status</p>
                    <p class="mt-1 font-bold capitalize text-slate-900">{{ str_replace('_', ' ', $paymentRequest->status) }}</p>
                </div>
                <div class="rounded-md bg-slate-50 p-4">
                    <p class="text-sm text-slate-500">Due Date</p>
                    <p class="mt-1 font-bold text-slate-900">{{ $paymentRequest->due_at?->format('M d, Y') ?? 'No due date' }}</p>
                </div>
            </div>

            @if(in_array($paymentRequest->status, ['paid', 'verified']))
                <div class="mt-6 rounded-md border border-emerald-200 bg-emerald-50 p-5 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="mt-3 text-lg font-bold text-emerald-800">Payment Complete</h3>
                    <p class="mt-1 text-sm text-emerald-600">This payment has been completed. Thank you!</p>
                    @if($transactions->where('status', 'paid')->first())
                        @php $paidTx = $transactions->where('status', 'paid')->first(); @endphp
                        <p class="mt-2 font-mono text-xs text-emerald-500">Reference: {{ $paidTx->provider_reference }}</p>
                    @endif
                </div>
            @elseif($paymentRequest->status === 'awaiting_verification')
                <div class="mt-6 rounded-md border border-purple-200 bg-purple-50 p-5 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-purple-100">
                        <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="mt-3 text-lg font-bold text-purple-800">Awaiting Verification</h3>
                    <p class="mt-1 text-sm text-purple-600">Your payment proof has been received. An administrator will verify it shortly.</p>
                    <div class="mt-4">
                        <a href="{{                             route('payment.cancel', $paymentRequest->secure_token) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-purple-700 hover:underline">
                            Cancel and retry
                        </a>
                    </div>
                </div>
            @elseif($paymentRequest->status === 'payment_initiated')
                <div class="mt-6 rounded-md border border-blue-200 bg-blue-50 p-5 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                        <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="mt-3 text-lg font-bold text-blue-800">Payment Initiated</h3>
                    <p class="mt-1 text-sm text-blue-600">Your payment is being processed. Please wait for confirmation.</p>
                    <div class="mt-4">
                        <a href="{{                             route('payment.cancel', $paymentRequest->secure_token) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700 hover:underline">
                            Cancel and retry
                        </a>
                    </div>
                </div>
            @elseif(in_array($paymentRequest->status, ['payment_required', 'pending']))
                @if($enabledGateways->isEmpty())
                    <div class="mt-6 rounded-md border border-amber-200 bg-amber-50 p-5 text-center">
                        <p class="text-sm font-semibold text-amber-700">No payment methods are currently available. Please contact support.</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('payment-request.start', $paymentRequest->secure_token) }}" class="mt-6" x-data="paymentForm()" id="payment-form">
                        @csrf
                        <h3 class="text-lg font-bold text-slate-900">Select Payment Method</h3>
                        <p class="mt-1 text-sm text-slate-500">Choose your preferred payment gateway to complete this transaction.</p>

                        <div id="payment-error" class="mt-3 hidden rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                        <div class="mt-4 grid gap-3">
                            @foreach($enabledGateways as $method)
                                <label class="flex cursor-pointer items-center gap-4 rounded-lg border-2 p-4 transition-all hover:bg-slate-50"
                                    :class="selectedProvider === '{{ $method['name'] }}' ? 'border-[#0d5368] bg-[#0d5368]/5' : 'border-slate-200'">
                                    <input type="radio" name="provider" value="{{ $method['name'] }}" class="hidden"
                                        @click="selectedProvider = '{{ $method['name'] }}'; fee = {{ $method['fee_percentage'] }}; fixedFee = {{ $method['fixed_fee'] }}">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white shadow-sm">
                                        @if($method['name'] === 'flutterwave')
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="4" fill="#F5A623"/><path d="M7 7h4v4H7zm0 6h4v4H7zm6-6h4v10h-4z" fill="#fff"/></svg>
                                        @elseif($method['name'] === 'stripe')
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="4" fill="#635BFF"/><path d="M11.2 9.6c0-.72.56-1.04 1.48-1.04 1.32 0 2.96.4 4.28 1.16V6.4C14.64 5.84 13.08 5.6 11.2 5.6 7.92 5.6 5.8 7.36 5.8 9.8c0 3.84 5.28 3.2 5.28 4.84 0 .8-.68 1.08-1.64 1.08-1.44 0-3.28-.6-4.72-1.36v3.36c1.6.68 3.24.96 4.72.96 3.48 0 5.76-1.72 5.76-4.24-.04-4.12-5.2-3.44-5.2-4.84z" fill="#fff"/></svg>
                                        @elseif($method['name'] === 'paypal')
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="4" fill="#003087"/><path d="M7 18.5h2.4l.6-3.4h1.3c2.8 0 4.5-1.4 4.9-4.1.2-1.1.1-2-.2-2.7-.3-.8-.9-1.4-1.7-1.7-1-.4-2.1-.5-3.3-.5H5.5l-1.8 11.4h3.3z" fill="#009cde"/></svg>
                                        @elseif($method['name'] === 'bank_transfer')
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="4" fill="#0d5368"/><path d="M4 18h16M4 18V10l8-5 8 5v8M8 18v-5h3v5M13 18v-5h3v5" stroke="#fff" stroke-width="1.5"/></svg>
                                        @elseif($method['name'] === 'crypto')
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="4" fill="#F7931A"/><path d="M14.5 9.5c.3-2-1.2-3-3.2-3.8l-.7-2.7h-1.6l.5 2.3c-.4.1-.9.2-1.3.3L7.3 3H5.7l-.6 2.3c-.3.1-.7.2-1 .3l-2.2-.5v1.6l2.1.5c-.1.3-.1.6-.1.9 0 .3 0 .6.1.9l-2.1.5v1.6l2.2-.5c.3.1.7.2 1 .3l.6 2.3h1.6l-.7-2.7c.5.1.9.2 1.3.3l-.5 2.3h1.6l.7-2.7c2-.8 3.5-1.8 3.2-3.8-.2-1.6-1.5-2.5-1.5-2.5s.8 1-.1 2.5z" fill="#fff"/></svg>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-bold text-slate-900">{{ $method['display_name'] }}</p>
                                        <p class="text-xs text-slate-500">
                                            @if($method['fee_percentage'] > 0 || $method['fixed_fee'] > 0)
                                                Fee: @if($method['fee_percentage'] > 0){{ $method['fee_percentage'] }}%@endif @if($method['fixed_fee'] > 0)+ ${{ number_format($method['fixed_fee'], 2) }}@endif
                                            @else
                                                No additional fees
                                            @endif
                                            &middot; {{ ucfirst($method['mode']) }} mode
                                        </p>
                                    </div>
                                    <div class="h-5 w-5 rounded-full border-2 transition-all"
                                        :class="selectedProvider === '{{ $method['name'] }}' ? 'border-[#0d5368] bg-[#0d5368]' : 'border-slate-300'">
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <button type="submit" class="focus-ring min-h-11 rounded-md bg-[#0d5368] px-8 text-sm font-bold text-white hover:bg-[#0b4658]"
                                :disabled="!selectedProvider || processing" :class="(!selectedProvider || processing) ? 'opacity-50 cursor-not-allowed' : ''">
                                <span x-show="!processing">Pay Now</span>
                                <span x-show="processing" class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75"/></svg>
                                    Processing...
                                </span>
                            </button>
                            @if(isset($transactions) && $transactions->count() > 0)
                                <a href="{{ route('payment-request.show', $paymentRequest->secure_token) }}" class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md border border-slate-200 px-5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                    Refresh Status
                                </a>
                            @endif
                        </div>
                    </form>
                @endif
            @endif

            {{-- Payment History --}}
            @if(isset($transactions) && $transactions->count() > 0)
                <div class="mt-8 border-t border-slate-200 pt-6">
                    <h3 class="text-lg font-bold text-slate-900">Payment History</h3>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-[500px] text-left text-sm">
                            <thead class="text-xs uppercase text-slate-500">
                                <tr><th class="border-b p-2">Date</th><th class="border-b p-2">Gateway</th><th class="border-b p-2">Reference</th><th class="border-b p-2">Amount</th><th class="border-b p-2">Status</th></tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $tx)
                                    <tr>
                                        <td class="border-b border-slate-100 p-2">{{ $tx->created_at->format('M d, Y H:i') }}</td>
                                        <td class="border-b border-slate-100 p-2 capitalize">{{ str_replace('_', ' ', $tx->provider) }}</td>
                                        <td class="border-b border-slate-100 p-2 font-mono text-xs">{{ $tx->provider_reference }}</td>
                                        <td class="border-b border-slate-100 p-2">{{ number_format((float)$tx->amount, 2) }} {{ $tx->currency }}</td>
                                        <td class="border-b border-slate-100 p-2">
                                            @if($tx->status === 'verified' || $tx->status === 'paid')
                                                <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">Paid</span>
                                            @elseif($tx->status === 'pending' || $tx->status === 'initiated')
                                                <span class="rounded bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Pending</span>
                                            @elseif($tx->status === 'failed')
                                                <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Failed</span>
                                            @else
                                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ ucfirst($tx->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>
    </div>

    <script src="https://checkout.flutterwave.com/v3.js" async></script>
    <script>
    function paymentForm() {
        return {
            selectedProvider: '',
            fee: 0,
            fixedFee: 0,
            processing: false,

            init() {
                const form = document.getElementById('payment-form');
                if (!form) return;

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    if (this.processing || !this.selectedProvider) return;

                    this.processing = true;
                    const errorEl = document.getElementById('payment-error');
                    if (errorEl) errorEl.classList.add('hidden');

                    const formData = new FormData(form);
                    formData.set('provider', this.selectedProvider);

                    try {
                        const resp = await fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        });

                        const data = await resp.json();

                        if (resp.ok && data.redirect_url) {
                            if (data.inline_config && this.selectedProvider === 'flutterwave') {
                                this.openFlutterwaveInline(data.inline_config);
                                return;
                            }
                            window.location.href = data.redirect_url;
                            return;
                        }

                        if (data.error) {
                            if (errorEl) {
                                errorEl.textContent = data.error;
                                errorEl.classList.remove('hidden');
                            }
                        } else if (resp.status === 422 && data.errors) {
                            const msgs = Object.values(data.errors).flat().join(' ');
                            if (errorEl) {
                                errorEl.textContent = msgs;
                                errorEl.classList.remove('hidden');
                            }
                        } else {
                            window.location.href = form.action;
                            return;
                        }
                    } catch (err) {
                        window.location.href = form.action;
                        return;
                    }

                    this.processing = false;
                });
            },

            openFlutterwaveInline(config) {
                var self = this;
                if (typeof FlutterwaveCheckout === 'undefined') {
                    var errorEl = document.getElementById('payment-error');
                    if (errorEl) {
                        errorEl.textContent = 'Payment system is loading, please try again in a moment.';
                        errorEl.classList.remove('hidden');
                    }
                    self.processing = false;
                    return;
                }

                FlutterwaveCheckout({
                    public_key: config.public_key,
                    tx_ref: config.tx_ref,
                    amount: config.amount,
                    currency: config.currency,
                    country: 'US',
                    customer: config.customer,
                    customizations: config.customizations,
                    callback: function(response) {
                        var callbackUrl = config.callback_url + '?transaction_id=' + encodeURIComponent(response.transaction_id || '') + '&tx_ref=' + encodeURIComponent(config.tx_ref) + '&status=' + encodeURIComponent(response.status || '');
                        window.location.href = callbackUrl;
                    },
                    onclose: function() {
                        self.processing = false;
                    },
                });
            },
        }
    }
    </script>
@endsection
