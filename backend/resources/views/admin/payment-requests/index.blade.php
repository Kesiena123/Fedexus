@extends('layouts.admin')
@section('title', 'Payment Requests')
@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Payment Requests</h1>
        <a href="{{ route('admin.payment-requests.create') }}" class="rounded-md bg-[#0d5368] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#0b4658]">+ Create New</a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $stats['active'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Outstanding</p>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ $stats['payable'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Awaiting Verification</p>
            <p class="mt-1 text-2xl font-bold text-purple-700">{{ $stats['awaiting_count'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Completed</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $stats['completed_count'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Failed / Expired</p>
            <p class="mt-1 text-2xl font-bold text-red-700">{{ $stats['failed_count'] + $stats['expired_count'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Revenue by Currency</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">
                @php $totalRevenue = $stats['revenue_by_currency']->sum() @endphp
                {{ number_format($totalRevenue, 2) }}
            </p>
            @if($stats['revenue_by_currency']->count() > 0)
                <div class="mt-1 flex flex-wrap gap-1">
                    @foreach($stats['revenue_by_currency'] as $cur => $amt)
                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-semibold text-slate-600">{{ $cur }} {{ number_format($amt, 0) }}</span>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Revenue by Gateway</p>
            @if($stats['revenue_by_gateway']->count() > 0)
                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1">
                    @foreach($stats['revenue_by_gateway'] as $gw => $amt)
                        <span class="text-sm font-semibold text-slate-900">{{ ucfirst(str_replace('_', ' ', $gw)) }}: <span class="text-emerald-700">{{ number_format($amt, 2) }}</span></span>
                    @endforeach
                </div>
            @else
                <p class="mt-1 text-sm text-slate-400">No gateway revenue data yet.</p>
            @endif
        </div>
    </div>

    {{-- Filters + Create --}}
    <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_140px_120px_120px_120px_auto]">
                <input name="search" value="{{ request('search') }}" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="Search title, tracking, customer...">
                <select name="status" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm">
                    <option value="">All statuses</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                    @endforeach
                </select>
                <input name="currency" value="{{ request('currency') }}" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="Currency">
                <select name="priority" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm">
                    <option value="">All priorities</option>
                    @foreach($priorities as $p)
                        <option value="{{ $p }}" @selected(request('priority') === $p)>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
                <div class="grid grid-cols-2 gap-2">
                    <input name="date_from" type="date" value="{{ request('date_from') }}" class="focus-ring min-h-11 rounded-md border border-slate-200 px-2 text-xs" placeholder="From">
                    <input name="date_to" type="date" value="{{ request('date_to') }}" class="focus-ring min-h-11 rounded-md border border-slate-200 px-2 text-xs" placeholder="To">
                </div>
                <div class="flex gap-2">
                    <button class="focus-ring min-h-11 rounded-md bg-[#0d5368] px-4 text-sm font-bold text-white hover:bg-[#0b4658]">Filter</button>
                    <a href="{{ route('admin.payment-requests.index') }}" class="focus-ring flex min-h-11 items-center rounded-md border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Clear</a>
                </div>
            </form>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('admin.payment-requests.index') }}" class="rounded-full px-3 py-1 text-xs font-semibold @if(!request('archived')) bg-[#0d5368] text-white @else bg-slate-100 text-slate-600 hover:bg-slate-200 @endif">Active</a>
                <a href="{{ route('admin.payment-requests.index', ['archived' => '1']) }}" class="rounded-full px-3 py-1 text-xs font-semibold @if(request('archived') === '1') bg-[#0d5368] text-white @else bg-slate-100 text-slate-600 hover:bg-slate-200 @endif">Archived</a>
                <a href="{{ route('admin.payment-requests.index', ['archived' => 'all']) }}" class="rounded-full px-3 py-1 text-xs font-semibold @if(request('archived') === 'all') bg-[#0d5368] text-white @else bg-slate-100 text-slate-600 hover:bg-slate-200 @endif">All</a>
            </div>
        </div>

        {{-- Quick Create Form --}}
        <form method="POST" action="{{ route('admin.payment-requests.store') }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <input type="hidden" name="status" value="draft">
            <h3 class="text-lg font-bold text-slate-900">Quick Create Payment Request</h3>
            <div class="mt-4 grid gap-3">
                <select name="shipment_id" required class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm">
                    <option value="">Select shipment</option>
                    @foreach($requestShipments as $shipment)
                        <option value="{{ $shipment->id }}">{{ $shipment->tracking_number }} - {{ $shipment->recipient_name }}</option>
                    @endforeach
                </select>
                <div class="grid gap-3 sm:grid-cols-2">
                    <input name="title" required class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="Payment title">
                    <input name="category" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="Category (e.g. Customs, Storage)">
                </div>
                <textarea name="reason" required rows="3" class="focus-ring rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Reason for payment"></textarea>
                <div class="grid gap-3 sm:grid-cols-3">
                    <input name="amount" required type="number" min="0.01" step="0.01" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="Amount">
                    <input name="currency" required value="{{ \App\Support\AppSettings::currency() }}" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="Currency">
                    <select name="priority" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm">
                        <option value="normal">Priority: Normal</option>
                        <option value="low">Low</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Allowed Payment Methods</label>
                    <div class="flex flex-wrap gap-2">
                        @forelse($activeGateways as $gateway)
                            <label class="flex cursor-pointer items-center gap-1.5 rounded-md border px-3 py-2 text-xs font-semibold transition-all hover:bg-slate-50 has-[:checked]:border-[#0d5368] has-[:checked]:bg-[#0d5368]/5 has-[:checked]:text-[#0d5368]">
                                <input type="checkbox" name="allowed_methods[]" value="{{ $gateway->gateway_name }}" class="rounded border-slate-300 text-[#0d5368]">
                                {{ ucfirst(str_replace('_', ' ', $gateway->gateway_name)) }}
                            </label>
                        @empty
                            <span class="text-xs text-slate-400">No gateways enabled.</span>
                        @endforelse
                        <label class="flex cursor-pointer items-center gap-1.5 rounded-md border border-dashed px-3 py-2 text-xs font-semibold text-slate-400 transition-all hover:bg-slate-50 has-[:checked]:border-slate-300 has-[:checked]:text-slate-600">
                            <input type="checkbox" name="allowed_methods[]" value="" class="rounded border-slate-300">
                            Any (all active)
                        </label>
                    </div>
                </div>
                <input name="due_at" type="date" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm">
                <button class="focus-ring min-h-11 rounded-md bg-[#0d5368] px-5 text-sm font-bold text-white hover:bg-[#0b4658]">Create as Draft</button>
            </div>
        </form>
    </div>

    {{-- Payment Requests Table --}}
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="p-3">Tracking</th>
                        <th class="p-3">Title</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Priority</th>
                        <th class="p-3">Created</th>
                        <th class="p-3">Due</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($paymentRequests as $pr)
                        <tr class="transition-all hover:bg-slate-50 @if(!$pr->is_active) opacity-60 @endif">
                            <td class="p-3 font-mono text-xs">{{ $pr->shipment?->tracking_number ?? 'N/A' }}</td>
                            <td class="p-3 font-semibold text-slate-900">{{ $pr->title }}</td>
                            <td class="p-3 font-semibold">{{ number_format((float) $pr->amount, 2) }} {{ $pr->currency }}</td>
                            <td class="p-3">
                                @php
                                    $statusColors = ['draft' => 'bg-slate-100 text-slate-600', 'payment_required' => 'bg-amber-100 text-amber-700', 'payment_initiated' => 'bg-blue-100 text-blue-700', 'awaiting_verification' => 'bg-purple-100 text-purple-700', 'processing' => 'bg-indigo-100 text-indigo-700', 'paid' => 'bg-emerald-100 text-emerald-700', 'verified' => 'bg-emerald-100 text-emerald-700', 'cancelled' => 'bg-red-100 text-red-700', 'expired' => 'bg-orange-100 text-orange-700', 'failed' => 'bg-red-100 text-red-700', 'refunded' => 'bg-cyan-100 text-cyan-700', 'archived' => 'bg-slate-200 text-slate-600'];
                                @endphp
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $statusColors[$pr->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $pr->statusLabel() }}
                                </span>
                            </td>
                            <td class="p-3">
                                @php
                                    $priorityColors = ['urgent' => 'text-red-600', 'high' => 'text-orange-600', 'normal' => 'text-slate-600', 'low' => 'text-slate-400'];
                                @endphp
                                <span class="text-xs font-semibold {{ $priorityColors[$pr->priority] ?? 'text-slate-600' }}">{{ $pr->priorityLabel() }}</span>
                            </td>
                            <td class="p-3 text-xs text-slate-500">{{ $pr->created_at->format('M d, Y') }}</td>
                            <td class="p-3 text-xs {{ $pr->due_at && $pr->due_at->isPast() && !$pr->isCompleted() ? 'text-red-600 font-bold' : 'text-slate-500' }}">
                                {{ $pr->due_at?->format('M d, Y') ?? '—' }}
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.payment-requests.show', $pr->id) }}" class="rounded-md bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">View</a>
                                    @if(!$pr->is_archived && !$pr->isCompleted())
                                        <a href="{{ route('admin.payment-requests.edit', $pr->id) }}" class="rounded-md bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">Edit</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-6 text-center text-slate-400">No payment requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($paymentRequests->hasPages())
            <div class="border-t border-slate-200 p-3">{{ $paymentRequests->links() }}</div>
        @endif
    </div>
</div>
@endsection
