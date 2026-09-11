@extends('layouts.app')
@section('title', 'Create New Shipment')
@php $hideHeaderFooter = true; @endphp
@section('content')
<x-dashboard-shell title="Create New Shipment">
    @if(session('success'))
        <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">Please fix the following errors:</p>
            <ul class="mt-1 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.shipments.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- SENDER --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Sender Information</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Full Name *</label>
                    <input type="text" name="sender_full_name" value="{{ old('sender_full_name', $sampleData['sender_full_name']) }}" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Company</label>
                    <input type="text" name="sender_company" value="{{ old('sender_company', $sampleData['sender_company']) }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Email *</label>
                    <input type="email" name="sender_email" value="{{ old('sender_email', $sampleData['sender_email']) }}" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Phone *</label>
                    <input type="text" name="sender_phone" value="{{ old('sender_phone', $sampleData['sender_phone']) }}" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Country *</label>
                    <select name="sender_country" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        <option value="">Select country</option>
                        @foreach($countries as $c)
                            @php $countryVal = $c['name'] . ' (' . $c['code'] . ')'; @endphp
                            <option value="{{ $countryVal }}" {{ old('sender_country', $sampleData['sender_country']) === $countryVal ? 'selected' : '' }}>{{ $c['name'] }} ({{ $c['code'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">State/Province</label>
                    <input type="text" name="sender_state" value="{{ old('sender_state', $sampleData['sender_state']) }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">City *</label>
                    <input type="text" name="sender_city" value="{{ old('sender_city', $sampleData['sender_city']) }}" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Address *</label>
                    <input type="text" name="sender_address" value="{{ old('sender_address', $sampleData['sender_address']) }}" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Postal Code</label>
                    <input type="text" name="sender_postal_code" value="{{ old('sender_postal_code', $sampleData['sender_postal_code']) }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- RECEIVER --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Receiver Information</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Full Name *</label>
                    <input type="text" name="receiver_full_name" value="{{ old('receiver_full_name', $sampleData['receiver_full_name']) }}" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Company</label>
                    <input type="text" name="receiver_company" value="{{ old('receiver_company', $sampleData['receiver_company']) }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Email *</label>
                    <input type="email" name="receiver_email" value="{{ old('receiver_email', $sampleData['receiver_email']) }}" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Phone *</label>
                    <input type="text" name="receiver_phone" value="{{ old('receiver_phone', $sampleData['receiver_phone']) }}" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Country *</label>
                    <select name="receiver_country" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        <option value="">Select country</option>
                        @foreach($countries as $c)
                            @php $countryVal = $c['name'] . ' (' . $c['code'] . ')'; @endphp
                            <option value="{{ $countryVal }}" {{ old('receiver_country', $sampleData['receiver_country']) === $countryVal ? 'selected' : '' }}>{{ $c['name'] }} ({{ $c['code'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">State/Province</label>
                    <input type="text" name="receiver_state" value="{{ old('receiver_state', $sampleData['receiver_state']) }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">City *</label>
                    <input type="text" name="receiver_city" value="{{ old('receiver_city', $sampleData['receiver_city']) }}" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Address *</label>
                    <input type="text" name="receiver_address" value="{{ old('receiver_address', $sampleData['receiver_address']) }}" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Postal Code</label>
                    <input type="text" name="receiver_postal_code" value="{{ old('receiver_postal_code', $sampleData['receiver_postal_code']) }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- SHIPMENT INFO --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Shipment Information</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Service Level *</label>
                    <select name="service_level" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        <option value="">Select service</option>
                        @foreach($serviceLevels as $key => $sl)
                            <option value="{{ $key }}" {{ old('service_level', $sampleData['service_level']) === $key ? 'selected' : '' }}>{{ $sl['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Shipment Type</label>
                    <select name="shipment_type" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        @foreach(['domestic' => 'Domestic', 'international' => 'International', 'express' => 'Express', 'freight' => 'Freight'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('shipment_type', $sampleData['shipment_type']) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Shipping Method</label>
                    <select name="shipping_method" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        @foreach(['air' => 'Air', 'sea' => 'Sea', 'ground' => 'Ground', 'rail' => 'Rail'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('shipping_method', $sampleData['shipping_method']) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Priority</label>
                    <select name="priority" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        @foreach(['standard' => 'Standard', 'high' => 'High', 'urgent' => 'Urgent'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('priority', $sampleData['priority']) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Estimated Delivery Date</label>
                    <input type="date" name="estimated_delivery_date" value="{{ old('estimated_delivery_date', $sampleData['estimated_delivery_date']) }}" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-semibold text-slate-700">Description</label>
                    <textarea name="description" rows="2" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">{{ old('description', $sampleData['description']) }}</textarea>
                </div>
            </div>
        </div>

        {{-- PARCEL --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Parcel Information</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Package Type *</label>
                    <select name="package_type" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        <option value="">Select type</option>
                        @foreach(['Parcel' => 'Parcel', 'Box' => 'Box', 'Pallet' => 'Pallet', 'Envelope' => 'Envelope', 'Tube' => 'Tube', 'Crate' => 'Crate'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('package_type', $sampleData['package_type']) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Weight (kg) *</label>
                    <input type="number" name="package_weight" value="{{ old('package_weight', $sampleData['package_weight']) }}" step="0.1" min="0.1" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Length (cm) *</label>
                    <input type="number" name="package_length" value="{{ old('package_length', $sampleData['package_length']) }}" step="0.1" min="0" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Width (cm) *</label>
                    <input type="number" name="package_width" value="{{ old('package_width', $sampleData['package_width']) }}" step="0.1" min="0" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Height (cm) *</label>
                    <input type="number" name="package_height" value="{{ old('package_height', $sampleData['package_height']) }}" step="0.1" min="0" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Quantity *</label>
                    <input type="number" name="package_quantity" value="{{ old('package_quantity', $sampleData['package_quantity']) }}" min="1" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-semibold text-slate-700">Package Description</label>
                    <textarea name="package_description" rows="2" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">{{ old('package_description', $sampleData['package_description']) }}</textarea>
                </div>
            </div>
        </div>

        {{-- COST --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Cost Information</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Shipping Cost ($) *</label>
                    <input type="number" name="shipping_cost" value="{{ old('shipping_cost', $sampleData['shipping_cost']) }}" step="0.01" min="0" required class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Handling Fee ($)</label>
                    <input type="number" name="handling_fee" value="{{ old('handling_fee', $sampleData['handling_fee']) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Insurance Fee ($)</label>
                    <input type="number" name="insurance_fee" value="{{ old('insurance_fee', $sampleData['insurance_fee']) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Customs Fee ($)</label>
                    <input type="number" name="customs_fee" value="{{ old('customs_fee', $sampleData['customs_fee']) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Tax ($)</label>
                    <input type="number" name="tax" value="{{ old('tax', $sampleData['tax']) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Discount ($)</label>
                    <input type="number" name="discount" value="{{ old('discount', $sampleData['discount']) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Additional Charges ($)</label>
                    <input type="number" name="additional_charges" value="{{ old('additional_charges', $sampleData['additional_charges']) }}" step="0.01" min="0" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- PAYMENT --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Payment Information</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Payment Method</label>
                    <select name="payment_method" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        <option value="">Select method</option>
                        @foreach(['credit_card' => 'Credit Card', 'bank_transfer' => 'Bank Transfer', 'cash_on_delivery' => 'Cash on Delivery', 'invoice' => 'Invoice', 'prepaid' => 'Prepaid'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('payment_method', $sampleData['payment_method']) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Payment Status</label>
                    <select name="payment_status" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        @foreach(['pending' => 'Pending', 'paid' => 'Paid', 'partial' => 'Partial', 'waived' => 'Waived'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('payment_status', $sampleData['payment_status']) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Warehouse</label>
                    <select name="warehouse_id" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        <option value="">Auto-assign</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Driver</label>
                    <select name="driver_id" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none">
                        <option value="">Auto-assign</option>
                        @foreach($drivers as $drv)
                            <option value="{{ $drv->id }}" {{ old('driver_id') == $drv->id ? 'selected' : '' }}>{{ $drv->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- PHOTOS --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Shipment Photos</h3>
            <p class="mt-1 text-sm text-slate-500">Upload up to 10 images (JPG, PNG, WebP). Max 5MB each.</p>
            <div class="mt-4">
                <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="mt-1.5 block w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm focus:border-azure-500 focus:outline-none file:mr-3 file:rounded-md file:border-0 file:bg-azure-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-azure-700 hover:file:bg-azure-100">
            </div>
        </div>

        {{-- SUBMIT --}}
        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-6">
            <a href="{{ route('admin.shipments') }}" class="rounded-md border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-md bg-azure-500 px-6 py-2.5 text-sm font-semibold text-white shadow-glow hover:bg-azure-600 hover:-translate-y-0.5 active:translate-y-0 transition-all">
                Create & Approve Shipment
            </button>
        </div>
    </form>
</x-dashboard-shell>
@endsection
