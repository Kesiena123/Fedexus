@extends('layouts.app')
@section('title', 'Bank Transfer Payment')
@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    {{-- Status flash messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-4">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d8fa3]">Bank Transfer</p>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Complete Your Payment</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Transfer the exact amount to one of the bank accounts below. Include your payment reference in the transfer description.</p>

        {{-- Amount and Reference --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border-2 border-[#0d5368] bg-[#0d5368] p-5 text-center">
                <p class="text-xs font-semibold uppercase text-white/70">Amount to Transfer</p>
                <p class="mt-1 text-3xl font-bold text-white">{{ number_format((float)$amount, 2) }} {{ $currency }}</p>
            </div>
            <div class="rounded-lg border-2 border-[#0d5368] bg-[#0d5368]/5 p-5 text-center">
                <p class="text-xs font-semibold uppercase text-slate-500">Your Payment Reference</p>
                <p class="mt-1 font-mono text-2xl font-bold tracking-wider text-[#0d5368]">{{ $reference }}</p>
                <button onclick="copyToClipboard('{{ $reference }}', this)" class="mt-2 inline-flex items-center gap-1 rounded bg-[#0d5368]/10 px-3 py-1 text-xs font-bold text-[#0d5368] hover:bg-[#0d5368]/20">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Copy Reference
                </button>
            </div>
        </div>

        {{-- Warning --}}
        <div class="mt-6 rounded-lg border-2 border-amber-300 bg-amber-50 p-4">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="font-bold text-amber-800">Important</p>
                    <p class="text-sm text-amber-700">Always include the payment reference in your transfer description. After transferring, upload your proof of payment below. Admin will verify and confirm your payment.</p>
                </div>
            </div>
        </div>

        {{-- Bank Account Selector --}}
        @if(!empty($bankAccounts) && count($bankAccounts) > 0)
            <div class="mt-6">
                <h2 class="text-sm font-bold text-slate-900">Select a Bank Account</h2>
                <div class="mt-3 space-y-3" id="bankAccountSelector">
                    @foreach($bankAccounts as $index => $bank)
                        <div class="bank-card cursor-pointer rounded-lg border-2 {{ $loop->first ? 'border-[#0d5368] bg-[#0d5368]/5' : 'border-slate-200 bg-white hover:border-slate-300' }} p-4 transition-all" onclick="selectBank({{ $index }})" data-index="{{ $index }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        @if($bank->is_default)
                                            <span class="rounded bg-[#0d5368] px-2 py-0.5 text-xs font-bold text-white">Default</span>
                                        @endif
                                        <h3 class="text-base font-bold text-slate-900">{{ $bank->bank_name }}</h3>
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600">{{ $bank->supported_currency }}</span>
                                    </div>
                                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                        <div>
                                            <p class="text-xs text-slate-500">Account Name</p>
                                            <p class="font-semibold text-slate-800">{{ $bank->account_name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-500">Account Number</p>
                                            <div class="flex items-center gap-2">
                                                <p class="font-mono font-semibold text-slate-800">{{ $bank->account_number }}</p>
                                                <button onclick="event.stopPropagation(); copyToClipboard('{{ $bank->account_number }}', this)" class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600 hover:bg-slate-200">Copy</button>
                                            </div>
                                        </div>
                                        @if($bank->swift_bic)
                                            <div>
                                                <p class="text-xs text-slate-500">SWIFT/BIC</p>
                                                <div class="flex items-center gap-2">
                                                    <p class="font-mono font-semibold text-slate-800">{{ $bank->swift_bic }}</p>
                                                    <button onclick="event.stopPropagation(); copyToClipboard('{{ $bank->swift_bic }}', this)" class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600 hover:bg-slate-200">Copy</button>
                                                </div>
                                            </div>
                                        @endif
                                        @if($bank->iban)
                                            <div>
                                                <p class="text-xs text-slate-500">IBAN</p>
                                                <div class="flex items-center gap-2">
                                                    <p class="font-mono font-semibold text-slate-800">{{ $bank->iban }}</p>
                                                    <button onclick="event.stopPropagation(); copyToClipboard('{{ $bank->iban }}', this)" class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600 hover:bg-slate-200">Copy</button>
                                                </div>
                                            </div>
                                        @endif
                                        @if($bank->country)
                                            <div>
                                                <p class="text-xs text-slate-500">Country</p>
                                                <p class="font-semibold text-slate-800">{{ $bank->country }}</p>
                                            </div>
                                        @endif
                                        @if($bank->branch_name)
                                            <div>
                                                <p class="text-xs text-slate-500">Branch</p>
                                                <p class="font-semibold text-slate-800">{{ $bank->branch_name }}</p>
                                            </div>
                                        @endif
                                    </div>
                                    @if($bank->payment_instructions)
                                        <div class="mt-2 rounded bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                            {{ $bank->payment_instructions }}
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-4 flex-shrink-0">
                                    <div class="h-5 w-5 rounded-full border-2 {{ $loop->first ? 'border-[#0d5368]' : 'border-slate-300' }} flex items-center justify-center" id="radio-{{ $index }}">
                                        @if($loop->first)
                                            <div class="h-2.5 w-2.5 rounded-full bg-[#0d5368]"></div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            {{-- Fallback: single bank from legacy $instructions --}}
            <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-5">
                <h2 class="text-sm font-bold text-slate-900">Bank Details</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-slate-500">Bank Name</p>
                        <p class="font-semibold text-slate-800">{{ $instructions['bank_name'] ?? 'Not configured' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Account Name</p>
                        <p class="font-semibold text-slate-800">{{ $instructions['account_name'] ?? 'Not configured' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Account Number</p>
                        <div class="flex items-center gap-2">
                            <p class="font-mono font-semibold text-slate-800">{{ $instructions['account_number'] ?? 'Not configured' }}</p>
                            @if(!empty($instructions['account_number']))
                                <button onclick="copyToClipboard('{{ $instructions['account_number'] }}', this)" class="rounded bg-slate-200 px-2 py-0.5 text-xs font-bold text-slate-600 hover:bg-slate-300">Copy</button>
                            @endif
                        </div>
                    </div>
                    @if(!empty($instructions['swift_code']))
                        <div>
                            <p class="text-xs text-slate-500">SWIFT/BIC</p>
                            <p class="font-mono font-semibold text-slate-800">{{ $instructions['swift_code'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Proof of Payment Upload --}}
        <div class="mt-8 border-t border-slate-200 pt-6">
            <h2 class="text-lg font-bold text-slate-900">Upload Proof of Payment</h2>
            <p class="mt-1 text-sm text-slate-600">After making the transfer, upload your payment receipt/screenshot for admin verification.</p>

            <form method="POST" action="{{ route('payment.upload-proof', $paymentRequest->secure_token) }}" enctype="multipart/form-data" class="mt-4 space-y-4" id="proofForm">
                @csrf
                <input type="hidden" name="payment_reference" value="{{ $reference }}">
                <input type="hidden" name="bank_account_id" id="selectedBankId" value="{{ $bankAccounts[0]->id ?? '' }}">

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Payment Reference</label>
                    <div class="mt-1 flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                        <span class="font-mono text-sm font-bold text-[#0d5368]">{{ $reference }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">This is automatically included. Do not change.</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Proof File *</label>
                    <div class="mt-1 flex items-center justify-center rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-8 transition-all hover:border-[#0d5368] hover:bg-[#0d5368]/5" id="dropZone">
                        <div class="text-center" id="dropContent">
                            <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <p class="mt-2 text-sm text-slate-600"><span class="font-bold text-[#0d5368]">Click to upload</span> or drag & drop</p>
                            <p class="mt-1 text-xs text-slate-400">JPG, PNG, or PDF — max 10 MB</p>
                        </div>
                        <div class="hidden text-center" id="filePreview">
                            <svg class="mx-auto h-8 w-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <p class="mt-1 text-sm font-bold text-slate-700" id="fileName"></p>
                            <p class="text-xs text-slate-500" id="fileSize"></p>
                        </div>
                    </div>
                    <input type="file" name="proof_file" id="proofFile" accept=".jpg,.jpeg,.png,.pdf" required class="hidden">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Notes (Optional)</label>
                    <textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]" placeholder="e.g. Transferred from my savings account"></textarea>
                </div>

                <button type="submit" class="w-full rounded-lg bg-[#0d5368] px-6 py-3 text-sm font-bold text-white hover:bg-[#0b4658] focus:ring-2 focus:ring-[#0d5368] focus:ring-offset-2">
                    Submit Proof of Payment
                </button>
            </form>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-6 flex flex-wrap justify-center gap-3 border-t border-slate-200 pt-6">
            <a href="{{ route('payment-request.show', $paymentRequest->secure_token) }}" class="rounded-lg bg-[#0d5368] px-6 py-2.5 text-sm font-bold text-white hover:bg-[#0b4658]">
                Check Payment Status
            </a>
            @if($paymentRequest->shipment)
                <a href="{{ route('tracking.number', $paymentRequest->shipment->tracking_number) }}" class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                    Track Shipment
                </a>
            @endif
            <button onclick="window.print()" class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                Print Instructions
            </button>
            <a href="{{ route('payment-request.instructions-pdf', $paymentRequest->secure_token) }}" class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-bold text-[#0d5368] hover:bg-[#0d5368]/5">
                Download PDF
            </a>
        </div>
    </section>
</div>

<script>
function selectBank(index) {
    document.querySelectorAll('.bank-card').forEach((card, i) => {
        if (i === index) {
            card.classList.add('border-[#0d5368]', 'bg-[#0d5368]/5');
            card.classList.remove('border-slate-200', 'hover:border-slate-300');
            card.querySelector('[id^="radio-"]').innerHTML = '<div class="h-2.5 w-2.5 rounded-full bg-[#0d5368]"></div>';
            card.querySelector('[id^="radio-"]').classList.add('border-[#0d5368]');
            card.querySelector('[id^="radio-"]').classList.remove('border-slate-300');
        } else {
            card.classList.remove('border-[#0d5368]', 'bg-[#0d5368]/5');
            card.classList.add('border-slate-200', 'bg-white', 'hover:border-slate-300');
            card.querySelector('[id^="radio-"]').innerHTML = '';
            card.querySelector('[id^="radio-"]').classList.remove('border-[#0d5368]');
            card.querySelector('[id^="radio-"]').classList.add('border-slate-300');
        }
    });
    var bankIds = @json($bankAccounts->pluck('id')->toArray());
    document.getElementById('selectedBankId').value = bankIds[index] || '';
}

function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
        var orig = btn.innerHTML;
        btn.innerHTML = 'Copied!';
        btn.classList.add('bg-emerald-100', 'text-emerald-700');
        setTimeout(function() {
            btn.innerHTML = orig;
            btn.classList.remove('bg-emerald-100', 'text-emerald-700');
        }, 2000);
    });
}

var dropZone = document.getElementById('dropZone');
var fileInput = document.getElementById('proofFile');
var dropContent = document.getElementById('dropContent');
var filePreview = document.getElementById('filePreview');
var fileName = document.getElementById('fileName');
var fileSize = document.getElementById('fileSize');

dropZone.addEventListener('click', function() { fileInput.click(); });
dropZone.addEventListener('dragover', function(e) { e.preventDefault(); dropZone.classList.add('border-[#0d5368]', 'bg-[#0d5368]/5'); });
dropZone.addEventListener('dragleave', function() { dropZone.classList.remove('border-[#0d5368]', 'bg-[#0d5368]/5'); });
dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    dropZone.classList.remove('border-[#0d5368]', 'bg-[#0d5368]/5');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        showFile(e.dataTransfer.files[0]);
    }
});
fileInput.addEventListener('change', function() {
    if (fileInput.files.length) showFile(fileInput.files[0]);
});

function showFile(file) {
    fileName.textContent = file.name;
    var size = file.size < 1024*1024
        ? (file.size/1024).toFixed(1) + ' KB'
        : (file.size/(1024*1024)).toFixed(1) + ' MB';
    fileSize.textContent = size;
    dropContent.classList.add('hidden');
    filePreview.classList.remove('hidden');
}
</script>

<style>
@media print {
    .no-print, nav, footer, [x-data] { display: none !important; }
}
</style>
@endsection
