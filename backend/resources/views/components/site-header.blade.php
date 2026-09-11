@php
    $companyName = \App\Support\AppSettings::companyName();
    $logo = \App\Support\AppSettings::logo();
@endphp
<header
    x-data="{
        mobile: false,
        search: false,
        searchQuery: '',
        doSearch() {
            if (this.searchQuery.trim()) {
                window.location.href = '/tracking/' + encodeURIComponent(this.searchQuery.trim());
            }
        }
    }"
    @keydown.escape.window="mobile = false; search = false"
    class="sticky top-0 z-50 bg-navy-900 shadow-lift"
>
    <div class="container-page flex h-[76px] items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="{{ $companyName }} home">
            @if($logo)
                <img src="{{ $logo }}" alt="{{ $companyName }}" class="h-10 w-10 rounded-xl object-contain bg-white">
            @else
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-azure-gradient shadow-glow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 18H2a4 4 0 0 0 4 4h12a4 4 0 0 0 4-4Z"/><path d="M6 14V2h12l4 6-4 6H6Z"/><path d="M6 2h12"/><path d="M18 22V14"/></svg>
                </span>
            @endif
            <span class="font-display text-xl font-bold tracking-tight text-white">{{ $companyName }}</span>
        </a>

        <nav class="hidden h-full items-center lg:flex">
            <a href="{{ route('home') }}" class="focus-ring inline-flex h-full items-center px-3 text-[15px] font-medium text-white/90 hover:text-white">Home</a>
            <a href="{{ route('tracking') }}" class="focus-ring inline-flex h-full items-center px-3 text-[15px] font-medium text-white/90 hover:text-white">Track</a>
            <a href="{{ route('services') }}" class="focus-ring inline-flex h-full items-center px-3 text-[15px] font-medium text-white/90 hover:text-white">Services</a>
            <a href="{{ route('about') }}" class="focus-ring inline-flex h-full items-center px-3 text-[15px] font-medium text-white/90 hover:text-white">About</a>
            <a href="{{ route('support') }}" class="focus-ring inline-flex h-full items-center px-3 text-[15px] font-medium text-white/90 hover:text-white">Support</a>
            <a href="{{ route('contact') }}" class="focus-ring inline-flex h-full items-center px-3 text-[15px] font-medium text-white/90 hover:text-white">Contact</a>
        </nav>

        <div class="hidden items-center gap-2 md:flex">
            <button @click="search = true" aria-label="Search" class="focus-ring grid h-10 w-10 place-items-center rounded-full text-white/90 hover:bg-white/10">
                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
            <a href="{{ route('tracking') }}" class="focus-ring inline-flex items-center gap-1.5 rounded-full bg-azure-500 px-4 py-2 text-sm font-semibold text-white hover:bg-azure-600">
                Track a Shipment
            </a>
            @auth
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="focus-ring rounded-full border border-white/30 px-4 py-2 text-sm font-medium text-white hover:bg-white/10">Logout</button>
                </form>
            @endauth
        </div>

        <button @click="mobile = !mobile" class="focus-ring grid h-10 w-10 place-items-center rounded-full text-white lg:hidden" :aria-label="mobile ? 'Close menu' : 'Open menu'">
            <svg x-show="!mobile" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
            <svg x-show="mobile" x-cloak xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
        </button>
    </div>

    <div x-cloak x-show="mobile" class="border-t border-white/10 bg-navy-900 lg:hidden">
        <div class="container-page grid gap-1 py-5">
            <a href="{{ route('home') }}" class="rounded-lg px-2 py-2 text-sm font-medium text-white/90 hover:bg-white/10">Home</a>
            <a href="{{ route('tracking') }}" class="rounded-lg px-2 py-2 text-sm font-medium text-white/90 hover:bg-white/10">Track</a>
            <a href="{{ route('services') }}" class="rounded-lg px-2 py-2 text-sm font-medium text-white/90 hover:bg-white/10">Services</a>
            <a href="{{ route('about') }}" class="rounded-lg px-2 py-2 text-sm font-medium text-white/90 hover:bg-white/10">About</a>
            <a href="{{ route('support') }}" class="rounded-lg px-2 py-2 text-sm font-medium text-white/90 hover:bg-white/10">Support</a>
            <a href="{{ route('contact') }}" class="rounded-lg px-2 py-2 text-sm font-medium text-white/90 hover:bg-white/10">Contact</a>
        </div>
    </div>

    <template x-teleport="body">
        <template x-if="search">
            <div class="fixed inset-0 z-[60] flex items-start justify-center bg-navy-950/60 p-4 pt-28 backdrop-blur-sm" @click="search = false">
                <div class="w-full max-w-xl rounded-2xl bg-white p-3 shadow-lift" @click.stop>
                    <form @submit.prevent="doSearch()">
                        <div class="flex items-center gap-2">
                            <input x-ref="searchInput" x-model="searchQuery" type="text" placeholder="Track a shipment" class="min-h-12 w-full bg-transparent px-3 text-base text-navy-900 outline-none placeholder:text-slate-400">
                            <button type="submit" class="focus-ring rounded-full bg-azure-500 px-4 py-2 text-sm font-semibold text-white hover:bg-azure-600">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </template>
</header>
