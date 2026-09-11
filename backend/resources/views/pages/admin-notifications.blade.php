@extends('layouts.app')
@section('title', 'Notifications')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Notifications">
        <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white/60 p-16 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-300"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <h3 class="mt-4 text-lg font-semibold text-navy-900">Notifications</h3>
            <p class="mt-2 max-w-md text-sm text-slate-500">System notifications, alerts, and broadcast management coming soon.</p>
        </div>
    </x-dashboard-shell>
@endsection