@extends('layouts.app')
@section('title', 'Payment Settings')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Payment Settings">
        @if(session('success'))
            <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
            <form method="POST" action="{{ route('admin.payment-settings') }}" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm" x-data="gatewayForm()" x-init="init()">
                @csrf
                <h3 class="text-lg font-bold text-slate-900">Gateway Configuration</h3>
                <p class="mt-1 text-sm text-slate-500">Credentials are encrypted at rest. Never share secret keys.</p>

                <div class="mt-5 grid gap-4">
                    {{-- GATEWAY SELECT --}}
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Payment Gateway</label>
                        <select name="gateway_name" required class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3" x-model="selectedGateway" @change="switchGateway()">
                            <option value="">Select gateway</option>
                            @foreach($registeredGateways as $gw)
                                <option value="{{ $gw }}">{{ ucfirst(str_replace('_', ' ', $gw)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <input name="merchant_name" x-ref="merchant_name" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3" placeholder="Merchant / Business Name">

                    {{-- ENVIRONMENT MODE --}}
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Environment</label>
                        <div class="flex rounded-md border border-slate-200 overflow-hidden">
                            <label class="flex-1 cursor-pointer text-center py-2.5 text-sm font-semibold transition-colors"
                                   :class="modeValue === 'test' ? 'bg-amber-500 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'">
                                <input type="radio" name="mode" value="test" x-model="modeValue" class="sr-only">
                                Test Mode
                            </label>
                            <label class="flex-1 cursor-pointer text-center py-2.5 text-sm font-semibold transition-colors border-l border-slate-200"
                                   :class="modeValue === 'live' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'">
                                <input type="radio" name="mode" value="live" x-model="modeValue" class="sr-only">
                                Live Mode
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-slate-400" x-text="modeValue === 'test' ? 'Using test credentials. No real charges.' : 'Using LIVE credentials. Real money at stake.'"></p>
                    </div>

                    {{-- FLUTTERWAVE BANNER --}}
                    <div x-show="selectedGateway === 'flutterwave'" x-cloak class="rounded-md border border-azure-200 bg-azure-50 px-3 py-2 text-xs font-semibold text-azure-700">
                        Flutterwave <span x-text="modeValue === 'live' ? 'Live' : 'Test'"></span> Credentials
                        <span x-show="savedData.flutterwave?.has_flutterwave_test_credentials && modeValue === 'test'" class="ml-2 text-emerald-600">&#10003; Saved</span>
                        <span x-show="savedData.flutterwave?.has_flutterwave_live_credentials && modeValue === 'live'" class="ml-2 text-emerald-600">&#10003; Saved</span>
                    </div>
                    <div x-show="selectedGateway === 'stripe'" x-cloak class="rounded-md border border-purple-200 bg-purple-50 px-3 py-2 text-xs font-semibold text-purple-700">
                        Stripe <span x-text="modeValue === 'live' ? 'Live' : 'Test'"></span> Credentials
                    </div>
                    <div x-show="selectedGateway === 'paypal'" x-cloak class="rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700">
                        PayPal <span x-text="modeValue === 'live' ? 'Live' : 'Sandbox'"></span> Credentials
                    </div>
                    <div x-show="selectedGateway === 'crypto'" x-cloak class="rounded-md border border-purple-200 bg-purple-50 px-3 py-2 text-xs font-semibold text-purple-700">
                        Crypto Payment Credentials
                    </div>
                    <div x-show="selectedGateway === 'bank_transfer'" x-cloak class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700">
                        Bank Transfer requires no API credentials. Configure bank details below.
                    </div>

                    {{-- API KEY (PayPal: Client ID, Crypto: API Key) --}}
                    <div x-show="selectedGateway === 'paypal' || selectedGateway === 'crypto'" x-cloak>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">
                            <span x-show="selectedGateway === 'paypal'">Client ID</span>
                            <span x-show="selectedGateway === 'crypto'">API Key <span class="font-normal text-slate-400">(NOWPayments)</span></span>
                        </label>
                        <div class="relative">
                            <input name="api_key" x-ref="api_key" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 pr-20" :placeholder="selectedGateway === 'paypal' ? 'PayPal Client ID' : 'NOWPayments API Key'" :type="showKeys ? 'text' : 'password'">
                            <span x-show="savedData[selectedGateway]?.has_api_key && !showKeys" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-emerald-600 font-semibold">Saved</span>
                        </div>
                    </div>

                    {{-- PUBLIC KEY (Flutterwave: FLWPUBK-..., Stripe: pk_test_...) --}}
                    <div x-show="selectedGateway === 'flutterwave' || selectedGateway === 'stripe'" x-cloak>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">
                            <span x-show="selectedGateway === 'flutterwave'">Public Key <span class="font-normal text-slate-400">(FLWPUBK-...)</span></span>
                            <span x-show="selectedGateway === 'stripe'">Publishable Key <span class="font-normal text-slate-400">(pk_test_...)</span></span>
                        </label>
                        <div class="relative">
                            <input name="public_key" x-ref="public_key" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 pr-20" :placeholder="selectedGateway === 'flutterwave' ? 'FLWPUBK_TEST-xxxxxxxx' : 'pk_test_xxxxxxxxxxxxx'" :type="showKeys ? 'text' : 'password'">
                            <span x-show="savedData[selectedGateway]?.has_public_key && !showKeys" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-emerald-600 font-semibold">Saved</span>
                        </div>
                    </div>

                    {{-- SECRET KEY (all except bank_transfer) --}}
                    <div x-show="selectedGateway && selectedGateway !== 'bank_transfer'" x-cloak>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">
                            <span x-show="selectedGateway === 'flutterwave'">Secret Key <span class="font-normal text-slate-400">(FLWSECK-...)</span></span>
                            <span x-show="selectedGateway === 'stripe'">Secret Key <span class="font-normal text-slate-400">(sk_test_...)</span></span>
                            <span x-show="selectedGateway === 'paypal'">Client Secret</span>
                            <span x-show="selectedGateway === 'crypto'">Secret Key</span>
                        </label>
                        <div class="relative">
                            <input name="secret_key" x-ref="secret_key" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 pr-20" :placeholder="secretPlaceholder()" :type="showKeys ? 'text' : 'password'">
                            <span x-show="savedData[selectedGateway]?.has_secret_key && !showKeys" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-emerald-600 font-semibold">Saved</span>
                        </div>
                    </div>

                    {{-- ENCRYPTION KEY (Flutterwave only) --}}
                    <div x-show="selectedGateway === 'flutterwave'" x-cloak>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Encryption Key <span class="font-normal text-slate-400">(for inline checkout)</span></label>
                        <div class="relative">
                            <input name="encryption_key" x-ref="encryption_key" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 pr-20" placeholder="Encryption key from Flutterwave Dashboard" :type="showKeys ? 'text' : 'password'">
                            <span x-show="savedData[selectedGateway]?.has_encryption_key && !showKeys" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-emerald-600 font-semibold">Saved</span>
                        </div>
                    </div>

                    {{-- WEBHOOK SECRET HASH (all except bank_transfer) --}}
                    <div x-show="selectedGateway && selectedGateway !== 'bank_transfer'" x-cloak>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Webhook Secret Hash</label>
                        <div class="relative">
                            <input name="webhook_secret" x-ref="webhook_secret" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 pr-20" placeholder="Webhook secret from your Dashboard" :type="showKeys ? 'text' : 'password'">
                            <span x-show="savedData[selectedGateway]?.has_webhook_secret && !showKeys" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-emerald-600 font-semibold">Saved</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-400" x-show="selectedGateway === 'flutterwave'">Found in Flutterwave Dashboard > Settings > Webhooks</p>
                    </div>

                    {{-- Show credentials toggle --}}
                    <label class="flex items-center gap-2 text-xs text-slate-500 cursor-pointer">
                        <input type="checkbox" x-model="showKeys" class="rounded border-slate-300">
                        Show credentials in plain text
                    </label>

                    {{-- WEBHOOK URL --}}
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Webhook URL</label>
                        <input name="webhook_url" x-ref="webhook_url" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm font-mono" placeholder="Auto-generated if empty" :value="defaultWebhookUrl(selectedGateway)">
                        <p class="mt-1 text-xs text-slate-400">
                            Notifications are sent as POST to this URL.
                            <template x-if="modeValue === 'live'">
                                <span class="font-semibold text-amber-600"> Production requires a public HTTPS domain.</span>
                            </template>
                        </p>
                        <p class="mt-0.5 text-xs text-slate-400" x-show="selectedGateway">
                            Route: <span class="font-mono text-slate-500" x-text="'POST /api/payments/webhook/' + selectedGateway"></span>
                        </p>
                    </div>

                    {{-- CURRENCY --}}
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Default Currency</label>
                        <input name="currency" required x-ref="currency" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3" placeholder="USD">
                    </div>

                    {{-- FEES --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Processing Fee %</label>
                            <input name="processing_fee_percent" x-ref="processing_fee_percent" type="number" step="0.01" min="0" max="100" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Fixed Fee ($)</label>
                            <input name="fixed_fee" x-ref="fixed_fee" type="number" step="0.01" min="0" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3">
                        </div>
                    </div>

                    {{-- AMOUNT LIMITS --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Min Amount</label>
                            <input name="min_amount" x-ref="min_amount" type="number" step="0.01" min="0" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Max Amount</label>
                            <input name="max_amount" x-ref="max_amount" type="number" step="0.01" min="0" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3">
                        </div>
                    </div>

                    {{-- BANK TRANSFER — LINK TO BANK ACCOUNT MANAGER --}}
                    <div x-show="selectedGateway === 'bank_transfer'" x-cloak class="rounded-md border border-amber-200 bg-amber-50 p-4">
                        <h4 class="text-sm font-bold text-amber-800">Bank Account Management</h4>
                        <p class="mt-1 text-xs text-amber-700">Manage multiple bank accounts with full CRUD, currencies, and defaults.</p>
                        <a href="{{ route('admin.bank-accounts') }}" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-bold text-white transition-all hover:bg-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                            Manage Bank Accounts
                        </a>
                        <div class="mt-3 rounded border border-amber-300 bg-white/60 p-3 text-xs text-amber-800">
                            <strong>Note:</strong> Bank account details are managed separately. Enable bank_transfer here, then 
                            <a href="{{ route('admin.bank-accounts') }}" class="font-bold underline">add bank accounts</a> 
                            that customers will see during checkout.
                        </div>
                    </div>

                    {{-- CRYPTO CONFIG --}}
                    <div x-show="selectedGateway === 'crypto'" x-cloak class="rounded-md border border-purple-200 bg-purple-50 p-4">
                        <h4 class="text-sm font-bold text-purple-800">Crypto Payment Settings</h4>
                        <div class="mt-3 grid gap-3">
                            <select name="config[provider]" x-ref="config_provider" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm">
                                <option value="nowpayments">NOWPayments</option>
                                <option value="generic">Generic API</option>
                            </select>
                            <select name="config[preferred_coin]" x-ref="config_preferred_coin" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm">
                                <option value="btc">Bitcoin (BTC)</option>
                                <option value="eth">Ethereum (ETH)</option>
                                <option value="usdt">Tether (USDT)</option>
                                <option value="usdc">USD Coin (USDC)</option>
                                <option value="ltc">Litecoin (LTC)</option>
                                <option value="sol">Solana (SOL)</option>
                            </select>
                            <input name="config[api_url]" x-ref="config_api_url" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="API URL (for generic provider)">
                            <input name="config[ipn_secret]" x-ref="config_ipn_secret" type="password" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 text-sm" placeholder="IPN Secret (NOWPayments)">
                        </div>
                    </div>

                    {{-- ENABLE TOGGLE --}}
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input name="is_active" value="1" type="checkbox" x-ref="is_active" class="rounded border-slate-300 text-azure-500">
                        Enable this gateway
                    </label>

                    {{-- SUBMIT --}}
                    <div class="flex gap-2">
                        <button class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md bg-azure-500 px-5 text-sm font-bold text-white hover:bg-azure-600">Save Gateway</button>
                        <button type="button" @click="testGateway()" class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md border border-slate-200 px-5 text-sm font-bold text-slate-700 hover:bg-slate-50" :disabled="!selectedGateway">Test Connection</button>
                    </div>
                </div>
            </form>

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900">Configured Gateways</h3>
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[700px] text-left text-sm">
                        <thead class="text-xs uppercase text-slate-500">
                            <tr><th class="border-b p-3">Gateway</th><th class="border-b p-3">Mode</th><th class="border-b p-3">Currency</th><th class="border-b p-3">Fee</th><th class="border-b p-3">Status</th><th class="border-b p-3">Updated</th><th class="border-b p-3">Action</th></tr>
                        </thead>
                        <tbody>
                            @forelse($paymentSettings as $setting)
                                <tr>
                                    <td class="border-b border-slate-100 p-3">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-block h-2.5 w-2.5 rounded-full {{ $setting->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                            <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $setting->gateway_name)) }}</span>
                                        </div>
                                    </td>
                                    <td class="border-b border-slate-100 p-3">
                                        <span class="rounded px-2 py-0.5 text-xs font-semibold {{ $setting->mode === 'live' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst($setting->mode) }}</span>
                                    </td>
                                    <td class="border-b border-slate-100 p-3">{{ $setting->currency }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ $setting->processing_fee_percent }}% + ${{ number_format($setting->fixed_fee, 2) }}</td>
                                    <td class="border-b border-slate-100 p-3">
                                        <span class="font-semibold {{ $setting->is_active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $setting->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="border-b border-slate-100 p-3">{{ $setting->updated_at->format('M d, Y H:i') }}</td>
                                    <td class="border-b border-slate-100 p-3">
                                        <form method="POST" action="{{ route('admin.payment-settings.test', $setting->gateway_name) }}" class="inline">
                                            @csrf
                                            <button class="rounded border border-slate-200 px-2 py-1 text-xs font-bold text-slate-600 hover:bg-slate-50">Test</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="p-6 text-center text-slate-500">No payment gateways configured. Select a gateway from the form to get started.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-dashboard-shell>

    <script>
    function gatewayForm() {
        return {
            showKeys: false,
            selectedGateway: '',
            modeValue: 'test',
            savedData: @json($gatewayData ?? []),
            init() {
                const edit = '{{ $editGateway ?? '' }}';
                if (edit && this.savedData[edit]) {
                    this.selectedGateway = edit;
                    this.modeValue = this.savedData[edit]?.mode || 'test';
                    this.$nextTick(() => this.populateForm(edit));
                }
            },
            secretPlaceholder() {
                if (this.selectedGateway === 'flutterwave') return 'FLWSECK_TEST-xxxxxxxx';
                if (this.selectedGateway === 'stripe') return 'sk_test_xxxxxxxxxxxxx';
                if (this.selectedGateway === 'paypal') return 'PayPal Client Secret';
                return 'Secret Key';
            },
            switchGateway() {
                if (this.selectedGateway && this.savedData[this.selectedGateway]) {
                    this.modeValue = this.savedData[this.selectedGateway]?.mode || 'test';
                    this.populateForm(this.selectedGateway);
                } else {
                    this.clearForm();
                }
            },
            defaultWebhookUrl(name) {
                if (!name) return '{{ url("/api/payments/webhook") }}';
                return '{{ url("/api/payments/webhook") }}/' + name;
            },
            populateForm(name) {
                const d = this.savedData[name];
                if (!d) return;
                this.modeValue = d.mode || 'test';
                this.$refs.merchant_name.value = d.merchant_name || '';
                this.$refs.currency.value = d.currency || 'USD';
                this.$refs.processing_fee_percent.value = d.processing_fee_percent ?? 0;
                this.$refs.fixed_fee.value = d.fixed_fee ?? 0;
                this.$refs.min_amount.value = d.min_amount ?? '';
                this.$refs.max_amount.value = d.max_amount ?? '';
                this.$refs.is_active.checked = !!d.is_active;
                this.$refs.webhook_url.value = d.webhook_url || this.defaultWebhookUrl(name);
                this.$refs.api_key.value = '';
                this.$refs.secret_key.value = '';
                this.$refs.public_key.value = '';
                this.$refs.webhook_secret.value = '';
                this.$refs.encryption_key.value = '';
                if (d.config) {
                    if (d.config.provider) this.$refs.config_provider.value = d.config.provider;
                    if (d.config.preferred_coin) this.$refs.config_preferred_coin.value = d.config.preferred_coin;
                    if (d.config.api_url) this.$refs.config_api_url.value = d.config.api_url;
                    if (d.config.ipn_secret) this.$refs.config_ipn_secret.value = d.config.ipn_secret;
                }
            },
            clearForm() {
                this.modeValue = 'test';
                this.$refs.merchant_name.value = '';
                this.$refs.api_key.value = '';
                this.$refs.secret_key.value = '';
                this.$refs.public_key.value = '';
                this.$refs.webhook_secret.value = '';
                this.$refs.encryption_key.value = '';
                this.$refs.webhook_url.value = this.defaultWebhookUrl(this.selectedGateway);
                this.$refs.currency.value = 'USD';
                this.$refs.processing_fee_percent.value = 0;
                this.$refs.fixed_fee.value = 0;
                this.$refs.min_amount.value = '';
                this.$refs.max_amount.value = '';
                this.$refs.is_active.checked = false;
            },
            testGateway() {
                if (!this.selectedGateway) {
                    alert('Select a gateway first');
                    return;
                }
                if (confirm('Test connection for ' + this.selectedGateway + '?')) {
                    const form = document.querySelector('form');
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = '_test';
                    input.value = '1';
                    form.appendChild(input);
                    form.action = '{{ url("/admin/settings/payment") }}/' + this.selectedGateway + '/test';
                    form.submit();
                }
            }
        }
    }
    </script>
@endsection
