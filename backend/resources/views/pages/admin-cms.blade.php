@extends('layouts.app')
@section('title', 'Website CMS')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Website CMS">
        <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white/60 p-16 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-300"><circle cx="12" cy="12" r="10"/><line x1="2" x2="22" y1="12" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            <h3 class="mt-4 text-lg font-semibold text-navy-900">Website CMS</h3>
            <p class="mt-2 max-w-md text-sm text-slate-500">Public website content management, page builder, and media library coming soon.</p>
        </div>
    </x-dashboard-shell>
@endsection