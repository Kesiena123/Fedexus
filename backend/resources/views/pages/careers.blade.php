@extends('layouts.app')

@section('title', 'Careers')

@section('content')
    <section class="bg-navy-gradient text-center text-white">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-16 lg:py-20">
            <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-gold-400"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            <h1 class="font-display text-4xl font-bold tracking-tight md:text-5xl">Careers at FreightFlow</h1>
            <p class="mx-auto mt-4 max-w-2xl text-navy-100">Join the teams building reliable international shipping, support, warehouse, and driver operations.</p>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 grid gap-6 lg:grid-cols-[360px_1fr]">
            <div class="grid h-max gap-4">
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-azure-600"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <h2 class="font-bold text-navy-900">Teams that move together</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Operations, product, support, warehouse, and fleet teams collaborate around real shipment workflows.</p>
                </div>
                <div class="rounded-2xl border border-navy-100 bg-white p-6 shadow-card">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-gold-600"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <h2 class="font-bold text-navy-900">Hub and remote roles</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Work from control towers, logistics hubs, or distributed support roles depending on the team.</p>
                </div>
            </div>

            <div class="rounded-2xl border border-navy-100 bg-white shadow-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left text-sm">
                        <thead class="bg-navy-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="p-4 font-semibold">Role</th>
                                <th class="p-4 font-semibold">Location</th>
                                <th class="p-4 font-semibold">Team</th>
                                <th class="p-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([
                                ['Logistics Operations Manager', 'Memphis, TN', 'Operations'],
                                ['Warehouse Systems Specialist', 'Louisville, KY', 'Warehouse'],
                                ['Customer Support Lead', 'Remote', 'Support'],
                                ['Driver Experience Coordinator', 'Dallas, TX', 'Fleet'],
                            ] as [$role, $location, $team])
                                <tr class="border-t border-navy-100">
                                    <td class="p-4 font-bold text-navy-900">{{ $role }}</td>
                                    <td class="p-4 text-slate-600">{{ $location }}</td>
                                    <td class="p-4 text-slate-600">{{ $team }}</td>
                                    <td class="p-4 text-right"><x-ui.button variant="outline" size="sm">Apply</x-ui.button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
