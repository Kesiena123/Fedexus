@props(['title' => 'Dashboard', 'audience' => null])
@php
    $companyName = \App\Models\AdminSetting::where('key', 'company_name')->value('value') ?: 'Remedy Logistics';
    $navItems = [
        [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'active' => request()->routeIs('admin.dashboard'),
            'icon' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h5v-6h4v6h5V9.5"/>',
        ],
        [
            'label' => 'Manage Shipments',
            'route' => 'admin.shipments',
            'active' => request()->routeIs('admin.shipments') || request()->routeIs('admin.shipments.show'),
            'icon' => '<path d="M10 17h4V5H2v12h3"/><path d="M14 8h4l4 5v4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
        ],
        [
            'label' => 'Create New Shipment',
            'route' => 'admin.shipments.create',
            'active' => request()->routeIs('admin.shipments.create'),
            'icon' => '<path d="M4 7h16l-2 14H6L4 7Z"/><path d="M8 7a4 4 0 0 1 8 0"/><path d="M12 11v6"/><path d="M9 14h6"/>',
        ],
        [
            'label' => 'Shipment Deposits',
            'route' => 'admin.payments',
            'active' => request()->routeIs('admin.payments'),
            'icon' => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M7 9v.01"/><path d="M17 15v.01"/>',
        ],
        [
            'label' => 'Email Services',
            'route' => 'admin.email-services',
            'active' => request()->routeIs('admin.email-services'),
            'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        ],
        [
            'label' => 'Live Chat',
            'route' => 'admin.live-chat',
            'active' => request()->routeIs('admin.live-chat'),
            'icon' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M8 9h8"/><path d="M8 13h5"/>',
        ],
        [
            'label' => 'Administrator(s)',
            'route' => 'admin.administrators',
            'active' => request()->routeIs('admin.administrators'),
            'icon' => '<path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="7" r="4"/>',
        ],
    ];

    $settingsChildren = [
        ['label' => 'My Account', 'route' => 'admin.account-settings', 'active' => request()->routeIs('admin.account-settings')],
        ['label' => 'App Settings', 'route' => 'admin.app-settings', 'active' => request()->routeIs('admin.app-settings')],
        ['label' => 'Payment Settings', 'route' => 'admin.payment-settings', 'active' => request()->routeIs('admin.payment-settings')],
        ['label' => 'Email Settings', 'route' => 'admin.email-settings', 'active' => request()->routeIs('admin.email-settings')],
        ['label' => 'Live Chat Settings', 'route' => 'admin.live-chat-settings', 'active' => request()->routeIs('admin.live-chat-settings')],
        ['label' => 'Map Settings', 'route' => 'admin.map-settings', 'active' => request()->routeIs('admin.map-settings')],
        ['label' => 'Notification Settings', 'route' => 'admin.notification-settings', 'active' => request()->routeIs('admin.notification-settings')],
    ];

    $settingsActive = collect($settingsChildren)->contains(fn ($item) => $item['active']);
@endphp

<div
    x-data="{
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('freightflow_admin_sidebar_collapsed') === 'true',
        settingsOpen: localStorage.getItem('freightflow_admin_settings_open') === null ? true : localStorage.getItem('freightflow_admin_settings_open') === 'true',
        setCollapsed(value) {
            this.sidebarCollapsed = value;
            localStorage.setItem('freightflow_admin_sidebar_collapsed', value ? 'true' : 'false');
        },
        setSettingsOpen(value) {
            this.settingsOpen = value;
            localStorage.setItem('freightflow_admin_settings_open', value ? 'true' : 'false');
        }
    }"
    class="relative min-h-screen bg-[#f0f4f8] text-slate-800"
