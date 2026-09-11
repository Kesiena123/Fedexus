@extends('layouts.app')
@section('title', 'Warehouse Management')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Warehouse Management">
        <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white/60 p-16 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-300"><path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/><path d="M6 18h12"/><path d="M6 14h12"/><rect width="12" height="12" x="6" y="10"/></svg>
            <h3 class="mt-4 text-lg font-semibold text-navy-900">Warehouse Management</h3>
            <p class="mt-2 max-w-md text-sm text-slate-500">Inventory tracking, stock management, and warehouse operations tools coming soon.</p>
        </div>
    </x-dashboard-shell>
@endsection