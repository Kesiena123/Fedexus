@extends('layouts.admin')
@section('title', 'Payment Request: ' . $paymentRequest->title)
@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d8fa3]">Payment Request #{{ $paymentRequest->id }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $paymentRequest->title }}</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.payment-requests.index') }}" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">&larr; Back</a>
            @if(!$paymentRequest->is_archived)
                <a href="{{ route('admin.payment-requests.edit', $paymentRequest->id) }}" class="rounded-md bg-[#0d5368] px-4 py-2 text-sm font-bold text-white hover:bg-[#0b4658]">Edit</a>
            @endif
        </div>
    </div>

    {{-- Detail Cards --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <h3 class="text-sm font-bold text-slate-900">Details</h3>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <div><dt class="text-xs text-slate-500">Tracking Number</dt><dd class="font-mono text-sm font-semibold text-slate-900">{{ $paymentRequest->shipment?->tracking_number ?? 'N/A' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Shipment</dt><dd class="text-sm font-semibold text-slate-900">{{ $paymentRequest->shipment?->recipient_name ?? 'N/A' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Amount</dt><dd class="text-sm font-bold text-slate-900">{{ number_format((float)$paymentRequest->amount, 2) }} {{ $paymentRequest->currency }}</dd></div>
                <div><dt class="text-xs text-slate-500">Category</dt><dd class="text-sm font-semibold text-slate-900">{{ $paymentRequest->category ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Priority</dt><dd class="text-sm font-semibold text-slate-900">{{ $paymentRequest->priorityLabel() }}</dd></div>
                <div><dt class="text-xs text-slate-500">Status</dt><dd><span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $paymentRequest->status === 'paid' || $paymentRequest->status === 'verified' ? 'bg-emerald-100 text-emerald-700' : ($paymentRequest->status === 'cancelled' || $paymentRequest->status === 'failed' ? 'bg-red-100 text-red-700' : ($paymentRequest->status === 'draft' ? 'bg-slate-100 text-slate-600' : 'bg-amber-100 text-amber-700')) }}">{{ $paymentRequest->statusLabel() }}</span></dd></div>
                <div><dt class="text-xs text-slate-500">Due Date</dt><dd class="text-sm font-semibold text-slate-900">{{ $paymentRequest->due_at?->format('M d, Y') ?? 'No due date' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Active</dt><dd class="text-sm font-semibold {{ $paymentRequest->is_active ? 'text-emerald-600' : 'text-red-600' }}">{{ $paymentRequest->is_active ? 'Yes' : 'No' }}</dd></div>
            </dl>
            @if($paymentRequest->reason)
                <div class="mt-4"><dt class="text-xs text-slate-500">Reason</dt><dd class="mt-1 text-sm text-slate-700">{{ $paymentRequest->reason }}</dd></div>
            @endif
            @if($paymentRequest->description)
                <div class="mt-4"><dt class="text-xs text-slate-500">Description</dt><dd class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $paymentRequest->description }}</dd></div>
            @endif
            @if($paymentRequest->payment_instructions)
                <div class="mt-4"><dt class="text-xs text-slate-500">Payment Instructions</dt><dd class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $paymentRequest->payment_instructions }}</dd></div>
            @endif
            @if($paymentRequest->internal_notes)
                <div class="mt-4 rounded-md border border-yellow-200 bg-yellow-50 p-3"><dt class="text-xs font-semibold text-yellow-700">Internal Notes (Admin Only)</dt><dd class="mt-1 whitespace-pre-wrap text-sm text-yellow-800">{{ $paymentRequest->internal_notes }}</dd></div>
            @endif
        </div>

        {{-- Actions Panel --}}
        <div class="space-y-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900">Actions</h3>
                <div class="mt-3 flex flex-col gap-2">
                    @if(!$paymentRequest->is_archived)
                        @if($paymentRequest->is_active)
                            <form method="POST" action="{{ route('admin.payment-requests.toggle-active', $paymentRequest->id) }}" onsubmit="return confirm('Disable this payment request?')">
                                @csrf @method('PATCH')
                                <button class="w-full rounded-md border border-orange-200 px-4 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-50">Disable</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.payment-requests.toggle-active', $paymentRequest->id) }}">
                                @csrf @method('PATCH')
                                <button class="w-full rounded-md border border-emerald-200 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Activate</button>
                            </form>
                        @endif
                    @endif

                    @if(!$paymentRequest->isTerminal() && !$paymentRequest->is_archived)
                        <form method="POST" action="{{ route('admin.payment-requests.cancel', $paymentRequest->id) }}" onsubmit="return confirm('Cancel this payment request?')">
                            @csrf @method('PATCH')
                            <button class="w-full rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Cancel</button>
                        </form>
                        <form method="POST" action="{{ route('admin.payment-requests.expire', $paymentRequest->id) }}" onsubmit="return confirm('Mark as expired?')">
                            @csrf @method('PATCH')
                            <button class="w-full rounded-md border border-orange-200 px-4 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-50">Mark Expired</button>
                        </form>
                    @endif

                    @if($paymentRequest->status === 'awaiting_verification')
                        <form method="POST" action="{{ route('admin.payment-requests.complete', $paymentRequest->id) }}" onsubmit="return confirm('Mark this payment as completed?')">
                            @csrf @method('PATCH')
                            <button class="w-full rounded-md border border-emerald-200 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Mark Completed</button>
                        </form>
                    @endif

                    @if($paymentRequest->isCompleted() && !$paymentRequest->is_archived)
                        <form method="POST" action="{{ route('admin.payment-requests.refund', $paymentRequest->id) }}" onsubmit="return confirm('Mark this payment as refunded? You will need to process the refund through the payment gateway separately.')">
                            @csrf @method('PATCH')
                            <button class="w-full rounded-md border border-cyan-200 px-4 py-2 text-sm font-semibold text-cyan-700 hover:bg-cyan-50">Mark Refunded</button>
                        </form>
                    @endif

                    @if(in_array($paymentRequest->status, ['cancelled', 'expired', 'failed', 'refunded']) && !$paymentRequest->is_archived)
                        <form method="POST" action="{{ route('admin.payment-requests.reopen', $paymentRequest->id) }}">
                            @csrf @method('PATCH')
                            <button class="w-full rounded-md border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">Reopen</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.payment-requests.duplicate', $paymentRequest->id) }}">
                        @csrf @method('POST')
                        <button class="w-full rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Duplicate</button>
                    </form>

                    @if(!$paymentRequest->is_archived)
                        <form method="POST" action="{{ route('admin.payment-requests.archive', $paymentRequest->id) }}" onsubmit="return confirm('Archive this completed payment request?')">
                            @csrf @method('PATCH')
                            <button class="w-full rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Archive</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.payment-requests.restore', $paymentRequest->id) }}">
                            @csrf @method('PATCH')
                            <button class="w-full rounded-md border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">Restore from Archive</button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Extend Due Date --}}
            @if(!$paymentRequest->is_archived && !$paymentRequest->isCompleted())
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900">Extend Due Date</h3>
                    <form method="POST" action="{{ route('admin.payment-requests.extend-due', $paymentRequest->id) }}" class="mt-3">
                        @csrf @method('PATCH')
                        <input type="date" name="due_at" required class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm">
                        <button class="mt-2 w-full rounded-md bg-[#0d5368] px-4 py-2 text-sm font-bold text-white hover:bg-[#0b4658]">Extend</button>
                    </form>
                </div>
            @endif

            {{-- Secure Link --}}
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900">Payment Link</h3>
                <p class="mt-2 text-xs text-slate-500">Share this link with the customer:</p>
                <div class="mt-2 flex gap-2">
                    <input readonly value="{{ route('payment-request.show', $paymentRequest->secure_token) }}" class="flex-1 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-mono text-slate-600" onclick="this.select()">
                </div>
            </div>
        </div>
    </div>

    {{-- Transactions --}}
    @if($paymentRequest->transactions->count() > 0)
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4"><h3 class="text-sm font-bold text-slate-900">Transactions ({{ $paymentRequest->transactions->count() }})</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                        <tr><th class="p-3">Date</th><th class="p-3">Provider</th><th class="p-3">Reference</th><th class="p-3">Amount</th><th class="p-3">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($paymentRequest->transactions as $tx)
                            <tr>
                                <td class="p-3 text-xs text-slate-500">{{ $tx->created_at->format('M d, Y H:i') }}</td>
                                <td class="p-3 font-semibold text-slate-900">{{ ucfirst(str_replace('_', ' ', $tx->provider)) }}</td>
                                <td class="p-3 font-mono text-xs text-slate-600">{{ $tx->provider_reference }}</td>
                                <td class="p-3 font-semibold">{{ number_format((float)$tx->amount, 2) }} {{ $tx->currency }}</td>
                                <td class="p-3">
                                    <span class="rounded px-2 py-0.5 text-xs font-bold {{ in_array($tx->status, ['paid', 'verified']) ? 'bg-emerald-100 text-emerald-700' : ($tx->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($tx->status) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- History --}}
    @if($history->count() > 0)
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4"><h3 class="text-sm font-bold text-slate-900">Change History ({{ $history->count() }})</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                        <tr><th class="p-3">Date / Time</th><th class="p-3">Admin</th><th class="p-3">Action</th><th class="p-3">Details</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($history as $log)
                            @php
                                $actionLabels = [
                                    'payment_request.created' => ['label' => 'Created', 'color' => 'bg-emerald-100 text-emerald-700'],
                                    'payment_request.updated' => ['label' => 'Updated', 'color' => 'bg-blue-100 text-blue-700'],
                                    'payment_request.deleted' => ['label' => 'Deleted', 'color' => 'bg-red-100 text-red-700'],
                                    'payment_request.activated' => ['label' => 'Activated', 'color' => 'bg-emerald-100 text-emerald-700'],
                                    'payment_request.disabled' => ['label' => 'Disabled', 'color' => 'bg-orange-100 text-orange-700'],
                                    'payment_request.cancelled' => ['label' => 'Cancelled', 'color' => 'bg-red-100 text-red-700'],
                                    'payment_request.expired' => ['label' => 'Expired', 'color' => 'bg-orange-100 text-orange-700'],
                                    'payment_request.due_date_extended' => ['label' => 'Due Date Extended', 'color' => 'bg-blue-100 text-blue-700'],
                                    'payment_request.completed' => ['label' => 'Paid', 'color' => 'bg-emerald-100 text-emerald-700'],
                                    'payment_request.refunded' => ['label' => 'Refunded', 'color' => 'bg-cyan-100 text-cyan-700'],
                                    'payment_request.reopened' => ['label' => 'Reopened', 'color' => 'bg-purple-100 text-purple-700'],
                                    'payment_request.archived' => ['label' => 'Archived', 'color' => 'bg-slate-200 text-slate-600'],
                                    'payment_request.restored' => ['label' => 'Restored', 'color' => 'bg-blue-100 text-blue-700'],
                                    'payment_request.duplicated' => ['label' => 'Duplicated', 'color' => 'bg-indigo-100 text-indigo-700'],
                                ];
                                $action = $actionLabels[$log->action] ?? ['label' => str_replace('payment_request.', '', $log->action), 'color' => 'bg-slate-100 text-slate-700'];
                            @endphp
                            <tr>
                                <td class="p-3 text-xs text-slate-500">{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                                <td class="p-3 text-sm font-semibold text-slate-900">{{ $log->admin?->name ?? 'System' }}</td>
                                <td class="p-3">
                                    <span class="rounded px-2 py-0.5 text-xs font-bold {{ $action['color'] }}">{{ $action['label'] }}</span>
                                </td>
                                <td class="p-3 text-xs text-slate-600">
                                    @php
                                        $changes = [];
                                        if ($log->previous_values && $log->new_values) {
                                            $prev = is_array($log->previous_values) ? $log->previous_values : [];
                                            $new = is_array($log->new_values) ? $log->new_values : [];
                                            $fieldLabels = [
                                                'amount' => 'Amount',
                                                'currency' => 'Currency',
                                                'status' => 'Status',
                                                'due_at' => 'Due Date',
                                                'title' => 'Title',
                                                'priority' => 'Priority',
                                                'category' => 'Category',
                                                'payment_instructions' => 'Instructions',
                                                'reason' => 'Reason',
                                            ];
                                            foreach ($fieldLabels as $field => $label) {
                                                if (array_key_exists($field, $prev) && array_key_exists($field, $new) && $prev[$field] != $new[$field]) {
                                                    $old = $prev[$field] ?: '(empty)';
                                                    $newVal = $new[$field] ?: '(empty)';
                                                    if ($field === 'due_at') {
                                                        $old = $prev[$field] ? \Carbon\Carbon::parse($prev[$field])->format('M d, Y') : '(none)';
                                                        $newVal = $new[$field] ? \Carbon\Carbon::parse($new[$field])->format('M d, Y') : '(none)';
                                                    }
                                                    $changes[] = "<strong>{$label}:</strong> {$old} &rarr; {$newVal}";
                                                }
                                            }
                                        }
                                        if ($log->action === 'payment_request.duplicated' && isset($log->metadata['original_title'])) {
                                            $changes[] = 'Duplicated from: <strong>' . e($log->metadata['original_title']) . '</strong>';
                                        }
                                    @endphp
                                    @if(count($changes))
                                        {!! implode('<br>', $changes) !!}
                                    @else
                                        {{ $log->reason ?? '—' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Delete --}}
    @if(!$paymentRequest->is_archived)
        <div class="rounded-lg border border-red-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-red-700">Danger Zone</h3>
            <p class="mt-1 text-xs text-slate-500">Permanently delete this payment request and all associated transactions.</p>
            <form method="POST" action="{{ route('admin.payment-requests.destroy', $paymentRequest->id) }}" class="mt-3" onsubmit="return confirm('Permanently delete this payment request? This cannot be undone.')">
                @csrf @method('DELETE')
                <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700">Delete Payment Request</button>
            </form>
        </div>
    @endif
</div>
@endsection
