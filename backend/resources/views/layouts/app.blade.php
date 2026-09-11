@php
    $siteName = \App\Support\AppSettings::companyName();
    $defaultTitle = $siteName.' - Global Logistics & Courier Platform';
    $favicon = \App\Support\AppSettings::favicon();
@endphp
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $defaultTitle) | {{ $siteName }}</title>
    <meta name="description" content="@yield('description', 'Enterprise logistics, courier, shipment tracking, and delivery management platform.')">
    <meta property="og:title" content="@yield('og_title', $defaultTitle)">
    <meta property="og:description" content="@yield('og_description', 'Ship, manage, track, and deliver across the globe with real-time visibility and milestone-based payments.')">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta name="twitter:card" content="summary_large_image">
    @if($favicon)
        <link rel="icon" href="{{ $favicon }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-slate-50 font-sans text-navy-900 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-azure-500 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">Skip to content</a>
    @if(!isset($hideHeaderFooter) || !$hideHeaderFooter)
        <x-site-header />
    @endif
    <main id="main">
        @yield('content')
    </main>
    @if(!isset($hideHeaderFooter) || !$hideHeaderFooter)
        <x-site-footer />
    @endif
</body>
</html>
