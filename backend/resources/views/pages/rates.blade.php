@extends('layouts.app')

@section('title', 'Rates')

@section('content')
    <section class="bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-14 lg:py-20">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-navy-200 bg-navy-50 px-3 py-1 text-xs font-semibold text-azure-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Shipping quote calculator
            </span>
            <div class="mt-5 grid gap-8 lg:grid-cols-[0.85fr_1.15fr] lg:items-end">
                <div>
                    <h1 class="font-display text-4xl font-bold tracking-tight text-navy-900 md:text-5xl">Rates and transit times</h1>
                    <p class="mt-4 text-lg leading-8 text-slate-600">Compare courier, freight, and international service levels with milestone-based payment estimates.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach(['Live quote API ready', '20% staged billing', 'Admin verification'] as $item)
                        <div class="rounded-2xl border border-navy-100 bg-slate-50 p-4 text-sm font-semibold text-navy-800">{{ $item }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid gap-6 py-12 lg:grid-cols-[420px_1fr]">
        <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card h-max">
            <h2 class="flex items-center gap-2 font-display text-xl font-bold text-navy-900">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Quote details
            </h2>
            <form class="mt-4 grid gap-4">
                <x-ui.input name="origin" placeholder="From ZIP or city" />
                <x-ui.input name="destination" placeholder="To ZIP or city" />
                <x-ui.input name="weight" type="number" placeholder="Weight kg" />
                <x-ui.input name="value" type="number" placeholder="Declared value" />
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Package type</label>
                    <select name="package_type" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-3 text-sm text-navy-900">
                        <option>Package</option>
                        <option>Envelope</option>
                        <option>Freight pallet</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Account type</label>
                    <select name="account_type" class="focus-ring w-full rounded-xl border border-navy-200 bg-white px-4 py-3 text-sm text-navy-900">
                        <option>Business account</option>
                        <option>Guest shipment</option>
                        <option>Enterprise contract</option>
                    </select>
                </div>
                <x-ui.button variant="primary" size="md">Calculate rates</x-ui.button>
            </form>
        </div>

        <div class="grid gap-4">
            @foreach([
                ['Domestic Express', 48.20, '1 business day', ['Priority pickup', 'Signature proof', 'SMS updates']],
                ['International Priority', 142.75, '2-4 business days', ['Customs support', 'Route visibility', 'Sequential payments']],
                ['Economy Freight', 312.40, '5-8 business days', ['Pallet handling', 'Warehouse scans', 'Invoice download']],
            ] as [$name, $price, $eta, $features])
                <div class="rounded-2xl border border-navy-100 bg-white shadow-card overflow-hidden">
                    <div class="grid gap-5 p-6 md:grid-cols-[1fr_auto] md:items-center">
                        <div>
                            <h3 class="font-display text-xl font-bold text-navy-900">{{ $name }}</h3>
                            <p class="mt-1 flex items-center gap-2 text-sm text-slate-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                {{ $eta }}
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach($features as $feature)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-navy-50 px-3 py-1 text-xs font-semibold text-navy-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-600"><path d="M20 6 9 17l-5-5"/></svg>
                                        {{ $feature }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div class="rounded-2xl bg-azure-50 p-5 text-left md:text-right">
                            <p class="font-display text-2xl font-bold text-azure-700">${{ number_format($price, 2) }}</p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-500">Estimated total</p>
                            <p class="mt-3 flex items-center gap-2 text-sm text-slate-600 md:justify-end">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                                ${{ number_format($price * 0.2, 2) }} first stage
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 pb-16">
        <div class="rounded-2xl border border-navy-100 bg-navy-gradient text-white p-8 shadow-card">
            <div class="grid gap-5 md:grid-cols-[auto_1fr_auto] md:items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-400"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <div>
                    <h2 class="font-display text-xl font-bold">Payment stages stay hidden until unlocked</h2>
                    <p class="mt-2 text-sm leading-6 text-navy-100">Customers only see the active installment after an administrator approves the previous shipment milestone.</p>
                </div>
                <x-ui.button variant="accent">View workflow</x-ui.button>
            </div>
        </div>
    </div>
@endsection
