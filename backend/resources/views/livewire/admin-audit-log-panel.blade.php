@php
    function auditVal($v) {
        if (is_null($v)) return '<span class="text-slate-400 italic">null</span>';
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_array($v)) {
            $lines = [];
            $isIndexed = array_keys($v) === range(0, count($v) - 1);
            if ($isIndexed) {
                foreach ($v as $item) {
                    if (is_array($item)) {
                        $parts = array_filter([
                            $item['packageName'] ?? $item['name'] ?? null,
                            $item['category'] ?? null,
                            $item['packageType'] ?? null,
                            isset($item['quantity']) ? $item['quantity'].'x' : null,
                            isset($item['weight']) ? $item['weight'].'kg' : null,
                            isset($item['length']) ? $item['length'].'x'.$item['width'].'x'.$item['height'].'cm' : null,
                            isset($item['declaredValue']) ? '$'.number_format($item['declaredValue'], 2) : null,
                        ]);
                        $lines[] = implode(' · ', $parts) ?: 'Item';
                    } else {
                        $lines[] = htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8');
                    }
                }
            } else {
                foreach ($v as $key => $val) {
                    if (is_null($val)) continue;
                    if (is_array($val)) {
                        $sub = auditVal($val);
                        if ($sub !== '') $lines[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8').': '.$sub;
                    } else {
                        $lines[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8').': '.htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
                    }
                }
            }
            $out = implode('<br>', $lines);
            return $out ?: '<span class="text-slate-400 italic">empty</span>';
        }
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
@endphp
<div>
    <x-ui.card class="mt-6">
        <x-ui.card-content>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-navy-900">Audit Logs</h2>
                    <p class="mt-1 text-sm text-slate-600">Search recent administrative actions across account, shipment, and payment changes.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button variant="outline" wire:click="exportCsv" wire:loading.attr="disabled" :disabled="$exporting">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export CSV
                    </x-ui.button>
                    <x-ui.button variant="outline" wire:click="refresh" wire:loading.attr="disabled">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh
                    </x-ui.button>
                </div>
            </div>

            @if (!$canViewAudit)
                <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    You do not have permission to view audit logs.
                </div>
            @else
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-[2fr_1fr_1fr_1fr_1fr_auto]">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-navy-800">Search Logs</label>
                        <input type="text" wire:model.live.debounce="search" placeholder="Action, admin, target, or reason..." class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 placeholder:text-slate-400 transition-colors hover:border-navy-300 focus:border-azure-400" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-navy-800">Action</label>
                        <select wire:model.live="actionFilter" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 appearance-none pr-10">
                            <option value="">All Actions</option>
                            @foreach ($uniqueActions as $action)
                                <option value="{{ $action }}">{{ $action }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-navy-800">Target Type</label>
                        <select wire:model.live="targetTypeFilter" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 appearance-none pr-10">
                            <option value="">All Types</option>
                            @foreach ($uniqueTargetTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-navy-800">From Date</label>
                        <input type="date" wire:model.live="dateFrom" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 transition-colors hover:border-navy-300 focus:border-azure-400" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-navy-800">To Date</label>
                        <input type="date" wire:model.live="dateTo" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 transition-colors hover:border-navy-300 focus:border-azure-400" />
                    </div>
                    <div class="flex items-end">
                        <x-ui.button variant="ghost" wire:click="clearFilters">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Clear
                        </x-ui.button>
                    </div>
                </div>

                <div class="mt-5 grid gap-3">
                    @php $paginatedLogs = $logs; @endphp

                    @forelse ($paginatedLogs as $log)
                        <div x-data="{ openDiff: false }" class="rounded-2xl border border-navy-100 p-4 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-navy-900">{{ $log->action }}</p>
                                    <p class="mt-1 text-slate-600">{{ $log->admin?->name ?? 'System' }} · {{ $log->created_at->format('M j, Y g:i A') }}</p>
                                </div>
                                <svg class="h-5 w-5 text-navy-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <p class="mt-2 text-slate-600">Target: {{ $log->target_type ?? '-' }} {{ $log->target_id ? '#'.$log->target_id : '' }}</p>
                            @if ($log->reason)
                                <p class="mt-2 text-slate-600">Reason: {{ $log->reason }}</p>
                            @endif
                            @if ($log->ip_address)
                                <p class="mt-2 text-xs text-slate-500">IP: {{ $log->ip_address }}</p>
                            @endif
                            @php
                                $skipKeys = ['user_id', 'driver_id', 'warehouse_id'];
                                $prev = $log->previous_values ?? [];
                                $next = $log->new_values ?? [];
                                $allKeys = array_unique(array_merge(array_keys($prev), array_keys($next)));
                                $changes = [];
                                foreach ($allKeys as $k) {
                                    if (in_array($k, $skipKeys, true)) continue;
                                    $pv = $prev[$k] ?? null;
                                    $nv = $next[$k] ?? null;
                                    if (json_encode($pv) !== json_encode($nv)) {
                                        if ($k === 'metadata') {
                                            $metaKeys = array_unique(array_merge(array_keys((array)$pv), array_keys((array)$nv)));
                                            foreach ($metaKeys as $mk) {
                                                if (in_array($mk, ['created_by_admin_id'], true)) continue;
                                                $mpv = $pv[$mk] ?? null;
                                                $mnv = $nv[$mk] ?? null;
                                                if (json_encode($mpv) !== json_encode($mnv)) {
                                                    $changes[] = ['key' => $mk, 'before' => $mpv, 'after' => $mnv];
                                                }
                                            }
                                        } else {
                                            $changes[] = ['key' => $k, 'before' => $pv, 'after' => $nv];
                                        }
                                    }
                                }
                            @endphp
                            @if ($changes)
                                <div class="mt-2">
                                    <button type="button" @@click="openDiff = !openDiff" class="focus-ring inline-flex items-center gap-1.5 rounded-lg border border-navy-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-navy-700 transition-colors hover:border-azure-400 hover:text-azure-700">
                                        <svg x-show="!openDiff" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                        <svg x-show="openDiff" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                        <span x-show="!openDiff">View change details</span>
                                        <span x-show="openDiff">Hide details</span>
                                    </button>
                                    <div x-show="openDiff" x-cloak class="mt-3 overflow-hidden rounded-xl border border-navy-200">
                                        <table class="w-full text-left text-xs">
                                            <thead>
                                                <tr class="bg-slate-50 text-navy-700">
                                                    <th class="px-3 py-2 font-semibold">Field</th>
                                                    <th class="px-3 py-2 font-semibold">Before</th>
                                                    <th class="px-3 py-2 font-semibold">After</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($changes as $c)
                                                    <tr class="border-t border-navy-100">
                                                        <td class="px-3 py-2 font-medium text-navy-900">{{ $c['key'] }}</td>
                                                        <td class="px-3 py-2 text-red-600">{!! auditVal($c['before']) !!}</td>
                                                        <td class="px-3 py-2 text-green-600">{!! auditVal($c['after']) !!}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="py-8 text-center text-sm text-slate-500">
                            No audit log entries matched the current filter.
                        </div>
                    @endforelse

                    <div class="mt-4">
                        {{ $paginatedLogs->links() }}
                    </div>
                </div>
            @endif
        </x-ui.card-content>
    </x-ui.card>

    @script
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('csvReady', (event) => {
                const { filename, path } = event;
                const url = '/storage/exports/' + encodeURIComponent(filename);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                a.click();
            });
        });
    </script>
    @endscript
</div>
