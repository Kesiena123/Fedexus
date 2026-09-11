@php use App\Support\AppSettings; $settings = app(AppSettings::class); $currencySymbol = $settings->currencySymbol(); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Bank Transfer Instructions</title>
    <style>
        @page { size: A4 portrait; margin: 30px; }
        body { color: #0f172a; font-family: Arial, Helvetica, sans-serif; font-size: 10px; line-height: 1.4; }
        table { border-collapse: collapse; width: 100%; margin: 0; padding: 0; }
        td { vertical-align: top; padding: 4px 0; }
        .header { border-bottom: 3px solid #0f172a; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { font-size: 18px; font-weight: bold; margin: 0; }
        .header p { font-size: 9px; color: #64748b; margin: 2px 0 0; }
        .amount-box { background: #0f172a; color: #fff; padding: 12px 16px; text-align: center; border-radius: 6px; margin-bottom: 16px; }
        .amount-box .label { font-size: 8px; text-transform: uppercase; color: #94a3b8; }
        .amount-box .value { font-size: 22px; font-weight: bold; margin-top: 4px; }
        .ref-box { border: 2px solid #0f172a; padding: 12px 16px; text-align: center; border-radius: 6px; margin-bottom: 16px; }
        .ref-box .label { font-size: 8px; text-transform: uppercase; color: #64748b; }
        .ref-box .value { font-size: 16px; font-weight: bold; font-family: monospace; margin-top: 4px; letter-spacing: 1px; }
        .bank-card { border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 10px; page-break-inside: avoid; }
        .bank-card h3 { font-size: 13px; font-weight: bold; margin: 0 0 6px; }
        .bank-card .row { display: flex; gap: 16px; margin-bottom: 3px; }
        .bank-card .label { font-size: 8px; color: #64748b; width: 100px; }
        .bank-card .val { font-size: 10px; font-weight: 600; }
        .bank-card .val.mono { font-family: monospace; }
        .instructions-text { font-size: 9px; color: #475569; background: #f8fafc; padding: 6px 10px; border-radius: 4px; margin-top: 6px; }
        .warning { background: #fef3c7; border: 1px solid #fde68a; border-radius: 6px; padding: 8px 12px; margin-bottom: 16px; font-size: 9px; color: #92400e; }
        .footer { border-top: 2px solid #e2e8f0; padding-top: 8px; margin-top: 20px; text-align: center; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyName }}</h1>
        <p>Bank Transfer Payment Instructions</p>
    </div>

    <div class="warning">
        <strong>Important:</strong> Always include your payment reference in the transfer description. Your payment cannot be verified without it.
    </div>

    <div class="amount-box">
        <div class="label">Amount to Transfer</div>
        <div class="value">{{ number_format((float)$amount, 2) }} {{ $currency }}</div>
    </div>

    <div class="ref-box">
        <div class="label">Your Payment Reference</div>
        <div class="value">{{ $reference }}</div>
    </div>

    <p style="font-size:9px; color:#64748b; margin-bottom:8px;">Tracking Number: <strong>{{ $trackingNumber }}</strong></p>

    @if($bankAccounts->isNotEmpty())
        <p style="font-size:10px; font-weight:bold; margin-bottom:6px;">Bank Account Details</p>
        @foreach($bankAccounts as $bank)
            <div class="bank-card">
                <h3>{{ $bank->bank_name }} <span style="font-size:9px; color:#64748b; font-weight:normal;">({{ $bank->supported_currency }})</span></h3>
                <div class="row"><span class="label">Account Name</span><span class="val">{{ $bank->account_name }}</span></div>
                <div class="row"><span class="label">Account Number</span><span class="val mono">{{ $bank->account_number }}</span></div>
                @if($bank->swift_bic)<div class="row"><span class="label">SWIFT/BIC</span><span class="val mono">{{ $bank->swift_bic }}</span></div>@endif
                @if($bank->iban)<div class="row"><span class="label">IBAN</span><span class="val mono">{{ $bank->iban }}</span></div>@endif
                @if($bank->routing_number)<div class="row"><span class="label">Routing Number</span><span class="val mono">{{ $bank->routing_number }}</span></div>@endif
                @if($bank->branch_name)<div class="row"><span class="label">Branch</span><span class="val">{{ $bank->branch_name }}</span></div>@endif
                @if($bank->country)<div class="row"><span class="label">Country</span><span class="val">{{ $bank->country }}</span></div>@endif
                @if($bank->payment_instructions)<div class="instructions-text">{{ $bank->payment_instructions }}</div>@endif
            </div>
        @endforeach
    @else
        <p style="color:#64748b; font-size:9px;">No bank accounts configured. Please contact support.</p>
    @endif

    <div class="footer">
        <p>{{ $companyName }} | Generated automatically. For inquiries, contact support.</p>
    </div>
</body>
</html>
