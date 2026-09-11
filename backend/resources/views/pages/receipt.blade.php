@php
    $settings = app(\App\Support\AppSettings::class);
    $companyName = $settings->companyName();
    $companyEmail = $settings->companyEmail();
    $companyPhone = $settings->companyPhone();
    $companyAddress = $settings->address();
    $companyLogo = $settings->logo();
    $companyWebsite = $settings->websiteUrl();
    $currencySymbol = $settings->currencySymbol();
    $receiptDate = $shipment->created_at->format('M d, Y \a\t g:i A');
    $trackingUrl = route('tracking.number', $shipment->tracking_number);
    $statusDisplay = \App\Support\ShipmentStatus::toDisplay($shipment->status);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Receipt {{ $shipment->tracking_number }} - {{ $companyName }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f1f5f9; color: #0f172a; font-family: 'Segoe UI', Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .receipt-page { background: #fff; max-width: 794px; margin: 20px auto; padding: 0; box-shadow: 0 4px 24px rgba(0,0,0,.08); border-radius: 8px; overflow: hidden; }

        .receipt-header { display: flex; align-items: center; justify-content: space-between; padding: 24px 32px 20px; border-bottom: 3px solid #0f172a; background: linear-gradient(135deg, #f8fafc 0%, #fff 100%); }
        .receipt-header-left { display: flex; align-items: center; gap: 16px; }
        .receipt-logo { height: 48px; width: auto; object-fit: contain; border-radius: 6px; }
        .receipt-logo-placeholder { width: 48px; height: 48px; border-radius: 8px; background: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; flex-shrink: 0; }
        .receipt-company h1 { font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.2; }
        .receipt-company p { font-size: 11px; color: #64748b; margin-top: 2px; }
        .receipt-header-right { text-align: right; }
        .receipt-header-right .doc-title { font-size: 16px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .receipt-header-right .doc-date { font-size: 11px; color: #64748b; margin-top: 4px; }

        .tracking-banner { display: flex; align-items: center; justify-content: space-between; padding: 16px 32px; background: #0f172a; color: #fff; }
        .tracking-banner-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
        .tracking-banner-number { font-size: 22px; font-weight: 800; letter-spacing: 1px; font-family: 'Courier New', monospace; }
        .tracking-barcode { display: flex; align-items: center; gap: 16px; }
        .tracking-barcode img { height: 50px; background: #fff; border-radius: 4px; padding: 4px 8px; }
        .tracking-qr { text-align: right; }
        .tracking-qr canvas { border-radius: 4px; }
        .tracking-qr-label { font-size: 9px; color: #94a3b8; margin-top: 3px; }

        .status-strip { display: flex; align-items: center; gap: 12px; padding: 12px 32px; background: {{ $shipment->status === 'delivered' ? '#ecfdf5' : ($shipment->status === 'cancelled' ? '#fef2f2' : '#eff6ff') }}; border-bottom: 1px solid #e2e8f0; }
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 14px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: {{ $shipment->status === 'delivered' ? '#059669' : ($shipment->status === 'cancelled' ? '#dc2626' : '#2563eb') }}; color: #fff; }
        .status-meta { font-size: 11px; color: #475569; }
        .status-meta strong { color: #0f172a; }

        .receipt-body { padding: 24px 32px; }

        .section { margin-bottom: 20px; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; padding-bottom: 6px; border-bottom: 2px solid #e2e8f0; margin-bottom: 10px; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .party-card { padding: 14px 16px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
        .party-card h4 { font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 8px; }
        .party-card .name { font-size: 14px; font-weight: 700; color: #0f172a; }
        .party-card .company { font-size: 11px; color: #475569; margin-top: 2px; }
        .party-card .detail { font-size: 11px; color: #64748b; margin-top: 3px; }
        .party-card .detail svg { width: 12px; height: 12px; vertical-align: -1px; margin-right: 4px; color: #94a3b8; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 24px; }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-size: 11px; color: #64748b; }
        .info-value { font-size: 11px; font-weight: 600; color: #0f172a; text-align: right; }

        .cost-table { width: 100%; border-collapse: collapse; }
        .cost-table td { padding: 7px 0; font-size: 11px; border-bottom: 1px solid #f1f5f9; }
        .cost-table td:last-child { text-align: right; font-weight: 600; }
        .cost-table .total-row td { border-top: 2px solid #0f172a; border-bottom: none; font-size: 14px; font-weight: 800; padding-top: 10px; }
        .cost-table .total-row td:last-child { color: #0f172a; }

        .progress-track { display: flex; align-items: center; gap: 0; margin: 16px 0 8px; padding: 0 8px; }
        .progress-step { flex: 1; text-align: center; position: relative; }
        .progress-step::before { content: ''; position: absolute; top: 12px; left: 0; right: 0; height: 3px; background: #e2e8f0; z-index: 0; }
        .progress-step:first-child::before { left: 50%; }
        .progress-step:last-child::before { right: 50%; }
        .progress-dot { width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0; border: 3px solid #fff; display: inline-flex; align-items: center; justify-content: center; position: relative; z-index: 1; }
        .progress-dot.active { background: #2563eb; }
        .progress-dot.completed { background: #059669; }
        .progress-label { font-size: 9px; color: #64748b; margin-top: 6px; line-height: 1.3; }
        .progress-label.active { color: #2563eb; font-weight: 700; }
        .progress-label.completed { color: #059669; font-weight: 600; }

        .receipt-footer { padding: 16px 32px; border-top: 2px solid #e2e8f0; background: #f8fafc; text-align: center; }
        .receipt-footer p { font-size: 10px; color: #94a3b8; line-height: 1.6; }
        .receipt-footer .brand { font-weight: 700; color: #64748b; }

        .receipt-actions { display: flex; justify-content: center; gap: 12px; padding: 20px 32px; background: #f1f5f9; border-top: 1px solid #e2e8f0; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; border: 0; transition: all .15s; }
        .btn-primary { background: #0f172a; color: #fff; }
        .btn-primary:hover { background: #1e293b; }
        .btn-outline { background: #fff; color: #0f172a; border: 1.5px solid #cbd5e1; }
        .btn-outline:hover { background: #f8fafc; border-color: #94a3b8; }
        .btn svg { width: 16px; height: 16px; }

        @media (max-width: 640px) {
            .receipt-page { margin: 0; border-radius: 0; }
            .receipt-header { flex-direction: column; gap: 12px; padding: 20px; text-align: center; }
            .receipt-header-right { text-align: center; }
            .tracking-banner { flex-direction: column; gap: 12px; padding: 16px 20px; text-align: center; }
            .tracking-barcode { flex-direction: column; }
            .tracking-qr { text-align: center; }
            .two-col { grid-template-columns: 1fr; }
            .info-grid { grid-template-columns: 1fr; }
            .receipt-body { padding: 20px; }
            .progress-track { flex-wrap: wrap; gap: 8px; }
            .progress-step { flex: 0 0 33%; }
            .progress-step::before { display: none; }
            .receipt-actions { flex-direction: column; }
            .btn { justify-content: center; }
        }

        @media print {
            body { background: #fff; }
            .receipt-page { box-shadow: none; margin: 0; max-width: none; border-radius: 0; }
            .receipt-actions { display: none !important; }
            .status-strip { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .tracking-banner { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .progress-dot { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .status-badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
<div class="receipt-page">

    {{-- HEADER --}}
    <div class="receipt-header">
        <div class="receipt-header-left">
            @if($companyLogo)
                <img src="{{ $companyLogo }}" alt="{{ $companyName }}" class="receipt-logo">
            @else
                <div class="receipt-logo-placeholder">{{ substr($companyName, 0, 1) }}</div>
            @endif
            <div class="receipt-company">
                <h1>{{ $companyName }}</h1>
                @if($companyAddress)<p>{{ $companyAddress }}</p>@endif
                @if($companyPhone || $companyEmail)<p>{{ $companyPhone }}{{ $companyPhone && $companyEmail ? ' | ' : '' }}{{ $companyEmail }}</p>@endif
                @if($companyWebsite)<p>{{ $companyWebsite }}</p>@endif
            </div>
        </div>
        <div class="receipt-header-right">
            <div class="doc-title">Shipment Receipt</div>
            <div class="doc-date">{{ $receiptDate }}</div>
        </div>
    </div>

    {{-- TRACKING + BARCODE + QR --}}
    <div class="tracking-banner">
        <div>
            <div class="tracking-banner-label">Tracking Number</div>
            <div class="tracking-banner-number">{{ $shipment->tracking_number }}</div>
        </div>
        <div class="tracking-barcode">
            <img id="barcode-img" alt="Barcode: {{ $shipment->tracking_number }}">
        </div>
        <div class="tracking-qr">
            <div id="qr-code"></div>
            <div class="tracking-qr-label">Scan to track</div>
        </div>
    </div>

    {{-- STATUS --}}
    <div class="status-strip">
        <span class="status-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
            {{ $statusDisplay }}
        </span>
        <span class="status-meta">
            Last updated: <strong>{{ $shipment->trackingEvents->first()?->occurred_at?->diffForHumans() ?? 'N/A' }}</strong>
            @if($shipment->estimated_delivery_at)
                &nbsp;&middot;&nbsp; Est. delivery: <strong>{{ $shipment->estimated_delivery_at->format('M d, Y') }}</strong>
            @endif
            @if(!empty($workflow['priority']) && $workflow['priority'] !== 'standard')
                &nbsp;&middot;&nbsp; Priority: <strong style="text-transform:uppercase">{{ $workflow['priority'] }}</strong>
            @endif
        </span>
    </div>

    <div class="receipt-body">

        {{-- SENDER / RECEIVER --}}
        <div class="section">
            <div class="two-col">
                <div class="party-card">
                    <h4>Sender</h4>
                    <div class="name">{{ $shipment->sender_name }}</div>
                    @if(!empty($senderMeta['company']))<div class="company">{{ $senderMeta['company'] }}</div>@endif
                    @if(!empty($senderMeta['phone']))<div class="detail"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>{{ $senderMeta['phone'] }}</div>@endif
                    @if(!empty($senderMeta['email']))<div class="detail"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7"/></svg>{{ $senderMeta['email'] }}</div>@endif
                    <div class="detail"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0116 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        {{ trim(($origin['address'] ?? '').', '.($origin['city'] ?? '').', '.($origin['state'] ?? ''), ', ') }}
                        {{ $origin['country'] ?? '' }} {{ $origin['postal_code'] ?? '' }}
                    </div>
                </div>
                <div class="party-card">
                    <h4>Receiver</h4>
                    <div class="name">{{ $shipment->recipient_name }}</div>
                    @if(!empty($receiverMeta['company']))<div class="company">{{ $receiverMeta['company'] }}</div>@endif
                    @if(!empty($receiverMeta['phone']))<div class="detail"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>{{ $receiverMeta['phone'] }}</div>@endif
                    @if(!empty($receiverMeta['email']))<div class="detail"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7"/></svg>{{ $receiverMeta['email'] }}</div>@endif
                    <div class="detail"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0116 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        {{ trim(($dest['address'] ?? '').', '.($dest['city'] ?? '').', '.($dest['state'] ?? ''), ', ') }}
                        {{ $dest['country'] ?? '' }} {{ $dest['postal_code'] ?? '' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- SHIPMENT INFO --}}
        <div class="section">
            <div class="section-title">Shipment Information</div>
            <div class="info-grid">
                <div class="info-row"><span class="info-label">Tracking Number</span><span class="info-value" style="font-family:monospace">{{ $shipment->tracking_number }}</span></div>
                <div class="info-row"><span class="info-label">Shipment Type</span><span class="info-value">{{ ucwords(str_replace('_', ' ', $workflow['shipment_type'] ?? $shipment->service_level)) }}</span></div>
                <div class="info-row"><span class="info-label">Service Level</span><span class="info-value">{{ ucwords(str_replace('_', ' ', $shipment->service_level)) }}</span></div>
                <div class="info-row"><span class="info-label">Shipping Method</span><span class="info-value">{{ ucwords($workflow['shipping_method'] ?? 'N/A') }}</span></div>
                <div class="info-row"><span class="info-label">Origin</span><span class="info-value">{{ trim(($origin['city'] ?? '').', '.($origin['country'] ?? ''), ', ') ?: 'N/A' }}</span></div>
                <div class="info-row"><span class="info-label">Destination</span><span class="info-value">{{ trim(($dest['city'] ?? '').', '.($dest['country'] ?? ''), ', ') ?: 'N/A' }}</span></div>
                <div class="info-row"><span class="info-label">Created</span><span class="info-value">{{ $shipment->created_at->format('M d, Y') }}</span></div>
                <div class="info-row"><span class="info-label">Est. Delivery</span><span class="info-value">{{ $shipment->estimated_delivery_at?->format('M d, Y') ?? 'Pending' }}</span></div>
                @if(!empty($meta['description']))
                    <div class="info-row" style="grid-column:1/-1"><span class="info-label">Description</span><span class="info-value">{{ $meta['description'] }}</span></div>
                @endif
            </div>
        </div>

        {{-- PARCEL --}}
        @if(!empty($pkg))
        <div class="section">
            <div class="section-title">Parcel Information</div>
            <div class="info-grid">
                <div class="info-row"><span class="info-label">Package Type</span><span class="info-value">{{ $pkg['package_type'] ?? $pkg['type'] ?? 'N/A' }}</span></div>
                <div class="info-row"><span class="info-label">Quantity</span><span class="info-value">{{ $pkg['quantity'] ?? 1 }}</span></div>
                <div class="info-row"><span class="info-label">Weight</span><span class="info-value">{{ $pkg['weight_kg'] ?? $shipment->weight_kg ?? '0' }} kg</span></div>
                <div class="info-row"><span class="info-label">Dimensions</span><span class="info-value">{{ $pkg['length_cm'] ?? '0' }} x {{ $pkg['width_cm'] ?? '0' }} x {{ $pkg['height_cm'] ?? '0' }} cm</span></div>
                @if(!empty($pkg['description']))
                    <div class="info-row" style="grid-column:1/-1"><span class="info-label">Description</span><span class="info-value">{{ $pkg['description'] }}</span></div>
                @endif
            </div>
        </div>
        @endif

        {{-- COST --}}
        <div class="section">
            <div class="section-title">Cost & Payment Summary</div>
            <table class="cost-table">
                <tr><td>Shipping Cost</td><td>{{ $currencySymbol }}{{ number_format((float)($costs['shipping_cost'] ?? 0), 2) }}</td></tr>
                @if(!empty($costs['handling_fee']) && (float)$costs['handling_fee'] > 0)
                    <tr><td>Handling Fee</td><td>{{ $currencySymbol }}{{ number_format((float)$costs['handling_fee'], 2) }}</td></tr>
                @endif
                @if(!empty($costs['insurance_fee']) && (float)$costs['insurance_fee'] > 0)
                    <tr><td>Insurance Fee</td><td>{{ $currencySymbol }}{{ number_format((float)$costs['insurance_fee'], 2) }}</td></tr>
                @elseif(!empty($costs['insurance']) && (float)$costs['insurance'] > 0)
                    <tr><td>Insurance Fee</td><td>{{ $currencySymbol }}{{ number_format((float)$costs['insurance'], 2) }}</td></tr>
                @endif
                @if(!empty($costs['customs_fee']) && (float)$costs['customs_fee'] > 0)
                    <tr><td>Customs Fee</td><td>{{ $currencySymbol }}{{ number_format((float)$costs['customs_fee'], 2) }}</td></tr>
                @endif
                @if(!empty($costs['tax']) && (float)$costs['tax'] > 0)
                    <tr><td>Tax</td><td>{{ $currencySymbol }}{{ number_format((float)$costs['tax'], 2) }}</td></tr>
                @endif
                @if(!empty($costs['additional_charges']) && (float)$costs['additional_charges'] > 0)
                    <tr><td>Additional Charges</td><td>{{ $currencySymbol }}{{ number_format((float)$costs['additional_charges'], 2) }}</td></tr>
                @endif
                @if(!empty($costs['discount']) && (float)$costs['discount'] > 0)
                    <tr><td>Discount</td><td style="color:#059669">-{{ $currencySymbol }}{{ number_format((float)$costs['discount'], 2) }}</td></tr>
                @endif
                <tr class="total-row"><td>Total Amount</td><td>{{ $currencySymbol }}{{ number_format($totalCost, 2) }}</td></tr>
            </table>

            {{-- Payment Details --}}
            @if($completedPaymentRequest || $pendingPaymentRequest || !empty($payment['method']) || !empty($payment['status']))
            <div style="margin-top:12px; border-top:1px solid #e2e8f0; padding-top:10px;">
                <div style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.8px; color:#64748b; margin-bottom:8px;">Payment Details</div>
                @if($completedPaymentRequest)
                    @php $pr = $completedPaymentRequest; @endphp
                    @php $txn = $pr->transactions->first(); @endphp
                    @php $proof = $pr->paymentProofs->first(); @endphp
                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:12px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                            <span style="font-weight:700; color:#059669; text-transform:uppercase">Payment Completed</span>
                        </div>
                        <div class="info-grid" style="margin-top:6px;">
                            <div class="info-row"><span class="info-label">Method</span><span class="info-value">{{ ucwords(str_replace('_', ' ', $pr->requested_method)) }}</span></div>
                            @if($txn)<div class="info-row"><span class="info-label">Amount</span><span class="info-value">{{ $currencySymbol }}{{ number_format($txn->amount, 2) }}</span></div>@endif
                            @if($txn)<div class="info-row"><span class="info-label">Reference</span><span class="info-value" style="font-family:monospace; font-size:10px;">{{ $txn->transaction_reference ?? $pr->reference }}</span></div>@endif
                            @if($pr->verified_at)<div class="info-row"><span class="info-label">Verified At</span><span class="info-value">{{ $pr->verified_at->format('M d, Y H:i') }}</span></div>@endif
                        </div>
                        @if($proof && $proof->file_path)
                        <div style="margin-top:8px;">
                            <a href="{{ Storage::url('private/' . $proof->file_path) }}" target="_blank" style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; color:#0d5368; text-decoration:none;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                View Payment Proof
                            </a>
                        </div>
                        @endif
                    </div>
                @elseif($payment['method'] === 'bank_transfer' && !empty($payment['reference']))
                    <div style="background:#fefce8; border:1px solid #fde68a; border-radius:8px; padding:12px 16px;">
                        <div style="font-weight:600; color:#92400e; margin-bottom:4px;">Bank Transfer Payment</div>
                        <div class="info-grid" style="margin-top:6px;">
                            <div class="info-row"><span class="info-label">Reference</span><span class="info-value" style="font-family:monospace">{{ $payment['reference'] }}</span></div>
                            @if(!empty($payment['amount']))
                            <div class="info-row"><span class="info-label">Amount</span><span class="info-value">{{ $currencySymbol }}{{ number_format((float)$payment['amount'], 2) }}</span></div>
                            @endif
                        </div>
                    </div>
                @endif
                @if(!empty($payment['method']) || !empty($payment['status']))
                <div style="margin-top:8px; display:flex; gap:24px;">
                    @if(!empty($payment['method']))
                        <div class="info-row" style="border:0"><span class="info-label">Payment Method</span><span class="info-value">{{ ucwords(str_replace('_', ' ', $payment['method'])) }}</span></div>
                    @endif
                    @if(!empty($payment['status']))
                        <div class="info-row" style="border:0"><span class="info-label">Payment Status</span><span class="info-value" style="text-transform:uppercase">{{ $payment['status'] }}</span></div>
                    @endif
                    @if(!empty($payment['reference']))
                        <div class="info-row" style="border:0"><span class="info-label">Reference</span><span class="info-value" style="font-family:monospace">{{ $payment['reference'] }}</span></div>
                    @endif
                </div>
                @endif
            </div>
            @endif
        </div>

        {{-- TRACKING PROGRESS --}}
        @php
            $progressSteps = [
                'booked' => 'Booked',
                'picked_up' => 'Picked Up',
                'in_transit' => 'In Transit',
                'arrived' => 'Arrived',
                'out_for_delivery' => 'Out for Delivery',
                'delivered' => 'Delivered',
            ];
            $statusOrder = array_keys($progressSteps);
            $currentIdx = array_search($shipment->status, $statusOrder);
            if ($currentIdx === false) $currentIdx = 0;
        @endphp
        <div class="section">
            <div class="section-title">Shipment Progress</div>
            <div class="progress-track">
                @foreach($progressSteps as $stepKey => $stepLabel)
                    @php
                        $stepIdx = array_search($stepKey, $statusOrder);
                        $isCompleted = $stepIdx < $currentIdx;
                        $isActive = $stepIdx === $currentIdx;
                    @endphp
                    <div class="progress-step">
                        <div class="progress-dot {{ $isCompleted ? 'completed' : ($isActive ? 'active' : '') }}">
                            @if($isCompleted)
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            @endif
                        </div>
                        <div class="progress-label {{ $isCompleted ? 'completed' : ($isActive ? 'active' : '') }}">{{ $stepLabel }}</div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- FOOTER --}}
    <div class="receipt-footer">
        <p><span class="brand">{{ $companyName }}</span> &middot; {{ $companyAddress }}</p>
        <p>{{ $companyEmail }} &middot; {{ $companyPhone }} &middot; {{ $companyWebsite }}</p>
        <p style="margin-top:6px">This receipt was generated automatically. For inquiries, contact {{ $companyEmail }}</p>
    </div>

    {{-- ACTIONS --}}
    <div class="receipt-actions" id="receipt-actions">
        <button class="btn btn-primary" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
            Print Receipt
        </button>
        <a href="{{ route('receipt.pdf', $shipment->tracking_number) }}" class="btn btn-outline" id="pdf-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>
            Download PDF
        </a>
        @auth
            <a href="{{ route('admin.shipments.show', $shipment) }}" class="btn btn-outline">
        @else
            <a href="{{ route('tracking.number', $shipment->tracking_number) }}" class="btn btn-outline">
        @endauth
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to Shipment
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var trackingNumber = @json($shipment->tracking_number);
    var trackingUrl = @json($trackingUrl);

    try {
        JsBarcode('#barcode-img', trackingNumber, {
            format: 'CODE128',
            width: 2,
            height: 50,
            displayValue: false,
            background: '#ffffff',
            lineColor: '#0f172a',
            margin: 0
        });
    } catch(e) {}

    try {
        QRCode.toCanvas(document.createElement('canvas'), trackingUrl, {
            width: 64,
            margin: 1,
            color: { dark: '#ffffff', light: '#00000000' }
        }, function(err, canvas) {
            if (!err && canvas) {
                var qr = document.getElementById('qr-code');
                if (qr) { qr.appendChild(canvas); canvas.style.borderRadius = '4px'; }
            }
        });
    } catch(e) {}
});
</script>
</body>
</html>
