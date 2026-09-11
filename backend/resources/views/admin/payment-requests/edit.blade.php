@extends('layouts.admin')
@section('title', 'Edit Payment Request')
@section('content')
<div class="mx-auto max-w-3xl">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d8fa3]">Edit Payment Request #{{ $paymentRequest->id }}</p>
        <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $paymentRequest->title }}</h1>

        <form method="POST" action="{{ route('admin.payment-requests.update', $paymentRequest->id) }}" class="mt-6 grid gap-5">
            @csrf @method('PATCH')

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Title</label>
                    <input name="title" value="{{ old('title', $paymentRequest->title) }}" required class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Category</label>
                    <input name="category" value="{{ old('category', $paymentRequest->category) }}" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm" placeholder="e.g. Customs, Storage, Insurance">
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Priority</label>
                    <select name="priority" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm">
                        @foreach($priorities as $p)
                            <option value="{{ $p }}" @selected(old('priority', $paymentRequest->priority) === $p)>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Status</label>
                    <select name="status" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm">
                        @foreach($statuses as $st)
                            <option value="{{ $st }}" @selected(old('status', $paymentRequest->status) === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Reason</label>
                <textarea name="reason" rows="3" class="focus-ring w-full rounded-md border border-slate-200 px-3 py-2 text-sm">{{ old('reason', $paymentRequest->reason) }}</textarea>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Description</label>
                <textarea name="description" rows="4" class="focus-ring w-full rounded-md border border-slate-200 px-3 py-2 text-sm">{{ old('description', $paymentRequest->description) }}</textarea>
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Amount</label>
                    <input name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount', $paymentRequest->amount) }}" required class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Currency</label>
                    <input name="currency" value="{{ old('currency', $paymentRequest->currency) }}" required class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm" placeholder="USD">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">Due Date</label>
                    <input name="due_at" type="date" value="{{ old('due_at', $paymentRequest->due_at?->format('Y-m-d')) }}" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Allowed Payment Methods</label>
                <div class="flex flex-wrap gap-2">
                    @forelse($activeGateways as $gateway)
                        @php $checked = in_array($gateway->gateway_name, old('allowed_methods', $allowedMethods)); @endphp
                        <label class="flex cursor-pointer items-center gap-1.5 rounded-md border px-3 py-2 text-xs font-semibold transition-all hover:bg-slate-50 has-[:checked]:border-[#0d5368] has-[:checked]:bg-[#0d5368]/5 has-[:checked]:text-[#0d5368]">
                            <input type="checkbox" name="allowed_methods[]" value="{{ $gateway->gateway_name }}" @checked($checked) class="rounded border-slate-300 text-[#0d5368]">
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

            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Payment Instructions (shown to customer)</label>
                <textarea name="payment_instructions" rows="3" class="focus-ring w-full rounded-md border border-slate-200 px-3 py-2 text-sm">{{ old('payment_instructions', $paymentRequest->payment_instructions) }}</textarea>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-500">Internal Notes (Admin Only)</label>
                <textarea name="internal_notes" rows="3" class="focus-ring w-full rounded-md border border-yellow-200 px-3 py-2 text-sm">{{ old('internal_notes', $paymentRequest->internal_notes) }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <button class="focus-ring min-h-11 rounded-md bg-[#0d5368] px-8 text-sm font-bold text-white hover:bg-[#0b4658]">Update Payment Request</button>
                <a href="{{ route('admin.payment-requests.show', $paymentRequest->id) }}" class="focus-ring min-h-11 flex items-center rounded-md border border-slate-200 px-5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endSection
