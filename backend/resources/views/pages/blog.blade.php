@extends('layouts.app')

@section('title', 'Blog')

@section('content')
    <section class="bg-navy-gradient text-center text-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-20">
            <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-gold-400"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>
            <h1 class="font-display text-4xl font-bold tracking-tight md:text-5xl">Logistics insights</h1>
            <p class="mx-auto mt-4 max-w-2xl text-navy-100">Operational updates, shipping guides, and technology notes from FreightFlow.</p>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid gap-6 md:grid-cols-3">
            @foreach([
                ['Building resilient global delivery networks', 'Operations', 'How connected warehouses, route events, and support teams reduce blind spots across borders.', 'https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?auto=format&fit=crop&w=900&q=80'],
                ['Sequential payments for high-value logistics', 'Payments', 'Why milestone-based payment releases help protect both shippers and customers.', 'https://images.unsplash.com/photo-1556740758-90de374c12ad?auto=format&fit=crop&w=900&q=80'],
                ['A practical guide to customs readiness', 'Guides', 'Documents, declared values, duties, and processing signals that keep international freight moving.', 'https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=900&q=80'],
            ] as [$title, $tag, $body, $img])
                <article class="group overflow-hidden rounded-2xl border border-navy-100 bg-white shadow-card hover:shadow-lift transition-shadow">
                    <div class="relative overflow-hidden">
                        <img src="{{ $img }}" alt="" width="900" height="208" class="h-52 w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy" />
                        <span class="absolute left-4 top-4">
                            <x-ui.badge variant="navy">{{ $tag }}</x-ui.badge>
                        </span>
                    </div>
                    <div class="p-5">
                        <h2 class="font-display text-xl font-bold text-navy-900">{{ $title }}</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $body }}</p>
                        <a href="{{ url('/support') }}" class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-azure-700">
                            Read more
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endsection
