@extends('layouts.app')
@section('title', 'Admin Control Center')
@php $hideHeaderFooter = true; @endphp
@php
    $inTransit = $metrics['in_transit'] ?? 0;
    $delivered = $metrics['delivered'] ?? 0;
    $cancelled = $metrics['cancelled'] ?? 0;
    $totalSignal = max($approvalQueue->count() + $trackedShipments->count() + $paymentQueue->count(), 1);
    function pct($val, $total) { return max(8, round(($val / max($total, 1)) * 100)); }
    function cityAddr($addr) {
        if (!$addr) return 'Not provided';
        $parts = array_filter([$addr['city'] ?? '', $addr['country'] ?? '']);
        return implode(', ', $parts) ?: 'Not provided';
    }
    function money($val) { return '$'.number_format((float)($val ?? 0), 2); }
@endphp
@section('content')
    <x-dashboard-shell title="Admin control center" audience="operations">
        <div x-data="{
            message: '{{ session('success') }}',
            error: '{{ session('error') }}',
            lastTracking: '',
            busyId: null,
            trackingFormat: localStorage.getItem('freightflow_admin_tracking_format') || 'FDX',
            rowFormats: {},
            openDetails: {},
            refreshVersion: 0,
            canApprove: {{ \App\Support\AdminPermissions::can(Auth::user(), 'shipments.approve') ? 'true' : 'false' }},
            canManagePayments: {{ \App\Support\AdminPermissions::can(Auth::user(), 'payments.manage') ? 'true' : 'false' }},
            toggleDetails(id) { this.openDetails[id] = !this.openDetails[id]; },
            init() {
                const saved = localStorage.getItem('freightflow_admin_tracking_format');
                if (saved) this.trackingFormat = saved;
            },
            previewTracking() {
                if (this.trackingFormat === 'AUTO') return 'FDX-{{ date('Y') }}-XXXXXXXX / TRK-XXXXXXXXXX / LOG-XXXXXXXXXX';
                const previews = { FDX: 'FDX-{{ date('Y') }}-8K3P91QZ', TRK: 'TRK-8K3P91QZ10', LOG: 'LOG-A1B2C3D4E5' };
                return previews[this.trackingFormat] || 'FDX-{{ date('Y') }}-8K3P91QZ';
            },
            getRowFormat(shipmentId) {
                return this.rowFormats[shipmentId] || this.trackingFormat;
            },
            scrollTo(id) {
                const el = document.getElementById(id);
                if (!el) return;
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                history.replaceState(null, '', '#' + id);
            },
            async runAction(id, url) {
                this.busyId = id;
                this.error = '';
                this.message = '';
                try {
                    const resp = await fetch(url, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    });
                    const data = await resp.json();
                    if (!resp.ok) throw new Error(data.message || 'Action failed.');
                    this.message = data.message || 'Action completed.';
                    this.lastTracking = data.tracking_number || '';
                    this.refreshVersion++;
                    setTimeout(() => window.location.reload(), 800);
                } catch (err) {
                    this.error = err.message;
                } finally {
                    this.busyId = null;
                }
            }
        }">
            <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @php
                    $dashboardStats = [
                        ['Total Shipments', $metrics['total_shipments'] ?? 0],
                        ['Delivered Shipments', $metrics['delivered'] ?? 0],
                        ['Pending Shipments', $metrics['pending_shipments'] ?? 0],
                        ['In Transit Shipments', $metrics['in_transit'] ?? 0],
                        ['Cancelled Shipments', $metrics['cancelled'] ?? 0],
                        ['Total Revenue', money($metrics['revenue'] ?? 0)],
                        ['Total Customers', $metrics['customers'] ?? 0],
                        ['Total Administrators', $metrics['administrators'] ?? 0],
                    ];
                @endphp
                @foreach($dashboardStats as [$label, $value])
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-navy-900">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mb-6 grid gap-5 xl:grid-cols-[1fr_1fr_0.8fr]">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-bold text-navy-900">Monthly Shipments</h3>
                    <div class="mt-4 h-72"><canvas id="monthlyShipmentsChart"></canvas></div>
                </section>
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-bold text-navy-900">Revenue</h3>
                    <div class="mt-4 h-72"><canvas id="revenueChart"></canvas></div>
                </section>
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-bold text-navy-900">Shipment Status Breakdown</h3>
                    <div class="mt-4 h-72"><canvas id="statusChart"></canvas></div>
                </section>
            </div>

            <div class="mb-6 grid gap-5 xl:grid-cols-2">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-bold text-navy-900">Top Shipping Routes</h3>
                    <div class="mt-4 space-y-3">
                        @forelse($topRoutes as $route)
                            <div class="flex items-center justify-between gap-3 rounded-md bg-slate-50 px-4 py-3 text-sm">
                                <span class="font-semibold text-slate-800">{{ $route['route'] }}</span>
                                <span class="text-slate-500">{{ $route['count'] }} shipments - {{ money($route['revenue']) }}</span>
                            </div>
                        @empty
                            <p class="rounded-md border border-dashed border-slate-200 p-4 text-sm text-slate-500">Routes will appear once shipments are created.</p>
                        @endforelse
                    </div>
                </section>
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-bold text-navy-900">Recent Activity</h3>
                    <div class="mt-4 space-y-3">
                        @forelse($recentActivities as $activity)
                            <div class="rounded-md bg-slate-50 px-4 py-3 text-sm">
                                <p class="font-semibold text-slate-800">{{ str_replace('.', ' ', $activity->action) }}</p>
                                <p class="text-slate-500">{{ $activity->admin?->name ?? 'System' }} - {{ $activity->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        @empty
                            <p class="rounded-md border border-dashed border-slate-200 p-4 text-sm text-slate-500">Audited activity will appear here as administrators work.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div class="space-y-2">
                    <template x-if="message">
                        <p x-text="message" class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700 shadow-sm"></p>
                    </template>
                    <template x-if="error">
                        <p x-text="error" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 shadow-sm"></p>
                    </template>
                    <template x-if="lastTracking">
                        <p class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 shadow-sm">
                            Latest tracking number: <span x-text="lastTracking"></span>
                        </p>
                    </template>
                </div>
                <form method="POST" action="{{ route('admin.dashboard') }}">
                    @csrf
                    <button type="submit" class="focus-ring inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60 min-h-11 px-6 text-sm border border-navy-200 bg-white text-navy-900 hover:border-azure-400 hover:text-azure-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                        Refresh
                    </button>
                </form>
            </div>

            <div id="overview" class="grid gap-5 xl:grid-cols-[1.7fr_1fr] scroll-mt-32">
                <div class="enterprise-panel relative overflow-hidden p-6 sm:p-8">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.12),transparent_36%),radial-gradient(circle_at_bottom_left,rgba(245,166,35,0.12),transparent_32%)]"></div>
                    <div class="relative">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="max-w-2xl">
                                <div class="inline-flex items-center gap-2 rounded-full border border-azure-200 bg-azure-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-azure-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                                    Global command surface
                                </div>
                                <h2 class="mt-4 max-w-3xl font-display text-3xl font-bold tracking-tight text-navy-950 sm:text-4xl">
                                    Enterprise-grade logistics oversight with live operational queues, payment control, and shipment intelligence.
                                </h2>
                                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                                    This workspace is optimized for fast decision-making across approvals, live tracking, and audit-safe payment progression.
                                </p>
                            </div>
                            <div class="grid min-w-[240px] gap-3 rounded-[28px] border border-white/70 bg-white/80 p-4 shadow-card sm:min-w-[280px]">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-12 items-center justify-center rounded-2xl bg-navy-gradient text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.54 15H17a2 2 0 0 0-2 2v4.54"/><path d="M7 3.34V5a3 3 0 0 0 3 3v0a2 2 0 0 1 2 2v0a2 2 0 0 0 2 2v0a2 2 0 0 1 2 2v0c0 .86.44 1.63 1.16 2.07"/><path d="M10.56 2.86A9 9 0 0 0 2.86 10.56"/><path d="M22 12c0 5.52-4.48 10-10 10"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-navy-950">Global network status</p>
                                        <p class="text-xs text-slate-500">API, realtime, and queue orchestration aligned</p>
                                    </div>
                                </div>
                                <div class="grid gap-2 text-sm">
                                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-2">
                                        <span class="text-slate-600">Authenticated API</span>
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Healthy</span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-2">
                                        <span class="text-slate-600">Realtime tracking</span>
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Streaming</span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-2">
                                        <span class="text-slate-600">Audit compliance</span>
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <button type="button" @@click="scrollTo('shipment-queues')" class="focus-ring inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60 min-h-11 px-6 text-sm bg-azure-500 text-white shadow-glow hover:bg-azure-600 hover:-translate-y-0.5 active:translate-y-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 9.4 7.5 4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                                Review approval queue
                            </button>
                            <button type="button" @@click="scrollTo('live-operations')" class="focus-ring inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60 min-h-11 px-6 text-sm border border-navy-200 bg-white text-navy-900 hover:border-azure-400 hover:text-azure-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M10 18h4"/><path d="M10 14h4"/></svg>
                                Open live operations
                            </button>
                            <button type="button" @@click="scrollTo('audit-logs')" class="focus-ring inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60 min-h-11 px-6 text-sm border border-navy-200 bg-white text-navy-900 hover:border-azure-400 hover:text-azure-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                Audit security posture
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5">
                    <div class="enterprise-panel p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Workflow radar</p>
                                <h3 class="mt-2 text-xl font-bold text-navy-950">Operational signals</h3>
                            </div>
                            <div class="rounded-full bg-navy-950 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-white">Live</div>
                        </div>
                        <div class="mt-5 space-y-4">
            @php
                $signals = [
                    ['label' => 'Approval pipeline', 'value' => $approvalQueue->count(), 'helper' => $approvalQueue->isEmpty() ? 'No shipment requests waiting.' : 'Shipment requests queued for review.', 'tone' => 'bg-blue-500'],
                    ['label' => 'Live tracking', 'value' => $trackedShipments->count(), 'helper' => $trackedShipments->isEmpty() ? 'No active tracked consignments.' : 'Tracked consignments visible on the operations map.', 'tone' => 'bg-emerald-500'],
                    ['label' => 'Payment verification', 'value' => $paymentQueue->count(), 'helper' => $paymentQueue->isEmpty() ? 'No paid stages need verification.' : 'Paid stages need manual clearance.', 'tone' => 'bg-amber-500'],
                ];
            @endphp
                            @foreach($signals as $signal)
                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                        <span class="font-semibold text-navy-950">{{ $signal['label'] }}</span>
                                        <span class="text-slate-500">{{ $signal['value'] }}</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full {{ $signal['tone'] }}" style="width: {{ pct($signal['value'], $totalSignal) }}%"></div>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500">{{ $signal['helper'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="enterprise-panel p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Recent route coverage</p>
                        <div class="mt-4 space-y-3">
                            @forelse($trackedShipments->take(4) as $shipment)
                                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="font-semibold text-navy-950">{{ $shipment->tracking_number ?? 'REQ-'.$shipment->id }}</p>
                                        <span class="rounded-full bg-white px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ str_replace('_', ' ', $shipment->status) }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ cityAddr($shipment->origin_address) }}
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-1 inline"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                        {{ cityAddr($shipment->destination_address) }}
                                    </p>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">Tracked routes will appear here once shipments are in motion.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                @php
                    $metricCards = [
                        ['Active shipments', (string)($metrics['active_shipments'] ?? 0), 'Boxes'],
                        ['Pending approvals', (string)$approvalQueue->count(), 'PackageCheck'],
                        ['Delivered', (string)$delivered, 'CheckCircle2'],
                        ['Cancelled', (string)$cancelled, 'Clock3'],
                        ['Pending payments', money($metrics['pending_payment_amount'] ?? 0), 'CreditCard'],
                        ['Revenue', money($metrics['revenue'] ?? 0), 'TrendingUp'],
                        ['Open tickets', (string)($metrics['open_tickets'] ?? 0), 'Activity'],
                    ];
                @endphp
                @foreach($metricCards as [$label, $value, $icon])
                    <div class="overflow-hidden rounded-[28px] border border-white/60 bg-[rgba(255,255,255,0.82)] shadow-card backdrop-blur-xl">
                        <div class="relative p-6">
                            <div class="absolute right-0 top-0 h-20 w-20 rounded-full bg-azure-100/70 blur-2xl"></div>
                            @php
                                $icons = [
                                    'Boxes' => '<path d="M16.5 9.4 7.5 4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
                                    'PackageCheck' => '<path d="m16 16 2 2 4-4"/><path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"/><path d="m7.5 4.27 9 5.15"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" x2="12" y1="22" y2="12"/>',
                                    'CheckCircle2' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
                                    'Clock3' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
                                    'CreditCard' => '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
                                    'TrendingUp' => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
                                    'Activity' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
                                ];
                            @endphp
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="relative mb-4">
                                {!! $icons[$icon] !!}
                            </svg>
                            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-800">{{ $value }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 grid gap-5 xl:grid-cols-[1.3fr_0.7fr]">
                @if(\App\Support\AdminPermissions::can(Auth::user(), 'shipments.create'))
                    <div class="rounded-[28px] border border-white/60 bg-[rgba(255,255,255,0.82)] shadow-card backdrop-blur-xl">
                        <div class="p-6">
                            <h2 class="text-xl font-bold text-slate-800">Create New Shipment</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Fill in sender, receiver, parcel, cost, and payment details to create a new shipment with tracking.</p>
                            <a href="{{ route('admin.shipments.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-azure-500 px-5 py-2.5 text-sm font-semibold text-white shadow-glow hover:bg-azure-600 hover:-translate-y-0.5 active:translate-y-0 transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                Open Create Shipment
                            </a>
                        </div>
                    </div>
                @else
                    <div class="rounded-[28px] border border-white/60 bg-[rgba(255,255,255,0.82)] shadow-card backdrop-blur-xl">
                        <div class="p-6">
                            <h2 class="text-xl font-bold text-slate-800">Shipment creation</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Your current admin role can review operational data but cannot create shipments from the control center.
                            </p>
                        </div>
                    </div>
                @endif

                <div class="grid gap-4">
                    @php
                        $infoCards = [
                            ['ShieldCheck', 'Security posture', 'Only validated operational roles can access the portal, and capability overrides remain auditable.'],
                            ['LockKeyhole', 'Payment control', 'Future stages remain locked until the previous paid stage is verified and released by operations.'],
                            ['CheckCircle2', 'Operational traceability', 'Approvals, status changes, payment verification, and account governance are recorded as enterprise audit events.'],
                        ];
                    @endphp
                    @foreach($infoCards as [$icon, $title, $body])
                        <div class="rounded-[28px] border border-white/60 bg-[rgba(255,255,255,0.82)] shadow-card backdrop-blur-xl">
                            <div class="p-6">
                                @php
                                    $infoIcons = [
                                        'ShieldCheck' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
                                        'LockKeyhole' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
                                        'CheckCircle2' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
                                    ];
                                @endphp
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1e3a5f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3">
                                    {!! $infoIcons[$icon] !!}
                                </svg>
                                <h3 class="font-bold text-slate-800">{{ $title }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $body }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if(\App\Support\AdminPermissions::can(Auth::user(), 'users.view'))
                <div id="user-control" class="mt-6 scroll-mt-32">
                    @livewire('admin-user-control')
                </div>
            @endif

            <div id="shipment-queues" class="mt-6 grid gap-5 scroll-mt-32 xl:grid-cols-[1fr_380px]">
                <div class="rounded-[28px] border border-white/60 bg-[rgba(255,255,255,0.82)] shadow-card backdrop-blur-xl">
                    <div class="p-6 sm:p-7">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold text-slate-800">Shipment requests awaiting approval</h2>
                                <p class="mt-1 text-sm text-slate-600">Tracking numbers are generated only after administrator approval.</p>
                            </div>
                            <div class="flex flex-wrap items-end gap-2">
                                <div>
                                    <span class="mb-1.5 block text-sm font-medium text-navy-800">Tracking format</span>
                                    <select x-model="trackingFormat" @@change="localStorage.setItem('freightflow_admin_tracking_format', trackingFormat)" class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 appearance-none pr-10 transition-colors hover:border-navy-300 focus:border-azure-400">
                                        <option value="FDX">FDX (FDX-YYYY-XXXXXXXX)</option>
                                        <option value="TRK">TRK (TRK-XXXXXXXXXX)</option>
                                        <option value="LOG">LOG (LOG-XXXXXXXXXX)</option>
                                        <option value="AUTO">Auto select</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Preview: <span x-text="previewTracking()"></span></p>

                        {{-- Mobile approval queue --}}
                        <div class="mt-5 space-y-4 lg:hidden">
                            @forelse($approvalQueue as $shipment)
                                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-navy-950">REQ-{{ $shipment->id }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ $shipment->user?->company ?? $shipment->user?->name ?? $shipment->sender_name }}</p>
                                        </div>
                                        <span class="rounded-full bg-white px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ str_replace('_', ' ', $shipment->service_level) }}</span>
                                    </div>
                                    <div class="mt-4 grid gap-2 text-sm text-slate-600">
                                        <p><span class="font-semibold text-slate-900">Origin:</span> {{ cityAddr($shipment->origin_address) }}</p>
                                        <p><span class="font-semibold text-slate-900">Destination:</span> {{ cityAddr($shipment->destination_address) }}</p>
                                        @php $meta = $shipment->metadata ?? []; @endphp
                                        @if($meta['shipment_reference'] ?? null)
                                            <p><span class="font-semibold text-slate-900">Reference:</span> {{ $meta['shipment_reference'] }}</p>
                                        @endif
                                        @if(($meta['packages'] ?? null) && count($meta['packages']) > 0)
                                            <p><span class="font-semibold text-slate-900">Packages:</span> {{ count($meta['packages']) }} item(s) · {{ $meta['packages'][0]['category'] ?? 'General' }} · {{ $meta['packages'][0]['weight'] ?? $shipment->weight_kg }} kg</p>
                                        @endif
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" @@click="toggleDetails('meta-{{ $shipment->id }}')" class="text-xs font-semibold text-azure-600 hover:text-azure-800">
                                            <span x-show="!openDetails['meta-{{ $shipment->id }}']">+ Show full details</span>
                                            <span x-show="openDetails['meta-{{ $shipment->id }}']">− Hide details</span>
                                        </button>
                                        <div x-show="openDetails['meta-{{ $shipment->id }}']" x-cloak class="mt-3 space-y-2 rounded-2xl border border-slate-200 bg-white p-3 text-xs">
                                            @php $meta = $shipment->metadata ?? []; @endphp
                                            @if(($meta['sender'] ?? null))
                                                <div><span class="font-semibold text-navy-900">Sender:</span><br>
                                                    {{ $meta['sender']['company_name'] ?? $shipment->sender_name }} ·
                                                    {{ $meta['sender']['email'] ?? '-' }} ·
                                                    {{ $meta['sender']['phone'] ?? '-' }}
                                                </div>
                                            @endif
                                            @if(($meta['receiver'] ?? null))
                                                <div><span class="font-semibold text-navy-900">Receiver:</span><br>
                                                    {{ $meta['receiver']['company_name'] ?? $shipment->recipient_name }} ·
                                                    {{ $meta['receiver']['email'] ?? '-' }} ·
                                                    {{ $meta['receiver']['phone'] ?? '-' }}
                                                </div>
                                            @endif
                                            @if(($meta['packages'] ?? null) && count($meta['packages']) > 0)
                                                <div><span class="font-semibold text-navy-900">Package{{ count($meta['packages']) > 1 ? 's' : '' }}:</span><br>
                                                    @foreach($meta['packages'] as $i => $pkg)
                                                        {{ $pkg['packageName'] ?? 'Item '.($i+1) }}:
                                                        {{ $pkg['category'] ?? '-' }} · {{ $pkg['packageType'] ?? '-' }} ·
                                                        {{ $pkg['quantity'] ?? 1 }}x {{ $pkg['weight'] ?? '-' }}kg ·
                                                        {{ $pkg['length'] ?? '-' }}×{{ $pkg['width'] ?? '-' }}×{{ $pkg['height'] ?? '-' }}cm
                                                        @if($pkg['declaredValue'] ?? null) · ${{ number_format($pkg['declaredValue'], 2) }} @endif
                                                        @if(!$loop->last)<br>@endif
                                                    @endforeach
                                                </div>
                                            @endif
                                            @if(($meta['logistics'] ?? null))
                                                <div><span class="font-semibold text-navy-900">Logistics:</span><br>
                                                    @if($meta['logistics']['warehouse_name'] ?? null)Warehouse: {{ $meta['logistics']['warehouse_name'] }} · @endif
                                                    @if($meta['logistics']['driver_name'] ?? null)Driver: {{ $meta['logistics']['driver_name'] }} · @endif
                                                    @if($meta['logistics']['pickup_date'] ?? null)Pickup: {{ $meta['logistics']['pickup_date'] }} @endif
                                                </div>
                                            @endif
                                            @if(($meta['workflow'] ?? null))
                                                <div><span class="font-semibold text-navy-900">Workflow:</span>
                                                    {{ $meta['workflow']['template_name'] ?? '-' }} ·
                                                    {{ $meta['workflow']['package_type'] ?? '-' }} ·
                                                    Payment: {{ ($meta['workflow']['payment_enabled'] ?? true) ? 'Enabled' : 'Disabled' }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <span class="mb-1.5 block text-sm font-medium text-navy-800">Tracking format</span>
                                        <select x-model="rowFormats[{{ $shipment->id }}]" class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 appearance-none pr-10 transition-colors hover:border-navy-300 focus:border-azure-400">
                                            <option value="FDX">FDX</option>
                                            <option value="TRK">TRK</option>
                                            <option value="LOG">LOG</option>
                                            <option value="AUTO">AUTO</option>
                                        </select>
                                    </div>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <button type="button" @@click="runAction('reject-{{ $shipment->id }}', '{{ route("admin.dashboard") }}?reject={{ $shipment->id }}')" ::disabled="!canApprove || busyId === 'reject-{{ $shipment->id }}'" class="focus-ring inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60 min-h-11 px-6 text-sm border border-navy-200 bg-white text-navy-900 hover:border-azure-400 hover:text-azure-600">
                                            Reject
                                        </button>
                                        <button type="button" @@click="runAction('approve-{{ $shipment->id }}', '{{ route("admin.dashboard") }}?approve={{ $shipment->id }}&format=' + (rowFormats[{{ $shipment->id }}] || trackingFormat))" ::disabled="!canApprove || busyId === 'approve-{{ $shipment->id }}'" class="focus-ring inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60 min-h-11 px-6 text-sm bg-azure-500 text-white shadow-glow hover:bg-azure-600 hover:-translate-y-0.5 active:translate-y-0">
                                            Approve
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">No shipment requests are waiting for tracking number generation.</div>
                            @endforelse
                        </div>

                        {{-- Desktop approval queue --}}
                        <div class="mt-5 hidden overflow-x-auto lg:block">
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
                                    @forelse($approvalQueue as $shipment)
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
                                                <select x-model="rowFormats[{{ $shipment->id }}]" class="focus-ring min-h-10 w-full rounded-xl border border-navy-200 bg-white px-3 text-sm text-navy-900 appearance-none pr-8 transition-colors hover:border-navy-300 focus:border-azure-400">
                                                    <option value="FDX">FDX</option>
                                                    <option value="TRK">TRK</option>
                                                    <option value="LOG">LOG</option>
                                                    <option value="AUTO">AUTO</option>
                                                </select>
                                            </td>
                                            <td class="border-b border-slate-100 p-3">
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" @@click="runAction('reject-{{ $shipment->id }}', '{{ route("admin.dashboard") }}?reject={{ $shipment->id }}')" ::disabled="!canApprove || busyId === 'reject-{{ $shipment->id }}'" class="focus-ring inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60 min-h-9 px-4 text-[13px] border border-navy-200 bg-white text-navy-900 hover:border-azure-400 hover:text-azure-600">
                                                        Reject
                                                    </button>
                                                    <button type="button" @@click="runAction('approve-{{ $shipment->id }}', '{{ route("admin.dashboard") }}?approve={{ $shipment->id }}&format=' + (rowFormats[{{ $shipment->id }}] || trackingFormat))" ::disabled="!canApprove || busyId === 'approve-{{ $shipment->id }}'" class="focus-ring inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60 min-h-9 px-4 text-[13px] bg-azure-500 text-white shadow-glow hover:bg-azure-600 hover:-translate-y-0.5 active:translate-y-0">
                                                        Approve
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr x-show="openDetails['meta-{{ $shipment->id }}']" x-cloak>
                                            <td colspan="8" class="border-b border-slate-100 bg-slate-50/60 p-4">
                                                @php $meta = $shipment->metadata ?? []; @endphp
                                                <div class="grid gap-x-6 gap-y-3 text-xs sm:grid-cols-2 lg:grid-cols-3">
                                                    @if(($meta['sender'] ?? null))
                                                        <div>
                                                            <span class="font-semibold text-navy-900">Sender</span><br>
                                                            <span class="text-slate-600">{{ $meta['sender']['company_name'] ?? $shipment->sender_name }}<br>
                                                            {{ $meta['sender']['email'] ?? '-' }} · {{ $meta['sender']['phone'] ?? '-' }}</span>
                                                        </div>
                                                    @endif
                                                    @if(($meta['receiver'] ?? null))
                                                        <div>
                                                            <span class="font-semibold text-navy-900">Receiver</span><br>
                                                            <span class="text-slate-600">{{ $meta['receiver']['company_name'] ?? $shipment->recipient_name }}<br>
                                                            {{ $meta['receiver']['email'] ?? '-' }} · {{ $meta['receiver']['phone'] ?? '-' }}</span>
                                                        </div>
                                                    @endif
                                                    @if(($meta['shipment_reference'] ?? null))
                                                        <div>
                                                            <span class="font-semibold text-navy-900">Reference</span><br>
                                                            <span class="text-slate-600">{{ $meta['shipment_reference'] }}</span>
                                                        </div>
                                                    @endif
                                                    @if(($meta['packages'] ?? null) && count($meta['packages']) > 0)
                                                        <div class="sm:col-span-2 lg:col-span-3">
                                                            <span class="font-semibold text-navy-900">Package{{ count($meta['packages']) > 1 ? 's' : '' }} ({{ count($meta['packages']) }})</span><br>
                                                            <span class="text-slate-600">
                                                            @foreach($meta['packages'] as $i => $pkg)
                                                                {{ $pkg['packageName'] ?? 'Item '.($i+1) }}:
                                                                {{ $pkg['category'] ?? '-' }} · {{ $pkg['packageType'] ?? '-' }} ·
                                                                {{ $pkg['quantity'] ?? 1 }}x {{ $pkg['weight'] ?? '-' }}kg ·
                                                                {{ $pkg['length'] ?? '-' }}×{{ $pkg['width'] ?? '-' }}×{{ $pkg['height'] ?? '-' }}cm
                                                                @if($pkg['declaredValue'] ?? null) · ${{ number_format($pkg['declaredValue'], 2) }} @endif
                                                                @if(!$loop->last)<br>@endif
                                                            @endforeach
                                                            </span>
                                                        </div>
                                                    @endif
                                                    @if(($meta['logistics'] ?? null))
                                                        <div>
                                                            <span class="font-semibold text-navy-900">Logistics</span><br>
                                                            <span class="text-slate-600">
                                                                @if($meta['logistics']['warehouse_name'] ?? null)Warehouse: {{ $meta['logistics']['warehouse_name'] }}<br>@endif
                                                                @if($meta['logistics']['driver_name'] ?? null)Driver: {{ $meta['logistics']['driver_name'] }}<br>@endif
                                                                @if($meta['logistics']['pickup_date'] ?? null)Pickup: {{ $meta['logistics']['pickup_date'] }}<br>@endif
                                                                @if($meta['logistics']['estimated_delivery_date'] ?? null)Est. delivery: {{ $meta['logistics']['estimated_delivery_date'] }} @endif
                                                            </span>
                                                        </div>
                                                    @endif
                                                    @if(($meta['workflow'] ?? null))
                                                        <div>
                                                            <span class="font-semibold text-navy-900">Workflow</span><br>
                                                            <span class="text-slate-600">
                                                                {{ $meta['workflow']['template_name'] ?? '-' }} ·
                                                                {{ $meta['workflow']['package_type'] ?? '-' }}<br>
                                                                Payment: {{ ($meta['workflow']['payment_enabled'] ?? true) ? 'Enabled' : 'Disabled' }} ·
                                                                Insurance: {{ ($meta['workflow']['insurance'] ?? 'None') }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="p-4 text-center text-slate-500" colspan="8">No shipment requests are waiting for tracking number generation.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Generated tracking numbers --}}
                        <div class="mt-8">
                            <h3 class="text-lg font-bold text-slate-800">Generated tracking numbers and shipment details</h3>
                            <p class="mt-1 text-sm text-slate-600">Recently approved shipments with tracking IDs, routing, value, and latest activity.</p>

                            {{-- Mobile tracked --}}
                            <div class="mt-4 grid gap-4 lg:hidden">
                                @forelse($trackedShipments as $shipment)
                                    <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-navy-950">{{ $shipment->tracking_number }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ $shipment->user?->company ?? $shipment->user?->name ?? 'User '.$shipment->user_id }}</p>
                                            </div>
                                            <span class="rounded-full bg-white px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ str_replace('_', ' ', $shipment->status) }}</span>
                                        </div>
                                        <div class="mt-4 grid gap-2 text-sm text-slate-600">
                                            <p><span class="font-semibold text-slate-900">Route:</span> {{ cityAddr($shipment->origin_address) }} → {{ cityAddr($shipment->destination_address) }}</p>
                                            <p><span class="font-semibold text-slate-900">Service:</span> {{ str_replace('_', ' ', $shipment->service_level) }}</p>
                                            <p><span class="font-semibold text-slate-900">Declared:</span> {{ money($shipment->declared_value) }}</p>
                                            <p><span class="font-semibold text-slate-900">Latest event:</span> {{ $shipment->trackingEvents?->first()?->description ?? 'No tracking events yet' }}</p>
                                        </div>
                                        @php $meta = $shipment->metadata ?? []; @endphp
                                        @if(($meta['packages'] ?? null) || ($meta['logistics'] ?? null) || ($meta['workflow'] ?? null))
                                            <div class="mt-2">
                                                <button type="button" @@click="toggleDetails('tracked-{{ $shipment->id }}')" class="text-xs font-semibold text-azure-600 hover:text-azure-800">
                                                    <span x-show="!openDetails['tracked-{{ $shipment->id }}']">+ Show packages & logistics</span>
                                                    <span x-show="openDetails['tracked-{{ $shipment->id }}']">− Hide details</span>
                                                </button>
                                                <div x-show="openDetails['tracked-{{ $shipment->id }}']" x-cloak class="mt-2 space-y-2 rounded-2xl border border-slate-200 bg-white p-3 text-xs">
                                                    @if(($meta['packages'] ?? null) && count($meta['packages']) > 0)
                                                        <div><span class="font-semibold text-navy-900">Package{{ count($meta['packages']) > 1 ? 's' : '' }}:</span><br>
                                                            @foreach($meta['packages'] as $i => $pkg)
                                                                {{ $pkg['packageName'] ?? 'Item '.($i+1) }}:
                                                                {{ $pkg['category'] ?? '-' }} · {{ $pkg['packageType'] ?? '-' }} ·
                                                                {{ $pkg['quantity'] ?? 1 }}x {{ $pkg['weight'] ?? '-' }}kg ·
                                                                {{ $pkg['length'] ?? '-' }}×{{ $pkg['width'] ?? '-' }}×{{ $pkg['height'] ?? '-' }}cm
                                                                @if($pkg['declaredValue'] ?? null) · ${{ number_format($pkg['declaredValue'], 2) }} @endif
                                                                @if(!$loop->last)<br>@endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    @if(($meta['logistics'] ?? null))
                                                        <div><span class="font-semibold text-navy-900">Logistics:</span><br>
                                                            @if($meta['logistics']['warehouse_name'] ?? null)Warehouse: {{ $meta['logistics']['warehouse_name'] }} · @endif
                                                            @if($meta['logistics']['driver_name'] ?? null)Driver: {{ $meta['logistics']['driver_name'] }} · @endif
                                                            @if($meta['logistics']['pickup_date'] ?? null)Pickup: {{ $meta['logistics']['pickup_date'] }} @endif
                                                        </div>
                                                    @endif
                                                    @if(($meta['workflow'] ?? null))
                                                        <div><span class="font-semibold text-navy-900">Workflow:</span>
                                                            {{ $meta['workflow']['template_name'] ?? '-' }} · {{ $meta['workflow']['package_type'] ?? '-' }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">No generated tracking numbers yet.</div>
                                @endforelse
                            </div>

                            {{-- Desktop tracked --}}
                            <div class="mt-4 hidden overflow-x-auto lg:block">
                                <table class="w-full min-w-[1060px] text-left text-sm">
                                    <thead class="text-xs uppercase text-slate-500">
                                        <tr>
                                            <th class="border-b p-3">Tracking</th>
                                            <th class="border-b p-3">Account</th>
                                            <th class="border-b p-3">Sender</th>
                                            <th class="border-b p-3">Recipient</th>
                                            <th class="border-b p-3">Route</th>
                                            <th class="border-b p-3">Status</th>
                                            <th class="border-b p-3">Service</th>
                                            <th class="border-b p-3">Weight</th>
                                            <th class="border-b p-3">Declared</th>
                                            <th class="border-b p-3">Latest event</th>
                                            <th class="border-b p-3">Details</th>
                                            <th class="border-b p-3">Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($trackedShipments as $shipment)
                                            <tr>
                                                <td class="border-b border-slate-100 p-3 font-semibold text-slate-800">{{ $shipment->tracking_number }}</td>
                                                <td class="border-b border-slate-100 p-3">{{ $shipment->user?->company ?? $shipment->user?->name ?? 'User '.$shipment->user_id }}</td>
                                                <td class="border-b border-slate-100 p-3">{{ $shipment->sender_name }}</td>
                                                <td class="border-b border-slate-100 p-3">{{ $shipment->recipient_name }}</td>
                                                <td class="border-b border-slate-100 p-3">{{ cityAddr($shipment->origin_address) }} → {{ cityAddr($shipment->destination_address) }}</td>
                                                <td class="border-b border-slate-100 p-3">{{ str_replace('_', ' ', $shipment->status) }}</td>
                                                <td class="border-b border-slate-100 p-3">{{ str_replace('_', ' ', $shipment->service_level) }}</td>
                                                <td class="border-b border-slate-100 p-3">{{ $shipment->weight_kg }} kg</td>
                                                <td class="border-b border-slate-100 p-3">{{ money($shipment->declared_value) }}</td>
                                                <td class="border-b border-slate-100 p-3">{{ $shipment->trackingEvents?->first()?->description ?? 'No tracking events yet' }}</td>
                                                <td class="border-b border-slate-100 p-3">
                                                    <button type="button" @@click="toggleDetails('tracked-{{ $shipment->id }}')" class="focus-ring inline-flex items-center gap-1.5 rounded-lg border border-navy-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-navy-700 transition-colors hover:border-azure-400 hover:text-azure-700">
                                                        <svg x-show="!openDetails['tracked-{{ $shipment->id }}']" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                                        <svg x-show="openDetails['tracked-{{ $shipment->id }}']" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                                        <span x-show="!openDetails['tracked-{{ $shipment->id }}']">View</span>
                                                        <span x-show="openDetails['tracked-{{ $shipment->id }}']">Hide</span>
                                                    </button>
                                                </td>
                                                <td class="border-b border-slate-100 p-3">{{ $shipment->trackingEvents?->first()?->occurred_at ? \Carbon\Carbon::parse($shipment->trackingEvents->first()->occurred_at)->format('M d, Y H:i') : '-' }}</td>
                                            </tr>
                                            <tr x-show="openDetails['tracked-{{ $shipment->id }}']" x-cloak>
                                                <td colspan="12" class="border-b border-slate-100 bg-slate-50/60 p-4">
                                                    @php $meta = $shipment->metadata ?? []; @endphp
                                                    <div class="grid gap-x-6 gap-y-3 text-xs sm:grid-cols-2 lg:grid-cols-3">
                                                        @if(($meta['packages'] ?? null) && count($meta['packages']) > 0)
                                                            <div class="sm:col-span-2 lg:col-span-3">
                                                                <span class="font-semibold text-navy-900">Package{{ count($meta['packages']) > 1 ? 's' : '' }} ({{ count($meta['packages']) }})</span><br>
                                                                <span class="text-slate-600">
                                                                @foreach($meta['packages'] as $i => $pkg)
                                                                    {{ $pkg['packageName'] ?? 'Item '.($i+1) }}:
                                                                    {{ $pkg['category'] ?? '-' }} · {{ $pkg['packageType'] ?? '-' }} ·
                                                                    {{ $pkg['quantity'] ?? 1 }}x {{ $pkg['weight'] ?? '-' }}kg ·
                                                                    {{ $pkg['length'] ?? '-' }}×{{ $pkg['width'] ?? '-' }}×{{ $pkg['height'] ?? '-' }}cm
                                                                    @if($pkg['declaredValue'] ?? null) · ${{ number_format($pkg['declaredValue'], 2) }} @endif
                                                                    @if(!$loop->last)<br>@endif
                                                                @endforeach
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(($meta['logistics'] ?? null))
                                                            <div>
                                                                <span class="font-semibold text-navy-900">Logistics</span><br>
                                                                <span class="text-slate-600">
                                                                    @if($meta['logistics']['warehouse_name'] ?? null)Warehouse: {{ $meta['logistics']['warehouse_name'] }}<br>@endif
                                                                    @if($meta['logistics']['driver_name'] ?? null)Driver: {{ $meta['logistics']['driver_name'] }}<br>@endif
                                                                    @if($meta['logistics']['pickup_date'] ?? null)Pickup: {{ $meta['logistics']['pickup_date'] }}<br>@endif
                                                                    @if($meta['logistics']['estimated_delivery_date'] ?? null)Est. delivery: {{ $meta['logistics']['estimated_delivery_date'] }} @endif
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(($meta['workflow'] ?? null))
                                                            <div>
                                                                <span class="font-semibold text-navy-900">Workflow</span><br>
                                                                <span class="text-slate-600">
                                                                    {{ $meta['workflow']['template_name'] ?? '-' }} ·
                                                                    {{ $meta['workflow']['package_type'] ?? '-' }}<br>
                                                                    Payment: {{ ($meta['workflow']['payment_enabled'] ?? true) ? 'Enabled' : 'Disabled' }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(($meta['sender'] ?? null))
                                                            <div>
                                                                <span class="font-semibold text-navy-900">Sender</span><br>
                                                                <span class="text-slate-600">{{ $meta['sender']['company_name'] ?? '-' }}<br>
                                                                {{ $meta['sender']['email'] ?? '-' }} · {{ $meta['sender']['phone'] ?? '-' }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td class="p-4 text-center text-slate-500" colspan="12">No generated tracking numbers yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5">
                    <div class="rounded-[28px] border border-white/60 bg-[rgba(255,255,255,0.82)] shadow-card backdrop-blur-xl">
                        <div class="p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Shipment lifecycle</p>
                            <h3 class="mt-2 text-xl font-bold text-navy-950">Status distribution</h3>
                            <div class="mt-5 space-y-4">
                                @php($statusDistribution = [
                                    ['In transit', $inTransit, 'bg-azure-500'],
                                    ['Delivered', $delivered, 'bg-emerald-500'],
                                    ['Cancelled', $cancelled, 'bg-rose-500'],
                                ])
                                @foreach($statusDistribution as [$label, $value, $tone])
                                    <div>
                                        <div class="mb-2 flex items-center justify-between text-sm">
                                            <span class="font-semibold text-navy-950">{{ $label }}</span>
                                            <span class="text-slate-500">{{ $value }}</span>
                                        </div>
                                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full {{ $tone }}" style="width: {{ pct($value, max($trackedShipments->count(), 1)) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[28px] border border-white/60 bg-[rgba(255,255,255,0.82)] shadow-card backdrop-blur-xl">
                        <div class="p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Command shortcuts</p>
                            <div class="mt-4 grid gap-3">
                                @php($shortcuts = [
                                    ['Create shipment', \App\Support\AdminPermissions::can(Auth::user(), 'shipments.create')],
                                    ['Approve tracking', \App\Support\AdminPermissions::can(Auth::user(), 'shipments.approve')],
                                    ['Manage payments', \App\Support\AdminPermissions::can(Auth::user(), 'payments.manage')],
                                    ['View audit logs', \App\Support\AdminPermissions::can(Auth::user(), 'audit.view')],
                                ])
                                @foreach($shortcuts as [$label, $enabled])
                                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                                        <span class="font-medium text-slate-700">{{ $label }}</span>
                                        <span class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] {{ $enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">{{ $enabled ? 'Enabled' : 'Restricted' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[28px] border border-white/60 bg-[rgba(255,255,255,0.82)] shadow-card backdrop-blur-xl">
                        <div class="p-6">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Live chat</p>
                                    <h3 class="mt-2 text-xl font-bold text-navy-950">Guest conversations</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($openChatCount > 0)
                                        <span class="inline-flex size-8 items-center justify-center rounded-full bg-rose-500 text-xs font-bold text-white">{{ $openChatCount }}</span>
                                    @else
                                        <span class="inline-flex size-8 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">0</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-4 space-y-3">
                                @foreach($liveChatConversations as $conversation)
                                    <div class="rounded-2xl bg-slate-50 px-4 py-3 {{ $conversation->needsReply ? 'ring-1 ring-amber-300' : '' }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="truncate text-sm font-semibold text-navy-950">
                                                {{ $conversation->guestDisplay }}
                                                @if($conversation->needsReply)
                                                    <span class="ml-1.5 inline-block size-2 rounded-full bg-amber-500 align-middle"></span>
                                                @endif
                                            </p>
                                            <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $conversation->status }}</span>
                                        </div>
                                        @if($conversation->subject)
                                            <p class="mt-0.5 truncate text-xs text-slate-500">Subject: {{ $conversation->subject }}</p>
                                        @endif
                                        @if($conversation->lastMsg)
                                            <p class="mt-1 truncate text-xs text-slate-600">{{ $conversation->lastMsg }}</p>
                                        @endif
                                    </div>
                                @endforeach
                                @if($liveChatConversations->isEmpty())
                                    <p class="rounded-2xl border border-dashed border-slate-200 p-4 text-center text-sm text-slate-500">No open guest conversations.</p>
                                @endif
                            </div>
                            <a href="{{ route('admin.live-chat') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-azure-600 hover:text-azure-800">
                                View all conversations
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @if(\App\Support\AdminPermissions::can(Auth::user(), 'payments.manage'))
                <div id="payment-queue" class="mt-6 scroll-mt-32 rounded-[28px] border border-white/60 bg-[rgba(255,255,255,0.82)] shadow-card backdrop-blur-xl">
                    <div class="p-6 sm:p-7">
                        <h2 class="text-xl font-bold text-slate-800">Payment verification queue</h2>
                        {{-- Mobile payment queue --}}
                        <div class="mt-5 grid gap-4 lg:hidden">
                            @forelse($paymentQueue as $payment)
                                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-navy-950">{{ $payment->shipment?->tracking_number ?? 'Shipment '.$payment->shipment_id }}</p>
                                            <p class="mt-1 text-sm text-slate-500">Stage {{ $payment->stage }} · {{ $payment->label }}</p>
                                        </div>
                                        <span class="rounded-full bg-white px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ str_replace('_', ' ', $payment->status) }}</span>
                                    </div>
                                    <p class="mt-3 text-sm text-slate-600">Amount: {{ money($payment->amount) }}</p>
                                    <div class="mt-4">
                                        <button type="button" @@click="runAction('payment-{{ $payment->id }}', '{{ route("admin.dashboard") }}?verify={{ $payment->id }}')" ::disabled="!canManagePayments || busyId === 'payment-{{ $payment->id }}'" class="focus-ring inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60 min-h-11 px-6 text-sm border border-navy-200 bg-white text-navy-900 hover:border-azure-400 hover:text-azure-600">
                                            Verify
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">No paid payment stages need verification.</div>
                            @endforelse
                        </div>
                        {{-- Desktop payment queue --}}
                        <div class="mt-5 hidden overflow-x-auto lg:block">
                            <table class="w-full min-w-[760px] text-left text-sm">
                                <thead class="text-xs uppercase text-slate-500">
                                    <tr><th class="border-b p-3">Tracking</th><th class="border-b p-3">Stage</th><th class="border-b p-3">Description</th><th class="border-b p-3">Amount</th><th class="border-b p-3">Status</th><th class="border-b p-3"></th></tr>
                                </thead>
                                <tbody>
                                    @forelse($paymentQueue as $payment)
                                        <tr>
                                            <td class="border-b border-slate-100 p-3">{{ $payment->shipment?->tracking_number ?? 'Shipment '.$payment->shipment_id }}</td>
                                            <td class="border-b border-slate-100 p-3">Stage {{ $payment->stage }}</td>
                                            <td class="border-b border-slate-100 p-3">{{ $payment->label }}</td>
                                            <td class="border-b border-slate-100 p-3">{{ money($payment->amount) }}</td>
                                            <td class="border-b border-slate-100 p-3">{{ str_replace('_', ' ', $payment->status) }}</td>
                                            <td class="border-b border-slate-100 p-3 text-right">
                                                <button type="button" @@click="runAction('payment-{{ $payment->id }}', '{{ route("admin.dashboard") }}?verify={{ $payment->id }}')" ::disabled="!canManagePayments || busyId === 'payment-{{ $payment->id }}'" class="focus-ring inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60 min-h-9 px-4 text-[13px] border border-navy-200 bg-white text-navy-900 hover:border-azure-400 hover:text-azure-600">
                                                    Verify
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="p-4 text-center text-slate-500" colspan="6">No paid payment stages need verification.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if(\App\Support\AdminPermissions::can(Auth::user(), 'shipments.update') || \App\Support\AdminPermissions::can(Auth::user(), 'payments.manage'))
                <div id="live-operations" class="mt-6 scroll-mt-32">
                    @livewire('admin-shipment-operations')
                </div>
            @endif

            @if(\App\Support\AdminPermissions::can(Auth::user(), 'audit.view'))
                <div id="audit-logs" class="mt-6 scroll-mt-32">
                    @livewire('admin-audit-log-panel')
                </div>
            @endif
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (!window.Chart) return;
                const chartData = @json($chartData);
                const common = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };
                new Chart(document.getElementById('monthlyShipmentsChart'), {
                    type: 'bar',
                    data: { labels: chartData.months, datasets: [{ label: 'Shipments', data: chartData.shipments, backgroundColor: '#2563eb' }] },
                    options: common
                });
                new Chart(document.getElementById('revenueChart'), {
                    type: 'line',
                    data: { labels: chartData.months, datasets: [{ label: 'Revenue', data: chartData.revenue, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.12)', fill: true, tension: 0.35 }] },
                    options: common
                });
                new Chart(document.getElementById('statusChart'), {
                    type: 'doughnut',
                    data: { labels: chartData.status_labels, datasets: [{ data: chartData.status_values, backgroundColor: ['#f59e0b', '#3b82f6', '#059669', '#dc2626'] }] },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            });
        </script>
    </x-dashboard-shell>
@endsection
