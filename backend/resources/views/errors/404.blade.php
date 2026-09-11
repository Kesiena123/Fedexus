@extends('layouts.app')
@section('title', 'Page Not Found')
@section('content')
<main class="mx-auto grid min-h-[60vh] max-w-3xl place-items-center px-4 py-16 text-center">
    <div>
        <p class="text-sm font-bold uppercase tracking-wider text-azure-600">404</p>
        <h1 class="mt-3 font-display text-4xl font-bold text-navy-900">Page not found</h1>
        <p class="mt-4 text-slate-600">The page you are looking for is unavailable or has moved.</p>
        <a href="{{ route('home') }}" class="mt-8 inline-block">
            <x-ui.button>Return home</x-ui.button>
        </a>
    </div>
</main>
@endsection
