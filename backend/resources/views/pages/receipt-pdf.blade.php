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
    $statusDisplay = \App\Support\ShipmentStatus::toDisplay($shipment->status);

    $pw = 525;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $shipment->tracking_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 35px; }
        body { color: #0f172a; font-family: Arial, Helvetica, sans-serif; font-size: 9px; line-height: 1.35; }
        table { border-collapse: collapse; margin: 0; padding: 0; }
        td { vertical-align: top; overflow: hidden; margin: 0; padding: 0; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <table cellpadding="0" cellspacing="0" style="width:100%; table-layout:fixed;">
        <tr>
            <td style="width:60%; padding-bottom:10px;">
                <div style="font-size:15px; font-weight:bold; color:#0f172a;">{{ $companyName }}</div>
                @if($companyAddress)<div style="font-size:8px; color:#64748b;">{{ $companyAddress }}</div>@endif
                @if($companyPhone || $companyEmail)<div style="font-size:8px; color:#64748b;">{{ $companyPhone }}{{ $companyPhone && $companyEmail ? ' | ' : '' }}{{ $companyEmail }}</div>@endif
                @if($companyWebsite)<div style="font-size:8px; color:#64748b;">{{ $companyWebsite }}</div>@endif
            </td>
            <td style="width:40%; text-align:right; padding-bottom:10px;">
                <div style="font-size:12px; font-weight:bold;">SHIPMENT RECEIPT</div>
                <div style="font-size:8px; color:#64748b;">{{ $receiptDate }}</div>
            </td>
        </tr>
        <tr><td colspan="2" style="height:2px; background-color:#0f172a; font-size:0; line-height:0;">&nbsp;</td></tr>
    </table>

    {{-- TRACKING BAR --}}
    <table cellpadding="0" cellspacing="0" style="width:100%; table-layout:fixed; background-color:#0f172a; color:#ffffff; padding:8px 12px; margin-top:8px;">
        <tr>
            <td style="padding:8px 12px;">
                <div style="font-size:7px; color:#94a3b8;">TRACKING NUMBER</div>
                <div style="font-size:13px; font-weight:bold; font-family:monospace;">{{ $shipment->tracking_number }}</div>
                <div style="margin-top:4px;">{!! $barcodeHtml !!}</div>
            </td>
        </tr>
    </table>

    {{-- STATUS --}}
    <table cellpadding="0" cellspacing="0" style="width:100%; table-layout:fixed; border:1px solid #e2e8f0; background-color:#f8fafc; margin-top:8px;">
        <tr>
            <td style="padding:6px 10px;">
                <span style="padding:2px 8px; font-size:8px; font-weight:bold; background-color:#2563eb; color:#ffffff;">{{ $statusDisplay }}</span>
                <div style="font-size:8px; color:#475569; margin-top:2px;">
                    Last updated: {{ $shipment->trackingEvents->first()?->occurred_at?->diffForHumans() ?? 'N/A' }}
                    @if($shipment->estimated_delivery_at) | Est. delivery: {{ $shipment->estimated_delivery_at->format('M d, Y') }} @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- SENDER / RECEIVER --}}
    <table cellpadding="0" cellspacing="0" style="width:100%; table-layout:fixed; margin-top:8px;">
        <tr>
            <td style="width:50%; padding:6px 8px; border:1px solid #e2e8f0; background-color:#f8fafc;">
                <div style="font-size:7px; color:#64748b; margin-bottom:3px;">SENDER</div>
                <div style="font-size:11px; font-weight:bold;">{{ $shipment->sender_name }}</div>
                @if(!empty($senderMeta['company']))<div style="font-size:8px; color:#64748b;">{{ $senderMeta['company'] }}</div>@endif
                @if(!empty($senderMeta['phone']))<div style="font-size:8px; color:#64748b;">{{ $senderMeta['phone'] }}</div>@endif
                @if(!empty($senderMeta['email']))<div style="font-size:8px; color:#64748b;">{{ $senderMeta['email'] }}</div>@endif
                <div style="font-size:8px; color:#64748b;">{{ trim(($origin['city'] ?? '').', '.($origin['country'] ?? ''), ', ') }}</div>
            </td>
            <td style="width:50%; padding:6px 8px; border:1px solid #e2e8f0; background-color:#f8fafc;">
                <div style="font-size:7px; color:#64748b; margin-bottom:3px;">RECEIVER</div>
                <div style="font-size:11px; font-weight:bold;">{{ $shipment->recipient_name }}</div>
                @if(!empty($receiverMeta['company']))<div style="font-size:8px; color:#64748b;">{{ $receiverMeta['company'] }}</div>@endif
                @if(!empty($receiverMeta['phone']))<div style="font-size:8px; color:#64748b;">{{ $receiverMeta['phone'] }}</div>@endif
                @if(!empty($receiverMeta['email']))<div style="font-size:8px; color:#64748b;">{{ $receiverMeta['email'] }}</div>@endif
                <div style="font-size:8px; color:#64748b;">{{ trim(($dest['city'] ?? '').', '.($dest['country'] ?? ''), ', ') }}</div>
            </td>
        </tr>
    </table>

    {{-- SHIPMENT INFO --}}
    <div style="margin-top:8px;">
        <div style="font-size:8px; font-weight:bold; color:#64748b; padding-bottom:3px; border-bottom:2px solid #e2e8f0; margin-bottom:5px;">SHIPMENT INFORMATION</div>
        <table cellpadding="0" cellspacing="0" style="width:100%; table-layout:fixed;">
            <tr><td style="width:35%; padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Tracking Number</td><td style="padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold;">{{ $shipment->tracking_number }}</td></tr>
            <tr><td style="width:35%; padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Service Level</td><td style="padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold;">{{ ucwords(str_replace('_', ' ', $shipment->service_level)) }}</td></tr>
            <tr><td style="width:35%; padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Origin</td><td style="padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold;">{{ trim(($origin['city'] ?? '').', '.($origin['country'] ?? ''), ', ') ?: 'N/A' }}</td></tr>
            <tr><td style="width:35%; padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Destination</td><td style="padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold;">{{ trim(($dest['city'] ?? '').', '.($dest['country'] ?? ''), ', ') ?: 'N/A' }}</td></tr>
            <tr><td style="width:35%; padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Created</td><td style="padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold;">{{ $shipment->created_at->format('M d, Y') }}</td></tr>
            <tr><td style="width:35%; padding:3px 0; font-size:8px;">Est. Delivery</td><td style="padding:3px 0; font-size:8px; font-weight:bold;">{{ $shipment->estimated_delivery_at?->format('M d, Y') ?? 'Pending' }}</td></tr>
        </table>
    </div>

    {{-- PARCEL --}}
    @if(!empty($pkg))
    <div style="margin-top:8px;">
        <div style="font-size:8px; font-weight:bold; color:#64748b; padding-bottom:3px; border-bottom:2px solid #e2e8f0; margin-bottom:5px;">PARCEL INFORMATION</div>
        <table cellpadding="0" cellspacing="0" style="width:100%; table-layout:fixed;">
            <tr><td style="width:35%; padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Package Type</td><td style="padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold;">{{ $pkg['package_type'] ?? $pkg['type'] ?? 'N/A' }}</td></tr>
            <tr><td style="width:35%; padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Quantity</td><td style="padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold;">{{ $pkg['quantity'] ?? 1 }}</td></tr>
            <tr><td style="width:35%; padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Weight</td><td style="padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold;">{{ $pkg['weight_kg'] ?? $shipment->weight_kg ?? '0' }} kg</td></tr>
            <tr><td style="width:35%; padding:3px 0; font-size:8px;">Dimensions</td><td style="padding:3px 0; font-size:8px; font-weight:bold;">{{ $pkg['length_cm'] ?? '0' }} x {{ $pkg['width_cm'] ?? '0' }} x {{ $pkg['height_cm'] ?? '0' }} cm</td></tr>
        </table>
    </div>
    @endif

    {{-- COST --}}
    <div style="margin-top:8px;">
        <div style="font-size:8px; font-weight:bold; color:#64748b; padding-bottom:3px; border-bottom:2px solid #e2e8f0; margin-bottom:5px;">COST SUMMARY</div>
        <table cellpadding="0" cellspacing="0" style="width:100%; table-layout:fixed;">
            <tr><td style="width:70%; padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Shipping Cost</td><td style="padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold; text-align:right;">{{ $currencySymbol }}{{ number_format((float)($costs['shipping_cost'] ?? 0), 2) }}</td></tr>
            @if(!empty($costs['handling_fee']) && (float)$costs['handling_fee'] > 0)
                <tr><td style="width:70%; padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Handling Fee</td><td style="padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold; text-align:right;">{{ $currencySymbol }}{{ number_format((float)$costs['handling_fee'], 2) }}</td></tr>
            @endif
            @if((!empty($costs['insurance_fee']) && (float)$costs['insurance_fee'] > 0) || (!empty($costs['insurance']) && (float)$costs['insurance'] > 0))
                <tr><td style="width:70%; padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Insurance Fee</td><td style="padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold; text-align:right;">{{ $currencySymbol }}{{ number_format((float)($costs['insurance_fee'] ?? $costs['insurance'] ?? 0), 2) }}</td></tr>
            @endif
            @if(!empty($costs['customs_fee']) && (float)$costs['customs_fee'] > 0)
                <tr><td style="width:70%; padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Customs Fee</td><td style="padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold; text-align:right;">{{ $currencySymbol }}{{ number_format((float)$costs['customs_fee'], 2) }}</td></tr>
            @endif
            @if(!empty($costs['tax']) && (float)$costs['tax'] > 0)
                <tr><td style="width:70%; padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Tax</td><td style="padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold; text-align:right;">{{ $currencySymbol }}{{ number_format((float)$costs['tax'], 2) }}</td></tr>
            @endif
            @if(!empty($costs['additional_charges']) && (float)$costs['additional_charges'] > 0)
                <tr><td style="width:70%; padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Additional Charges</td><td style="padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold; text-align:right;">{{ $currencySymbol }}{{ number_format((float)$costs['additional_charges'], 2) }}</td></tr>
            @endif
            @if(!empty($costs['discount']) && (float)$costs['discount'] > 0)
                <tr><td style="width:70%; padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Discount</td><td style="padding:4px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold; text-align:right; color:#059669;">-{{ $currencySymbol }}{{ number_format((float)$costs['discount'], 2) }}</td></tr>
            @endif
            <tr><td style="width:70%; padding:5px 0; font-size:11px; font-weight:bold; border-top:2px solid #0f172a;">Total Amount</td><td style="padding:5px 0; font-size:11px; font-weight:bold; text-align:right; border-top:2px solid #0f172a;">{{ $currencySymbol }}{{ number_format($totalCost, 2) }}</td></tr>
        </table>
        @if(!empty($payment['method']) || !empty($payment['status']))
        <table cellpadding="0" cellspacing="0" style="width:100%; table-layout:fixed; margin-top:4px;">
            @if(!empty($payment['method']))<tr><td style="width:35%; padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9;">Payment Method</td><td style="padding:3px 0; font-size:8px; border-bottom:1px solid #f1f5f9; font-weight:bold;">{{ ucwords(str_replace('_', ' ', $payment['method'])) }}</td></tr>@endif
            @if(!empty($payment['status']))<tr><td style="width:35%; padding:3px 0; font-size:8px;">Payment Status</td><td style="padding:3px 0; font-size:8px; font-weight:bold;">{{ strtoupper($payment['status']) }}</td></tr>@endif
        </table>
        @endif
    </div>

    {{-- FOOTER --}}
    <table cellpadding="0" cellspacing="0" style="width:100%; table-layout:fixed; border-top:2px solid #e2e8f0; margin-top:10px; padding-top:6px;">
        <tr><td style="text-align:center; padding-top:6px;">
            <p style="font-size:7px; color:#94a3b8;"><strong style="color:#64748b;">{{ $companyName }}</strong> | {{ $companyAddress }}</p>
            <p style="font-size:7px; color:#94a3b8;">{{ $companyEmail }} | {{ $companyPhone }} | {{ $companyWebsite }}</p>
            <p style="font-size:7px; color:#94a3b8; margin-top:3px;">Track this shipment: {{ $trackingUrl }}</p>
        </td></tr>
    </table>

</body>
</html>
