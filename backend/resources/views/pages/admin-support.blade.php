@extends('layouts.app')
@section('title', 'Support Center')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Support Center">
        <div class="space-y-6 p-6">

            @if(session('status_updated'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    Ticket status updated successfully.
                </div>
            @endif

            {{-- Summary Cards --}}
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-navy-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        </span>
                        <div>
                            <p class="text-2xl font-bold text-navy-900">{{ $openCount }}</p>
                            <p class="text-xs text-slate-500">Open tickets</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-navy-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </span>
                        <div>
                            <p class="text-2xl font-bold text-navy-900">{{ $pendingCount }}</p>
                            <p class="text-xs text-slate-500">Pending</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-navy-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </span>
                        <div>
                            <p class="text-2xl font-bold text-navy-900">{{ $resolvedCount }}</p>
                            <p class="text-xs text-slate-500">Resolved</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tickets Table --}}
            <div class="rounded-2xl border border-navy-100 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-navy-100 px-6 py-4">
                    <h2 class="font-display text-lg font-bold text-navy-900">All Tickets</h2>
                </div>

                @if($tickets->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-slate-300"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <p class="mt-3 text-sm font-medium text-slate-500">No support tickets yet</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-navy-100 bg-slate-50/60">
                                    <th class="px-6 py-3 font-semibold text-slate-600">ID</th>
                                    <th class="px-6 py-3 font-semibold text-slate-600">Subject</th>
                                    <th class="px-6 py-3 font-semibold text-slate-600">Category</th>
                                    <th class="px-6 py-3 font-semibold text-slate-600">Status</th>
                                    <th class="px-6 py-3 font-semibold text-slate-600">Created</th>
                                    <th class="px-6 py-3 font-semibold text-slate-600">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-navy-100">
                                @foreach($tickets as $ticket)
                                    <tr class="hover:bg-slate-50/40 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs text-slate-500">#{{ $ticket->id }}</td>
                                        <td class="px-6 py-4">
                                            <p class="font-medium text-navy-900">{{ $ticket->subject }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500 line-clamp-1">{{ Str::limit($ticket->body, 80) }}</p>
                                        </td>
                                        <td class="px-6 py-4">
                                            @php
                                                $catColors = [
                                                    'delivery' => 'bg-blue-100 text-blue-700',
                                                    'billing' => 'bg-amber-100 text-amber-700',
                                                    'customs' => 'bg-purple-100 text-purple-700',
                                                    'damage' => 'bg-red-100 text-red-700',
                                                    'account' => 'bg-slate-100 text-slate-700',
                                                    'other' => 'bg-slate-100 text-slate-500',
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $catColors[$ticket->category] ?? 'bg-slate-100 text-slate-500' }}">
                                                {{ ucfirst($ticket->category) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            @php
                                                $statusColors = [
                                                    'open' => 'bg-amber-100 text-amber-700',
                                                    'pending' => 'bg-blue-100 text-blue-700',
                                                    'resolved' => 'bg-emerald-100 text-emerald-700',
                                                    'closed' => 'bg-slate-100 text-slate-500',
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$ticket->status] ?? 'bg-slate-100 text-slate-500' }}">
                                                {{ ucfirst($ticket->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-500">{{ $ticket->created_at->format('M j, Y') }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2" x-data="{ open: false }">
                                                <button @click="open = !open" class="rounded-lg border border-navy-200 px-3 py-1.5 text-xs font-medium text-navy-700 hover:bg-navy-50 transition-colors">
                                                    Update
                                                </button>
                                                <div x-show="open" @click.outside="open = false" x-cloak
                                                     class="absolute z-50 mt-1 w-40 rounded-xl border border-navy-100 bg-white p-1.5 shadow-lg">
                                                    @foreach(['open', 'pending', 'resolved', 'closed'] as $s)
                                                        @if($s !== $ticket->status)
                                                            <form method="POST" action="{{ route('admin.support.status', $ticket) }}">
                                                                @csrf
                                                                <input type="hidden" name="status" value="{{ $s }}">
                                                                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-xs font-medium text-navy-700 hover:bg-navy-50 transition-colors capitalize">
                                                                    Mark as {{ $s }}
                                                                </button>
                                                            </form>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-navy-100 px-6 py-3">
                        {{ $tickets->links() }}
                    </div>
                @endif
            </div>
        </div>
    </x-dashboard-shell>
@endsection
