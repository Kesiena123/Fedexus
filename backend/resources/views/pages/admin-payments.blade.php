@extends('layouts.app')
@section('title', 'Payment Management')
@php
    $hideHeaderFooter = true;
    function money($val) { return '$'.number_format((float)($val ?? 0), 2); }
@endphp
@section('content')
    <x-dashboard-shell title="Payment Management">
        @if(session('success'))
            <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
        @endif

        {{-- Gateway Status Bar --}}
        <div class="mb-6 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-700">Active Gateways</h3>
                <a href="{{ route('admin.payment-settings') }}" class="text-xs font-semibold text-[#0d5368] hover:underline">Manage Settings</a>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                @forelse($allGateways as $gw)
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold
                        {{ $gw->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-400' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $gw->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                        {{ ucfirst(str_replace('_', ' ', $gw->gateway_name)) }}
                        {{ $gw->is_active ? '' : '(off)' }}
                    </span>
                @empty
                    <span class="text-xs text-slate-400">No gateways configured</span>
                @endforelse
            </div>
        </div>

        {{-- Search/Filter --}}
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.payments') }}" class="grid gap-3 lg:grid-cols-[1fr_180px_180px_auto]">
                <input name="search" value="{{ request('search') }}" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="Search tracking number or transaction reference">
                <select name="status" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm">
                    <option value="">All statuses</option>
                    @foreach($paymentStatuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                    @endforeach
                </select>
                <select name="method" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm">
                    <option value="">All gateways</option>
                    @foreach($methods as $method)
                        <option value="{{ $method }}" @selected(request('method') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                    @endforeach
                </select>
                <button class="focus-ring min-h-11 rounded-md bg-[#0d5368] px-5 text-sm font-bold text-white hover:bg-[#0b4658]">Filter</button>
            </form>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[420px_1fr]">
            {{-- Payment Requests Management Card --}}
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900">Payment Requests</h3>
                <p class="mt-2 text-sm text-slate-500">Full payment request management with status lifecycle, audit history, and dashboard analytics.</p>
                <div class="mt-4 flex flex-col gap-2">
                    <a href="{{ route('admin.payment-requests.index') }}" class="flex items-center justify-center rounded-md bg-[#0d5368] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#0b4658]">Manage Payment Requests</a>
                    <a href="{{ route('admin.payment-requests.create') }}" class="flex items-center justify-center rounded-md border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">+ Create New Payment Request</a>
                </div>
            </div>

            {{-- Recent Payment Requests --}}
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900">Recent Payment Requests</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[700px] text-left text-sm">
                        <thead class="text-xs uppercase text-slate-500">
                            <tr><th class="border-b p-3">Tracking</th><th class="border-b p-3">Title</th><th class="border-b p-3">Amount</th><th class="border-b p-3">Status</th><th class="border-b p-3">Secure Link</th><th class="border-b p-3">Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse($paymentRequests as $request)
                                <tr>
                                    <td class="border-b border-slate-100 p-3 font-semibold">{{ $request->shipment?->tracking_number }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ $request->title }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ number_format((float)$request->amount, 2) }} {{ $request->currency }}</td>
                                    <td class="border-b border-slate-100 p-3">
                                        @if(in_array($request->status, ['paid', 'verified']))
                                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">Paid</span>
                                        @elseif(in_array($request->status, ['payment_required', 'pending']))
                                            <span class="rounded bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Payment Required</span>
                                        @elseif($request->status === 'payment_initiated')
                                            <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">Payment Initiated</span>
                                        @elseif($request->status === 'awaiting_verification')
                                            <span class="rounded bg-purple-100 px-2 py-0.5 text-xs font-bold text-purple-700">Awaiting Verification</span>
                                        @elseif($request->status === 'failed')
                                            <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Failed</span>
                                        @else
                                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ ucfirst($request->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="border-b border-slate-100 p-3"><a class="font-semibold text-[#0d5368]" href="{{ route('payment-request.show', $request->secure_token) }}" target="_blank">Open link</a></td>
                                    <td class="border-b border-slate-100 p-3">
                                        <form method="POST" action="{{ route('admin.payment-requests.destroy', $request->id) }}" onsubmit="return confirm('Delete this payment request?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-700">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="p-6 text-center text-slate-500">No payment requests have been created yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- Staged Payments --}}
        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Shipment Staged Payments</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead class="text-xs uppercase text-slate-500">
                        <tr>
                            <th class="border-b p-3">Shipment</th>
                            <th class="border-b p-3">Tracking</th>
                            <th class="border-b p-3">Stage</th>
                            <th class="border-b p-3">Amount</th>
                            <th class="border-b p-3">Gateway</th>
                            <th class="border-b p-3">Reference</th>
                            <th class="border-b p-3">Status</th>
                            <th class="border-b p-3">Date</th>
                            <th class="border-b p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td class="border-b border-slate-100 p-3">#{{ $payment->shipment_id }}</td>
                                <td class="border-b border-slate-100 p-3 font-semibold text-slate-900">{{ $payment->shipment?->tracking_number ?? 'N/A' }}</td>
                                <td class="border-b border-slate-100 p-3">{{ $payment->label }} ({{ $payment->stage }}/5)</td>
                                <td class="border-b border-slate-100 p-3">{{ money($payment->amount) }}</td>
                                <td class="border-b border-slate-100 p-3">
                                    @if($payment->provider)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ ucfirst(str_replace('_', ' ', $payment->provider)) }}</span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="border-b border-slate-100 p-3 font-mono text-xs">{{ $payment->provider_reference ?? '-' }}</td>
                                <td class="border-b border-slate-100 p-3">
                                    @if($payment->status === 'verified')
                                        <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">Verified</span>
                                    @elseif($payment->status === 'paid')
                                        <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">Paid</span>
                                    @elseif($payment->status === 'pending')
                                        <span class="rounded bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Pending</span>
                                    @elseif($payment->status === 'locked')
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-500">Locked</span>
                                    @elseif($payment->status === 'failed')
                                        <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Failed</span>
                                    @else
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ ucfirst($payment->status) }}</span>
                                    @endif
                                </td>
                                <td class="border-b border-slate-100 p-3">{{ $payment->created_at->format('M d, Y') }}</td>
                                <td class="border-b border-slate-100 p-3">
                                    <form method="POST" action="{{ route('admin.payments') }}" class="flex flex-wrap gap-2">
                                        @csrf
                                        <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                        <button name="status" value="verified" class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700">Approve</button>
                                        <button name="status" value="rejected" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-700">Reject</button>
                                        <button name="status" value="pending" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Reset</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="p-6 text-center text-slate-500">No staged payments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $payments->links() }}</div>
        </div>
    </x-dashboard-shell>
@endsection
