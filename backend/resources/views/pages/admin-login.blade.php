@extends('layouts.app', ['hideHeaderFooter' => true])
@section('title', 'Admin Login')
@section('content')
<div
    x-data="{
        email: '{{ old('email') }}',
        password: '',
        captchaAnswer: '',
        captchaPrompt: '{{ session('captcha_prompt', '') }}',
        captchaToken: '{{ session('captcha_token', '') }}',
        needsTwoFactor: @json((bool)session('requires_two_factor')),
        twoFactorCode: '',
        loading: false,
        deviceRecognized: {{ session('device_unrecognized') ? 'false' : 'null' }},
        message: '{{ session('message', '') }}',
        error: '{{ $errors->first() }}',
        init() {
            if (!this.captchaPrompt) this.refreshCaptcha();
        },
        refreshCaptcha() {
            fetch('/api/admin/auth/captcha')
                .then(r => r.json())
                .then(d => { this.captchaPrompt = d.prompt; this.captchaToken = d.token; this.captchaAnswer = ''; })
                .catch(() => {});
        }
    }"
    class="relative min-h-screen overflow-hidden bg-[#eef3f8]"
>
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(20,99,255,0.18),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(245,166,35,0.14),transparent_28%)]"></div>
    <div class="absolute inset-0 enterprise-grid opacity-50"></div>

    <div class="relative mx-auto grid min-h-screen max-w-[1600px] gap-8 px-4 py-8 lg:grid-cols-[1.1fr_0.9fr] lg:px-8 lg:py-10">
        <section class="enterprise-panel relative overflow-hidden p-6 sm:p-8 lg:p-10">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(20,99,255,0.14),transparent_34%)]"></div>
            <div class="relative flex h-full flex-col justify-between gap-10">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-azure-200 bg-azure-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-azure-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                        Enterprise admin access
                    </div>
                    <h1 class="mt-5 max-w-2xl font-display text-4xl font-bold tracking-tight text-navy-950 sm:text-5xl">
                        Logistics operations control for global shipment, payment, and security teams.
                    </h1>
                    <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">
                        Secure sign-in to the FreightFlow command environment. Access is monitored, device-aware, and tied to auditable enterprise permissions.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-[26px] border border-white/70 bg-white/80 p-5 shadow-card">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <h2 class="mt-4 text-lg font-bold text-navy-950">Security-first</h2>
                        <p class="mt-2 text-sm leading-7 text-slate-600">CAPTCHA, token ability checks, idle timeout, and audit trails are active on every session.</p>
                    </div>
                    <div class="rounded-[26px] border border-white/70 bg-white/80 p-5 shadow-card">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.54 15H17a2 2 0 0 0-2 2v4.54"/><path d="M7 3.34V5a3 3 0 0 0 3 3v0a2 2 0 0 1 2 2v0a2 2 0 0 0 2 2v0a2 2 0 0 1 2 2v0c0 .86.44 1.63 1.16 2.07"/><path d="M10.56 2.86A9 9 0 0 0 2.86 10.56"/><path d="M22 12c0 5.52-4.48 10-10 10"/></svg>
                        <h2 class="mt-4 text-lg font-bold text-navy-950">Global oversight</h2>
                        <p class="mt-2 text-sm leading-7 text-slate-600">Run approvals, live tracking, and payment operations from a single workspace.</p>
                    </div>
                    <div class="rounded-[26px] border border-white/70 bg-white/80 p-5 shadow-card">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        <h2 class="mt-4 text-lg font-bold text-navy-950">Operational velocity</h2>
                        <p class="mt-2 text-sm leading-7 text-slate-600">Responsive layouts and guided workflows reduce training time for new operations staff.</p>
                    </div>
                </div>

                <div class="rounded-[28px] border border-navy-100 bg-navy-gradient p-6 text-white shadow-lift">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-azure-100/80">Readiness signal</p>
                            <p class="mt-2 text-2xl font-bold">Operations environment prepared for secure sign-in</p>
                        </div>
                        <div class="rounded-full bg-white/10 px-4 py-2 text-sm font-semibold">24/7 monitored</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="flex items-center justify-center">
            <div class="w-full max-w-xl rounded-[30px] border border-white/70 bg-[rgba(255,255,255,0.88)] shadow-lift backdrop-blur-xl">
                <div class="space-y-4 border-b border-slate-100 p-8">
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-navy-gradient text-white shadow-glow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-azure-700">Operations authentication</p>
                        <h2 class="mt-2 font-display text-3xl font-bold tracking-tight text-navy-950">Sign in to the admin command center</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Use your administrator credentials. Unknown devices trigger stepped-up verification automatically.</p>
                    </div>
                </div>
                <div class="p-8">
                    <form method="POST" action="{{ route('admin.login') }}" class="grid gap-4">
                        @csrf
                        <input type="hidden" name="captcha_token" x-model="captchaToken" />
                        <input type="hidden" name="needs_two_factor" x-model="needsTwoFactor" />

                        <label class="grid gap-2 text-sm font-semibold text-navy-900">
                            Email
                            <input type="email" name="email" x-model="email" autocomplete="email" required x-bind:disabled="needsTwoFactor"
                                class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 placeholder:text-slate-400 transition-colors hover:border-navy-300 focus:border-azure-400" />
                        </label>

                        <template x-if="needsTwoFactor">
                            <label class="grid gap-2 text-sm font-semibold text-navy-900">
                                Two-factor code
                                <input type="text" name="two_factor_code" x-model="twoFactorCode" inputmode="numeric" maxlength="6" required
                                    class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 placeholder:text-slate-400 transition-colors hover:border-navy-300 focus:border-azure-400" />
                            </label>
                        </template>

                        <template x-if="!needsTwoFactor">
                            <>
                                <label class="grid gap-2 text-sm font-semibold text-navy-900">
                                    Password
                                    <input type="password" name="password" x-model="password" autocomplete="current-password" required
                                        class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 placeholder:text-slate-400 transition-colors hover:border-navy-300 focus:border-azure-400" />
                                </label>
                                <div class="grid gap-4 sm:grid-cols-[1.1fr_0.9fr]">
                                    <label class="grid gap-2 text-sm font-semibold text-navy-900">
                                        CAPTCHA challenge
                                        <input type="text" readonly x-model="captchaPrompt" aria-label="Captcha challenge"
                                            class="w-full rounded-xl border border-navy-200 bg-slate-100 px-4 py-3 text-sm font-mono text-navy-700" />
                                    </label>
                                    <label class="grid gap-2 text-sm font-semibold text-navy-900">
                                        CAPTCHA answer
                                        <input type="number" name="captcha_answer" x-model="captchaAnswer" inputmode="numeric" required
                                            class="focus-ring min-h-12 w-full rounded-xl border border-navy-200 bg-white px-4 text-sm text-navy-900 placeholder:text-slate-400 transition-colors hover:border-navy-300 focus:border-azure-400" />
                                    </label>
                                </div>
                            </>
                        </template>

                        <template x-if="message">
                            <p x-text="message" class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-700"></p>
                        </template>
                        <template x-if="deviceRecognized === false">
                            <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700">
                                Unrecognized device detected. Two-factor verification is required.
                            </p>
                        </template>
                        <template x-if="error">
                            <p x-text="error" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"></p>
                        </template>

                        <x-ui.button type="submit" class="w-full" x-bind:disabled="loading">
                            <svg x-show="!loading" class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <span x-text="loading ? 'Signing in...' : (needsTwoFactor ? 'Verify code' : 'Sign in')"></span>
                            <svg x-show="loading" class="ml-2 h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        </x-ui.button>

                        <template x-if="!needsTwoFactor">
                            <x-ui.button type="button" variant="outline" class="w-full" @@click="refreshCaptcha()" x-bind:disabled="loading">
                                Refresh challenge
                            </x-ui.button>
                        </template>
                    </form>


                </div>
            </div>
        </section>
    </div>
</div>
@endsection