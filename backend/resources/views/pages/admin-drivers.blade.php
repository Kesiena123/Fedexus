@extends('layouts.app')
@section('title', 'Driver Management')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Driver Management">
        <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white/60 p-16 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-300"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
            <h3 class="mt-4 text-lg font-semibold text-navy-900">Driver Management</h3>
            <p class="mt-2 max-w-md text-sm text-slate-500">Driver assignment, tracking, and performance monitoring tools coming soon.</p>
        </div>
    </x-dashboard-shell>
@endsection