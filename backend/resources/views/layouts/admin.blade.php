<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - {{ \App\Support\AppSettings::companyName() }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
    <div x-data="{ sidebarOpen: false, settingsOpen: false }" class="flex h-screen overflow-hidden">
        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-black/50 lg:hidden"></div>

        {{-- SIDEBAR --}}
        <aside x-show="sidebarOpen" x-cloak
            class="fixed inset-y-0 left-0 z-30 w-64 shrink-0 overflow-y-auto bg-navy-950 text-white transition-all lg:static lg:block lg:z-auto"
            :class="sidebarOpen ? 'block' : 'hidden'">

            <div class="flex h-16 items-center justify-between border-b border-white/10 px-6">
                <a href="{{ route('admin.dashboard') }}" class="font-display text-xl font-bold tracking-tight">
                    {{ \App\Support\AppSettings::companyName() }}
                </a>
                <button @click="sidebarOpen = false" class="text-white/60 hover:text-white lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <nav class="mt-4 space-y-1 px-3 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('admin.shipments') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium transition-colors {{ request()->routeIs('admin.shipments') && !request()->routeIs('admin.shipments.create') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16.5 9.4 7.5 4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    Manage Shipments
                </a>
                <a href="{{ route('admin.shipments.create') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium transition-colors {{ request()->routeIs('admin.shipments.create') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create Shipment
                </a>
                <a href="{{ route('admin.payments') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium transition-colors {{ request()->routeIs('admin.payments') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    Payments
                </a>
                <a href="{{ route('admin.payment-requests.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium transition-colors {{ request()->routeIs('admin.payment-requests.*') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Payment Requests
                </a>
                <a href="{{ route('admin.email-services') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium transition-colors {{ request()->routeIs('admin.email-services') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    Email Services
                </a>
                @php $unreadChats = \App\Models\GuestChatConversation::unread()->count(); @endphp
                <a href="{{ route('admin.live-chat') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium transition-colors {{ request()->routeIs('admin.live-chat') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Live Chat
                    @if($unreadChats > 0)
                        <span class="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-xs font-bold text-white">{{ $unreadChats }}</span>
                    @endif
                </a>

                {{-- SETTINGS DROPDOWN --}}
                <div x-data="{ open: {{ request()->routeIs('admin.*.settings*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex w-full items-center justify-between rounded-xl px-4 py-3 font-medium transition-colors {{ request()->routeIs('admin.*.settings*') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                        <span class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                            Settings
                        </span>
                        <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        <svg x-show="open" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                    </button>
                    <div x-show="open" x-transition class="ml-4 mt-1 space-y-1">
                        <a href="{{ route('admin.app-settings') }}" class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm transition-colors {{ request()->routeIs('admin.app-settings') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">App Settings</a>
                        <a href="{{ route('admin.payment-settings') }}" class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm transition-colors {{ request()->routeIs('admin.payment-settings') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Payment Settings</a>
                        <a href="{{ route('admin.email-settings') }}" class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm transition-colors {{ request()->routeIs('admin.email-settings') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Email Settings</a>
                        <a href="{{ route('admin.live-chat-settings') }}" class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm transition-colors {{ request()->routeIs('admin.live-chat-settings') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Live Chat Settings</a>
                        <a href="{{ route('admin.map-settings') }}" class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm transition-colors {{ request()->routeIs('admin.map-settings') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Map Settings</a>
                        <a href="{{ route('admin.notification-settings') }}" class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm transition-colors {{ request()->routeIs('admin.notification-settings') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Notification Settings</a>
                    </div>
                </div>

                <a href="{{ route('admin.audit-logs') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium transition-colors {{ request()->routeIs('admin.audit-logs') ? 'bg-azure-500/20 text-azure-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Audit Logs
                </a>
            </nav>

            <div class="mt-auto border-t border-white/10 p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-slate-400 transition-colors hover:bg-white/5 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <div class="flex flex-1 flex-col overflow-hidden">
            {{-- Top bar --}}
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 lg:px-8">
                <button @click="sidebarOpen = true" class="text-slate-600 lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-500">{{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button type="submit" class="rounded-full border border-slate-200 px-4 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Sign Out</button>
                    </form>
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto p-4 lg:p-8 min-h-0">
                @if(session('success'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
                        {{ session('error') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>
