@extends('layouts.app')
@section('title', 'Security')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Security">
        <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white/60 p-16 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-300"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <h3 class="mt-4 text-lg font-semibold text-navy-900">Security</h3>
            <p class="mt-2 max-w-md text-sm text-slate-500">Access control, IP restrictions, and security policy management coming soon.</p>
        </div>
    </x-dashboard-shell>
@endsection