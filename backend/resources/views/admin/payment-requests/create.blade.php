@extends('layouts.admin')
@section('title', 'Create Payment Request')
@section('content')
<div class="mx-auto max-w-3xl">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d8fa3]">Admin</p>
        <h1 class="mt-1 text-xl font-bold text-slate-900">Create Payment Request</h1>

        <form method="POST" action="{{ route('admin.payment-requests.store') }}" class="mt-6 grid gap-5">
            @csrf

            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Shipment / Tracking Number</label>
                <select name="shipment_id" required class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm">
                    <option value="">Select shipment</option>
                    @foreach($requestShipments as $shipment)
                        <option value="{{ $shipment->id }}" @selected(old('shipment_id') == $shipment->id)>{{ $shipment->tracking_number }} — {{ $shipment->recipient_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Payment Title</label>
                    <input name="title" value="{{ old('title') }}" required class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm" placeholder="e.g. Customs Clearance Fee">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Category</label>
                    <input name="category" value="{{ old('category') }}" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm" placeholder="e.g. Customs, Storage, Insurance, Duty">
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Priority</label>
                    <select name="priority" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm">
                        @foreach($priorities as $p)
                            <option value="{{ $p }}" @selected(old('priority', 'normal') === $p)>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Status</label>
                    <select name="status" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm">
                        @foreach($statuses as $st)
                            <option value="{{ $st }}" @selected(old('status', 'draft') === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Due Date</label>
                    <input name="due_at" type="date" value="{{ old('due_at') }}" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" class="focus-ring w-full rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Why is this payment required?">{{ old('reason') }}</textarea>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Description</label>
                <textarea name="description" rows="4" class="focus-ring w-full rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Optional detailed description">{{ old('description') }}</textarea>
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Amount <span class="text-red-500">*</span></label>
                    <input name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm" placeholder="0.00">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Currency <span class="text-red-500">*</span></label>
                    <input name="currency" value="{{ old('currency', \App\Support\AppSettings::currency()) }}" required class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm" placeholder="Currency">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Allowed Payment Methods</label>
                <p class="mb-2 text-xs text-slate-400">Select which gateways the customer can use for this payment.</p>
                <div class="flex flex-wrap gap-2">
                    @forelse($activeGateways as $gateway)
                        <label class="flex cursor-pointer items-center gap-1.5 rounded-md border px-3 py-2 text-xs font-semibold transition-all hover:bg-slate-50 has-[:checked]:border-[#0d5368] has-[:checked]:bg-[#0d5368]/5 has-[:checked]:text-[#0d5368]">
                            <input type="checkbox" name="allowed_methods[]" value="{{ $gateway->gateway_name }}" @checked(in_array($gateway->gateway_name, old('allowed_methods', []))) class="rounded border-slate-300 text-[#0d5368]">
                            {{ ucfirst(str_replace('_', ' ', $gateway->gateway_name)) }}
                        </label>
                    @empty
                        <span class="text-xs text-slate-400">No gateways enabled. Enable payment gateways in Settings first.</span>
                    @endforelse
                    <label class="flex cursor-pointer items-center gap-1.5 rounded-md border border-dashed px-3 py-2 text-xs font-semibold text-slate-400 transition-all hover:bg-slate-50 has-[:checked]:border-slate-300 has-[:checked]:text-slate-600">
                        <input type="checkbox" name="allowed_methods[]" value="" class="rounded border-slate-300">
                        Any (all active)
                    </label>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Payment Instructions <span class="text-slate-400 font-normal">(shown to customer)</span></label>
                <textarea name="payment_instructions" rows="3" class="focus-ring w-full rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Bank details, crypto address, or any instructions the customer needs to complete payment">{{ old('payment_instructions') }}</textarea>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Internal Notes <span class="text-slate-400 font-normal">(Admin Only — never visible to customer)</span></label>
                <textarea name="internal_notes" rows="3" class="focus-ring w-full rounded-md border border-yellow-200 px-3 py-2 text-sm" placeholder="Internal notes about this payment request">{{ old('internal_notes') }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <button class="focus-ring min-h-11 rounded-md bg-[#0d5368] px-8 text-sm font-bold text-white hover:bg-[#0b4658]">Create Payment Request</button>
                <a href="{{ route('admin.payment-requests.index') }}" class="focus-ring min-h-11 flex items-center rounded-md border border-slate-200 px-5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endSection
