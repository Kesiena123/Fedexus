<div>
    <x-ui.card class="mt-6">
        <x-ui.card-content>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-navy-900">Shipment Operations</h2>
                    <p class="mt-1 text-sm text-slate-600">Update live status, location, checkpoints, and unlocked payment stages from the admin control center.</p>
                </div>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    Real-time tracking control
                </div>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[340px_1fr]">
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-navy-800">Tracked Shipments</label>
                        <select wire:model.live="selectedShipmentId" class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 transition-colors hover:border-navy-300 focus:border-azure-400 appearance-none pr-10">
                            <option value="">Select a shipment...</option>
                            @foreach ($shipments as $s)
                                <option value="{{ $s['id'] }}">{{ $s['tracking_number'] ?? 'REQ-'.$s['id'] }} · {{ $s['recipient_name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="max-h-[380px] space-y-3 overflow-auto rounded-2xl border border-navy-100 p-3">
                        @forelse ($shipments as $s)
                            <button type="button" wire:click="selectShipment({{ $s['id'] }})"
                                class="block w-full rounded-2xl border px-4 py-3 text-left text-sm transition-colors
                                    {{ $selectedShipmentId === $s['id'] ? 'border-azure-400 bg-azure-50' : 'border-navy-100 hover:bg-slate-50' }}">
                                <p class="font-semibold text-navy-900">{{ $s['tracking_number'] ?? 'REQ-'.$s['id'] }}</p>
                                <p class="mt-1 capitalize text-slate-600">{{ str_replace('_', ' ', $s['status']) }}</p>
                            </button>
                        @empty
                            <p class="text-sm text-slate-500">No tracked shipments are available yet.</p>
                        @endforelse
                    </div>
                </div>

                @if ($selectedShipment && $status !== '')
                    <div class="space-y-5">
                        <div class="grid gap-5 xl:grid-cols-[1.2fr_0.8fr]">
                            <div class="rounded-3xl border border-navy-100 bg-white p-4 shadow-card">
                                <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Route Map</h3>
                                <div class="mt-3 space-y-3">
                                    @foreach ($routePoints as $point)
                                        <div class="flex items-center gap-3 text-sm">
                                            <span class="flex h-3 w-3 rounded-full
                                                {{ $point['kind'] === 'origin' ? 'bg-azure-500' : ($point['kind'] === 'destination' ? 'bg-emerald-500' : ($point['kind'] === 'current' ? 'bg-gold-500' : 'bg-navy-300')) }}">
                                            </span>
                                            <span class="font-medium text-navy-900">{{ $point['label'] }}</span>
                                            <span class="text-slate-500">{{ $point['location'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rounded-3xl border border-navy-100 bg-white p-4 shadow-card">
                                <h3 class="text-lg font-bold text-navy-900">Current Snapshot</h3>
                                <div class="mt-4 space-y-2 text-sm text-slate-600">
                                    <p><span class="font-semibold text-navy-900">Tracking:</span> {{ $selectedShipment['tracking_number'] ?? 'REQ-'.$selectedShipment['id'] }}</p>
                                    <p><span class="font-semibold text-navy-900">Route:</span> {{ $selectedShipment['origin_address']['city'] ?? 'Origin' }} to {{ $selectedShipment['destination_address']['city'] ?? 'Destination' }}</p>
                                    <p><span class="font-semibold text-navy-900">Latest event:</span> {{ $selectedShipment['tracking_events'][0]['description'] ?? 'No events yet' }}</p>
                                    <p><span class="font-semibold text-navy-900">Updated:</span> {{ isset($selectedShipment['tracking_events'][0]['occurred_at']) ? \Carbon\Carbon::parse($selectedShipment['tracking_events'][0]['occurred_at'])->format('M j, Y g:i A') : '-' }}</p>
                                </div>

                                @if ($canUpdateShipments && $selectedShipment['tracking_number'])
                                    <div class="mt-5 rounded-2xl border border-navy-100 bg-slate-50 p-4">
                                        <p class="text-sm font-semibold text-navy-900">Regenerate tracking number</p>
                                        <div class="mt-3 grid gap-3 sm:grid-cols-[1fr_auto]">
                                            <select wire:model="trackingFormat" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 appearance-none pr-10">
                                                <option value="GEX">GEX format</option>
                                                <option value="FDX">FDX format</option>
                                                <option value="TRK">TRK format</option>
                                                <option value="LOG">LOG format</option>
                                            </select>
                                            <x-ui.button type="button" variant="outline" wire:click="regenerateTracking" wire:loading.attr="disabled" :disabled="$busyAction === 'regenerate-tracking'">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                Regenerate
                                            </x-ui.button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if ($canUpdateShipments)
                            <section class="rounded-3xl border border-navy-100 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-lg font-bold text-navy-900">Shipment Profile & Packages</h3>
                                        <p class="mt-1 text-sm text-slate-600">Edit shipment ownership details, service profile, and package metadata.</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.button type="button" variant="outline" wire:click="addPackage">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            Add Package
                                        </x-ui.button>
                                        <x-ui.button type="button" wire:click="saveShipmentProfile" wire:loading.attr="disabled" :disabled="$busyAction === 'save-shipment-profile'">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                            Save Profile
                                        </x-ui.button>
                                        <x-ui.button type="button" variant="outline" wire:click="deleteShipment" wire:loading.attr="disabled" :disabled="$busyAction === 'delete-shipment'" wire:confirm="Delete this shipment? This cannot be undone.">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Delete
                                        </x-ui.button>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-navy-800">Sender Name</label>
                                        <input type="text" wire:model="profileSenderName" class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 transition-colors hover:border-navy-300 focus:border-azure-400" />
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-navy-800">Recipient Name</label>
                                        <input type="text" wire:model="profileRecipientName" class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 transition-colors hover:border-navy-300 focus:border-azure-400" />
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-navy-800">Service Level</label>
                                        <select wire:model="profileServiceLevel" class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 appearance-none pr-10">
                                            <option value="domestic_express">Domestic Express</option>
                                            <option value="international_priority">International Priority</option>
                                            <option value="freight">Freight</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-navy-800">Weight (kg)</label>
                                        <input type="number" step="0.01" min="0.1" wire:model="profileWeightKg" class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 transition-colors hover:border-navy-300 focus:border-azure-400" />
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-navy-800">Declared Value</label>
                                        <input type="number" step="0.01" min="0" wire:model="profileDeclaredValue" class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 transition-colors hover:border-navy-300 focus:border-azure-400" />
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-navy-800">Shipment Reference</label>
                                        <input type="text" wire:model="profileShipmentReference" class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 transition-colors hover:border-navy-300 focus:border-azure-400" />
                                    </div>
                                </div>

                                <div class="mt-5 grid gap-4">
                                    @foreach ($profilePackages as $index => $pkg)
                                        <div class="rounded-2xl border border-navy-100 bg-slate-50 p-4">
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <h4 class="text-base font-bold text-navy-900">Package {{ $index + 1 }}</h4>
                                                <x-ui.button type="button" variant="outline" wire:click="removePackage({{ $index }})" :disabled="count($profilePackages) <= 1">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    Remove
                                                </x-ui.button>
                                            </div>
                                            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Package Name</label>
                                                    <input type="text" wire:model="profilePackages.{{ $index }}.packageName" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Category</label>
                                                    <input type="text" wire:model="profilePackages.{{ $index }}.category" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Type</label>
                                                    <input type="text" wire:model="profilePackages.{{ $index }}.packageType" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Quantity</label>
                                                    <input type="number" min="1" wire:model="profilePackages.{{ $index }}.quantity" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Weight (kg)</label>
                                                    <input type="number" step="0.01" min="0.1" wire:model="profilePackages.{{ $index }}.weight" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Length (cm)</label>
                                                    <input type="number" step="0.1" min="0" wire:model="profilePackages.{{ $index }}.length" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Width (cm)</label>
                                                    <input type="number" step="0.1" min="0" wire:model="profilePackages.{{ $index }}.width" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Height (cm)</label>
                                                    <input type="number" step="0.1" min="0" wire:model="profilePackages.{{ $index }}.height" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Declared Value</label>
                                                    <input type="number" step="0.01" min="0" wire:model="profilePackages.{{ $index }}.declaredValue" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Currency</label>
                                                    <input type="text" maxlength="3" wire:model="profilePackages.{{ $index }}.currency" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Insurance Value</label>
                                                    <input type="number" step="0.01" min="0" wire:model="profilePackages.{{ $index }}.insuranceValue" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Visible Images</label>
                                                    <select wire:model="profilePackages.{{ $index }}.showImagesToCustomer" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm appearance-none pr-10">
                                                        <option value="yes">Visible to customers</option>
                                                        <option value="no">Internal only</option>
                                                    </select>
                                                </div>
                                                <div class="md:col-span-2 xl:col-span-4">
                                                    <label class="mb-1.5 block text-sm font-medium text-navy-800">Description</label>
                                                    <textarea wire:model="profilePackages.{{ $index }}.description" class="focus-ring min-h-[80px] w-full rounded-xl border border-navy-200 bg-white px-4 py-3 text-sm transition-colors hover:border-navy-300 focus:border-azure-400 resize-y leading-6"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        <div class="grid gap-4 rounded-3xl border border-navy-100 p-4 md:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-navy-800">Status</label>
                                <select wire:model="status" {{ $canUpdateShipments ? '' : 'disabled' }} class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm capitalize appearance-none pr-10">
                                    <option value="admin_review">Admin Review</option>
                                    <option value="approved">Approved</option>
                                    <option value="booked">Booked</option>
                                    <option value="pickup_scheduled">Pickup Scheduled</option>
                                    <option value="picked_up">Picked Up</option>
                                    <option value="warehouse_processing">Warehouse Processing</option>
                                    <option value="international_processing">International Processing</option>
                                    <option value="destination_hub">Destination Hub</option>
                                    <option value="out_for_delivery">Out For Delivery</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="paused">Paused</option>
                                    <option value="exception">Exception</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-navy-800">Location</label>
                                <input type="text" wire:model="location" {{ $canUpdateShipments ? '' : 'disabled' }} class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-navy-800">Checkpoint Label</label>
                                <input type="text" wire:model="checkpointLabel" {{ $canUpdateShipments ? '' : 'disabled' }} class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-navy-800">Latitude</label>
                                <input type="text" wire:model="latitude" {{ $canUpdateShipments ? '' : 'disabled' }} class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-navy-800">Longitude</label>
                                <input type="text" wire:model="longitude" {{ $canUpdateShipments ? '' : 'disabled' }} class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-navy-800">Country Code</label>
                                <input type="text" wire:model="countryCode" maxlength="2" {{ $canUpdateShipments ? '' : 'disabled' }} class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                            </div>
                            <div class="md:col-span-2 xl:col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-navy-800">Warehouse Label</label>
                                <input type="text" wire:model="warehouseName" {{ $canUpdateShipments ? '' : 'disabled' }} class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-navy-800">Public Tracking Note</label>
                                <textarea wire:model="description" {{ $canUpdateShipments ? '' : 'disabled' }} class="focus-ring min-h-[80px] w-full rounded-xl border border-navy-200 bg-white px-4 py-3 text-sm transition-colors hover:border-navy-300 focus:border-azure-400 resize-y leading-6"></textarea>
                            </div>
                            <div class="md:col-span-2 xl:col-span-3">
                                <label class="mb-1.5 block text-sm font-medium text-navy-800">Internal Admin Note</label>
                                <textarea wire:model="adminNotes" {{ $canUpdateShipments ? '' : 'disabled' }} class="focus-ring min-h-[80px] w-full rounded-xl border border-navy-200 bg-white px-4 py-3 text-sm transition-colors hover:border-navy-300 focus:border-azure-400 resize-y leading-6"></textarea>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <x-ui.button wire:click="saveShipmentUpdate" wire:loading.attr="disabled" :disabled="!$canUpdateShipments || $busyAction === 'save-shipment-status'">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                Save Update
                            </x-ui.button>
                            <x-ui.button variant="outline" wire:click="unlockNextPaymentStage" wire:loading.attr="disabled" :disabled="!$canManagePayments || $busyAction === 'unlock-payment-stage'">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-6-6h12"/></svg>
                                Unlock Payment Stage
                            </x-ui.button>
                            <x-ui.button variant="outline" wire:click="refreshShipmentData" wire:loading.attr="disabled" :disabled="$busyAction === 'refresh-shipment-panel'">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Refresh
                            </x-ui.button>
                        </div>

                        <div class="grid gap-5 xl:grid-cols-2">
                            <section class="rounded-3xl border border-navy-100 p-4">
                                <h3 class="flex items-center gap-2 text-lg font-bold text-navy-900">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    Tracking Events
                                </h3>
                                <div class="mt-4 space-y-3 max-h-[400px] overflow-auto">
                                    @forelse (array_slice($selectedShipment['tracking_events'] ?? [], 0, 10) as $event)
                                        <div class="rounded-2xl bg-slate-50 p-3 text-sm">
                                            <p class="font-semibold text-navy-900">{{ $event['checkpoint_label'] ?? $event['status'] }}</p>
                                            <p class="mt-1 text-slate-600">{{ $event['location'] ?? 'Location unavailable' }}</p>
                                            <p class="mt-1 text-slate-500">{{ isset($event['occurred_at']) ? \Carbon\Carbon::parse($event['occurred_at'])->format('M j, Y g:i A') : '' }}</p>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">No tracking events recorded yet.</p>
                                    @endforelse
                                </div>
                            </section>

                            <section class="rounded-3xl border border-navy-100 p-4">
                                <h3 class="flex items-center gap-2 text-lg font-bold text-navy-900">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    Payment Stages
                                </h3>
                                <div class="mt-4 space-y-3">
                                    @forelse ($selectedShipment['payments'] ?? [] as $payment)
                                        <div class="rounded-2xl bg-slate-50 p-3 text-sm">
                                            <p class="font-semibold text-navy-900">Stage {{ $payment['stage'] }} · {{ $payment['label'] }}</p>
                                            <p class="mt-1 text-slate-600 capitalize">{{ str_replace('_', ' ', $payment['status']) }} · ${{ number_format((float)$payment['amount'], 2) }}</p>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">No payment stages created yet.</p>
                                    @endforelse
                                </div>
                            </section>
                        </div>

                        @if ($canManageAttachments)
                            <section class="rounded-3xl border border-navy-100 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-lg font-bold text-navy-900">Shipment Attachments</h3>
                                        <p class="mt-1 text-sm text-slate-600">Upload and manage operational documents.</p>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-4 md:grid-cols-[220px_1fr_auto]">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-navy-800">Category</label>
                                        <input type="text" wire:model="attachmentCategory" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm transition-colors hover:border-navy-300 focus:border-azure-400" placeholder="operations" />
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-navy-800">File</label>
                                        <input type="file" wire:model="attachmentFile" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-azure-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-azure-700 hover:file:bg-azure-100" />
                                    </div>
                                    <div class="flex items-end">
                                        <x-ui.button type="button" wire:click="uploadAttachment" wire:loading.attr="disabled"
                                            :disabled="!$attachmentFile || $busyAction === 'upload-attachment'">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                            Upload
                                        </x-ui.button>
                                    </div>
                                </div>

                                @if ($attachmentCategory === 'package_image' && count($profilePackages) > 0)
                                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-navy-800">Package Assignment</label>
                                            <select wire:model="attachmentPackageId" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm appearance-none pr-10">
                                                @foreach ($profilePackages as $index => $pkg)
                                                    <option value="{{ $pkg['id'] }}">Package {{ $index + 1 }} · {{ $pkg['packageName'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-navy-800">Customer Visibility</label>
                                            <select wire:model="attachmentVisibleToCustomer" class="focus-ring min-h-11 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm appearance-none pr-10">
                                                <option value="yes">Visible on customer tracking</option>
                                                <option value="no">Internal only</option>
                                            </select>
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-5 grid gap-3">
                                    @forelse ($attachments as $attachment)
                                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-slate-50 p-3 text-sm">
                                            <div>
                                                <p class="font-semibold text-navy-900">{{ $attachment['original_name'] }}</p>
                                                <p class="mt-1 text-slate-600">{{ $attachment['category'] }} · {{ max(1, round($attachment['size_bytes'] / 1024)) }} KB · {{ \Carbon\Carbon::parse($attachment['created_at'])->format('M j, Y') }}</p>
                                                @if ($attachment['category'] === 'package_image')
                                                    <p class="mt-1 text-slate-500">
                                                        {{ $attachment['metadata']['package_name'] ?? $attachment['metadata']['package_id'] ?? 'Package image' }} ·
                                                        {{ ($attachment['metadata']['visible_to_customer'] ?? false) ? 'Visible to customer' : 'Internal only' }}
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <x-ui.button variant="outline" href="{{ \Illuminate\Support\Facades\Storage::disk($attachment['disk'])->url($attachment['path']) }}" target="_blank" size="sm">Open</x-ui.button>
                                                <x-ui.button variant="outline" wire:click="deleteAttachment({{ $attachment['id'] }})" wire:loading.attr="disabled" :disabled="$busyAction === 'delete-attachment-'.$attachment['id']" size="sm">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    Delete
                                                </x-ui.button>
                                            </div>
                                        </div>
                                    @empty
                                        @if (!$attachmentsLoading)
                                            <p class="text-sm text-slate-500">No shipment attachments uploaded yet.</p>
                                        @else
                                            <p class="text-sm text-slate-500">Loading shipment attachments...</p>
                                        @endif
                                    @endforelse
                                </div>
                            </section>
                        @endif
                    </div>
                @else
                    <div class="flex items-center justify-center py-16">
                        <p class="text-sm text-slate-500">Select a shipment to view and manage operations.</p>
                    </div>
                @endif
            </div>
        </x-ui.card-content>
    </x-ui.card>
</div>
