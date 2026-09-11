@extends('layouts.app')
@section('title', 'Shipment Management')
@php
$hideHeaderFooter = true;
function cityAddr($addr) { return ($addr['city'] ?? '') . ', ' . ($addr['state'] ?? $addr['country'] ?? ''); }
function money($val) { return '$'.number_format((float)($val ?? 0), 2); }
@endphp
@section('content')
    <x-dashboard-shell title="Shipment Management">
        <div x-data="{
            message: '{{ session('success') }}',
            error: '{{ session('error') }}',
            busyId: null,
            trackingFormat: localStorage.getItem('freightflow_admin_tracking_format') || 'FDX',
            rowFormats: {},
            openDetails: {},
            get canApprove() { return true; },
            runAction(id, url) {
                this.busyId = id;
                fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content } })
                    .then(r => r.json()).then(d => { this.message = d.message; this.busyId = null; location.reload(); })
                    .catch(() => { this.error = 'Action failed'; this.busyId = null; });
            },
            toggleDetails(key) { this.openDetails[key] = !this.openDetails[key]; }
        }">
            <div class="mb-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="font-display text-2xl font-bold text-navy-950">Approval Queue</h2>
                        <p class="mt-1 text-sm text-slate-500">Review and approve pending shipment requests.</p>
                    </div>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="text-xs uppercase text-slate-500">
                            <tr>
                                <th class="border-b p-3">Request</th>
                                <th class="border-b p-3">Sender</th>
                                <th class="border-b p-3">Origin</th>
                                <th class="border-b p-3">Destination</th>
                                <th class="border-b p-3">Service</th>
                                <th class="border-b p-3">Details</th>
                                <th class="border-b p-3">Format</th>
                                <th class="border-b p-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($approvalQueue ?? [] as $shipment)
                                <tr>
                                    <td class="border-b border-slate-100 p-3">REQ-{{ $shipment->id }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ $shipment->user?->company ?? $shipment->user?->name ?? $shipment->sender_name }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ cityAddr($shipment->origin_address) }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ cityAddr($shipment->destination_address) }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ str_replace('_', ' ', $shipment->service_level) }}</td>
                                    <td class="border-b border-slate-100 p-3">
                                        <button type="button" @@click="toggleDetails('meta-{{ $shipment->id }}')" class="focus-ring inline-flex items-center gap-1.5 rounded-lg border border-navy-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-navy-700 transition-colors hover:border-azure-400 hover:text-azure-700">
                                            <svg x-show="!openDetails['meta-{{ $shipment->id }}']" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                            <svg x-show="openDetails['meta-{{ $shipment->id }}']" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                            <span x-show="!openDetails['meta-{{ $shipment->id }}']">View</span>
                                            <span x-show="openDetails['meta-{{ $shipment->id }}']">Hide</span>
                                        </button>
                                    </td>
                                    <td class="border-b border-slate-100 p-3">
                                        <select x-model="rowFormats[{{ $shipment->id }}]" class="focus-ring min-h-10 w-full rounded-xl border border-navy-200 bg-white px-3 text-sm text-navy-900">
                                            <option value="FDX">FDX</option>
                                            <option value="TRK">TRK</option>
                                            <option value="LOG">LOG</option>
                                            <option value="AUTO">AUTO</option>
                                        </select>
                                    </td>
                                    <td class="border-b border-slate-100 p-3">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @@click="runAction('reject-{{ $shipment->id }}', '{{ route("admin.dashboard") }}?reject={{ $shipment->id }}')" ::disabled="!canApprove || busyId === 'reject-{{ $shipment->id }}'" class="focus-ring inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60 min-h-9 px-4 text-[13px] border border-navy-200 bg-white text-navy-900 hover:border-azure-400 hover:text-azure-600">Reject</button>
                                            <button type="button" @@click="runAction('approve-{{ $shipment->id }}', '{{ route("admin.dashboard") }}?approve={{ $shipment->id }}&format=' + (rowFormats[{{ $shipment->id }}] || trackingFormat))" ::disabled="!canApprove || busyId === 'approve-{{ $shipment->id }}'" class="focus-ring inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60 min-h-9 px-4 text-[13px] bg-azure-500 text-white shadow-glow hover:bg-azure-600 hover:-translate-y-0.5 active:translate-y-0">Approve</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="p-6 text-center text-sm text-slate-500">No shipments pending approval.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('admin.shipments') }}" class="grid gap-3 lg:grid-cols-[1fr_180px_180px_160px_160px_auto]">
                    <input name="search" value="{{ request('search') }}" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="Search tracking, sender, receiver">
                    <select name="status" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm">
                        <option value="">All statuses</option>
                        @foreach($statuses as $option)
                            <option value="{{ $option }}" @selected(request('status') === $option)>{{ str_replace('_', ' ', ucfirst($option)) }}</option>
                        @endforeach
                    </select>
                    <input name="destination" value="{{ request('destination') }}" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="Destination">
                    <input name="date_from" value="{{ request('date_from') }}" type="date" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm">
                    <input name="date_to" value="{{ request('date_to') }}" type="date" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm">
                    <button class="focus-ring min-h-11 rounded-md bg-[#0d5368] px-5 text-sm font-bold text-white hover:bg-[#0b4658]">Filter</button>
                </form>
            </div>

            <div class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-display text-2xl font-bold text-navy-950">All Shipments</h2>
                        <p class="mt-1 text-sm text-slate-500">Search, filter, sort by newest, and review real shipment records.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.shipments.export', request()->query()) }}" class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md border border-slate-200 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">Export CSV</a>
                        <a href="{{ route('admin.shipments.create') }}" class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md bg-[#0d5368] px-4 text-sm font-bold text-white hover:bg-[#0b4658]">Create New Shipment</a>
                    </div>
                </div>
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[1100px] text-left text-sm">
                        <thead class="text-xs uppercase text-slate-500">
                            <tr>
                                <th class="border-b p-3">Tracking Number</th>
                                <th class="border-b p-3">Sender Information</th>
                                <th class="border-b p-3">Receiver Information</th>
                                <th class="border-b p-3">Origin</th>
                                <th class="border-b p-3">Destination</th>
                                <th class="border-b p-3">Shipment Type</th>
                                <th class="border-b p-3">Current Status</th>
                                <th class="border-b p-3">Estimated Delivery</th>
                                <th class="border-b p-3">Shipment Date</th>
                                <th class="border-b p-3">Amount</th>
                                <th class="border-b p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shipments as $shipment)
                                <tr>
                                    <td class="border-b border-slate-100 p-3 font-semibold text-slate-900">{{ $shipment->tracking_number ?? 'REQ-'.$shipment->id }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ $shipment->sender_name }}<br><span class="text-xs text-slate-500">{{ data_get($shipment->metadata, 'sender.email') }}</span></td>
                                    <td class="border-b border-slate-100 p-3">{{ $shipment->recipient_name }}<br><span class="text-xs text-slate-500">{{ data_get($shipment->metadata, 'receiver.email') }}</span></td>
                                    <td class="border-b border-slate-100 p-3">{{ cityAddr($shipment->origin_address) }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ cityAddr($shipment->destination_address) }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ str_replace('_', ' ', $shipment->service_level) }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ str_replace('_', ' ', ucfirst($shipment->status)) }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ $shipment->estimated_delivery_at?->format('M d, Y') ?? '-' }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ $shipment->created_at->format('M d, Y') }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ money($shipment->quoted_amount) }}</td>
                                    <td class="border-b border-slate-100 p-3">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('admin.shipments.show', $shipment) }}" class="rounded-md bg-[#0d5368] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#0b4658]">View</a>
                                            <a href="{{ route('admin.shipments.edit', $shipment) }}" class="rounded-md border border-amber-200 px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-50">Edit</a>
                                            @if($shipment->tracking_number)
                                                <a href="{{ route('tracking.number', $shipment->tracking_number) }}" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Track</a>
                                            @endif
                                            @if(\App\Support\AdminPermissions::can(Auth::user(), \App\Support\AdminPermissions::SHIPMENTS_DELETE))
                                                <form method="POST" action="{{ route('admin.shipments.delete', $shipment) }}" onsubmit="return confirm('Delete this shipment and related records?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="p-6 text-center text-sm text-slate-500">No shipments match the current filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $shipments->links() }}</div>
            </div>
        </div>
    </x-dashboard-shell>
@endsection