>
    <template x-teleport="body">
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-slate-950/40 backdrop-blur-sm lg:hidden" @@click="sidebarOpen = false"></div>
    </template>

    <aside
        x-cloak
        :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'lg:w-[92px]' : 'lg:w-[292px]']"
        class="admin-scrollbar fixed inset-y-0 left-0 z-40 flex w-[292px] max-w-[88vw] flex-col overflow-y-auto bg-white text-slate-600 shadow-2xl transition-all duration-300 lg:translate-x-0"
    >
        <div class="flex h-[72px] items-center justify-between bg-navy-900 px-5 text-white">
            <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3" x-show="!sidebarCollapsed" x-transition.opacity>
                <span class="grid size-10 shrink-0 place-items-center rounded-md bg-azure-500 font-bold text-white">{{ strtoupper(substr($companyName, 0, 1)) }}</span>
                <span class="truncate text-xl font-semibold tracking-tight">{{ $companyName }}</span>
            </a>
            <button
                type="button"
                @@click="window.innerWidth >= 1024 ? setCollapsed(!sidebarCollapsed) : sidebarOpen = false"
                class="grid size-11 place-items-center rounded-md text-white transition-colors hover:bg-white/10"
                aria-label="Toggle navigation"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 space-y-2 px-4 py-6">
            @foreach($navItems as $item)
                <a
                    href="{{ route($item['route']) }}"
                    class="group flex h-12 items-center gap-4 rounded-lg px-4 text-[15px] font-medium transition-all duration-200 {{ $item['active'] ? 'bg-azure-500 text-white shadow-md shadow-azure-500/20' : 'text-slate-500 hover:bg-slate-100 hover:text-navy-900' }}"
                    :class="sidebarCollapsed ? 'justify-center px-0' : ''"
                    title="{{ $item['label'] }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6 shrink-0 {{ $item['active'] ? 'text-white' : 'text-slate-400 group-hover:text-azure-500' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                        {!! $item['icon'] !!}
                    </svg>
                    <span x-show="!sidebarCollapsed" x-transition.opacity>{{ $item['label'] }}</span>
                </a>
            @endforeach

            <div>
                <button
                    type="button"
                    @@click="setSettingsOpen(!settingsOpen)"
                    class="group flex h-12 w-full items-center gap-4 rounded-lg px-4 text-left text-[15px] font-medium transition-all duration-200 {{ $settingsActive ? 'bg-slate-100 text-azure-600' : 'text-slate-500 hover:bg-slate-100 hover:text-azure-600' }}"
                    :class="sidebarCollapsed ? 'justify-center px-0' : ''"
                    title="Settings"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6 shrink-0 {{ $settingsActive ? 'text-azure-500' : 'text-slate-400 group-hover:text-azure-500' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09A1.65 1.65 0 0 0 15 4.6a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.14.31.46 1 .99 1H21a2 2 0 0 1 0 4h-.61c-.53 0-.85.69-.99 1Z"/>
                    </svg>
                    <span class="flex-1" x-show="!sidebarCollapsed" x-transition.opacity>Settings</span>
                    <svg x-show="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 transition-transform" :class="settingsOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>

                <div x-show="settingsOpen && !sidebarCollapsed" x-cloak class="mt-2 space-y-1">
                    @foreach($settingsChildren as $item)
                        <a href="{{ route($item['route']) }}" class="block rounded-md py-2.5 pl-14 pr-3 text-sm transition-colors {{ $item['active'] ? 'bg-slate-100 font-semibold text-azure-600' : 'text-slate-500 hover:bg-slate-100 hover:text-azure-600' }}">
                            <span class="mr-3">-</span>{{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>
    </aside>

    <div :class="sidebarCollapsed ? 'lg:pl-[92px]' : 'lg:pl-[292px]'" class="min-h-screen transition-all duration-300">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur-xl">
            <div class="flex flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 xl:px-8">
                <div class="flex items-center gap-3">
                    <button @@click="sidebarOpen = true" class="grid size-11 place-items-center rounded-lg border border-slate-200 bg-white text-azure-500 shadow-sm lg:hidden" aria-label="Open navigation">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>
                        </svg>
                    </button>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-azure-500">Admin dashboard</p>
                        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $title }}</h2>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.shipments.create') }}" class="focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-azure-500 px-4 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-azure-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14"/><path d="M5 12h14"/>
                        </svg>
                        New shipment
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button type="submit" class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600">Sign out</button>
                    </form>
                </div>
            </div>
        </header>

        <section class="px-4 py-6 sm:px-6 xl:px-8">
            <div class="mx-auto w-full max-w-[1600px]">
                {{ $slot }}
            </div>
        </section>
    </div>
</div>
