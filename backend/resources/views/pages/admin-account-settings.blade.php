@extends('layouts.app')
@section('title', 'My Account')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="My Account">

        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700">
                <div class="mb-1 font-semibold">Please fix the following errors:</div>
                <ul class="list-inside list-disc space-y-0.5 text-red-600">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-3">

            {{-- Profile Card --}}
            <div class="xl:col-span-1">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col items-center text-center">
                        <div class="grid size-20 place-items-center rounded-full bg-[#0d5368] text-3xl font-bold text-white shadow-md">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-slate-900">{{ $user->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $user->email }}</p>
                        <span class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-[#0d5368]/10 px-3 py-1 text-xs font-semibold text-[#0d5368]">
                            {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                        </span>
                        <div class="mt-4 w-full border-t border-slate-100 pt-4 text-xs text-slate-400">
                            <div class="flex justify-between"><span>Member since</span><span class="font-medium text-slate-600">{{ $user->created_at->format('M d, Y') }}</span></div>
                            <div class="mt-1 flex justify-between"><span>Last login</span><span class="font-medium text-slate-600">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'N/A' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6 xl:col-span-2">

                {{-- Account Information --}}
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="grid size-10 place-items-center rounded-lg bg-slate-100 text-[#0d5368]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Account Information</h3>
                            <p class="text-xs text-slate-500">Update your name and login email address</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.account-settings') }}">
                        @csrf
                        <input type="hidden" name="action" value="update_profile">

                        <div class="grid gap-5 sm:grid-cols-2">
                            <label class="grid gap-1.5">
                                <span class="text-sm font-semibold text-slate-700">Full Name</span>
                                <input
                                    type="text"
                                    name="name"
                                    value="{{ old('name', $user->name) }}"
                                    required
                                    class="focus-ring min-h-11 rounded-lg border border-slate-200 px-4 text-sm font-normal text-slate-800 transition-colors hover:border-slate-300 focus:border-[#0d5368]"
                                >
                                @error('name') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            </label>

                            <label class="grid gap-1.5">
                                <span class="text-sm font-semibold text-slate-700">Login Email</span>
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email', $user->email) }}"
                                    required
                                    class="focus-ring min-h-11 rounded-lg border border-slate-200 px-4 text-sm font-normal text-slate-800 transition-colors hover:border-slate-300 focus:border-[#0d5368]"
                                >
                                @error('email') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            </label>
                        </div>

                        <div class="mt-5 flex items-center gap-3 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>If you change your email, you will need to use the new email address for future logins.</span>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="focus-ring inline-flex min-h-11 items-center justify-center rounded-lg bg-[#0d5368] px-6 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#0b4658]">Save Account Information</button>
                        </div>
                    </form>
                </div>

                {{-- Security / Password --}}
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="grid size-10 place-items-center rounded-lg bg-slate-100 text-[#0d5368]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Change Password</h3>
                            <p class="text-xs text-slate-500">Ensure your account remains secure with a strong password</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.account-settings') }}" x-data="{ showCurrent: false, showNew: false, showConfirm: false, pw: '' }">
                        @csrf
                        <input type="hidden" name="action" value="update_password">

                        <div class="grid gap-5 sm:grid-cols-2">
                            {{-- Current Password (full width) --}}
                            <label class="grid gap-1.5 sm:col-span-2">
                                <span class="text-sm font-semibold text-slate-700">Current Password</span>
                                <div class="relative">
                                    <input
                                        :type="showCurrent ? 'text' : 'password'"
                                        name="current_password"
                                        required
                                        autocomplete="current-password"
                                        class="focus-ring min-h-11 w-full rounded-lg border border-slate-200 px-4 pr-12 text-sm font-normal text-slate-800 transition-colors hover:border-slate-300 focus:border-[#0d5368]"
                                    >
                                    <button type="button" @@click="showCurrent = !showCurrent" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-400 transition-colors hover:text-slate-600">
                                        <svg x-show="!showCurrent" xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg x-show="showCurrent" x-cloak xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    </button>
                                </div>
                                @error('current_password') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            </label>

                            {{-- New Password --}}
                            <label class="grid gap-1.5" x-data="{ strength: 0, label: '', color: '' }" x-init="$watch('pw', v => {
                                let s = 0;
                                if (v.length >= 8) s++;
                                if (v.length >= 12) s++;
                                if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
                                if (/[0-9]/.test(v)) s++;
                                if (/[^A-Za-z0-9]/.test(v)) s++;
                                strength = s;
                                label = s <= 2 ? 'Weak' : s <= 3 ? 'Fair' : s <= 4 ? 'Strong' : 'Very Strong';
                                color = s <= 2 ? 'bg-red-500' : s <= 3 ? 'bg-amber-500' : s <= 4 ? 'bg-emerald-500' : 'bg-emerald-600';
                            })">
                                <span class="text-sm font-semibold text-slate-700">New Password</span>
                                <div class="relative">
                                    <input
                                        :type="showNew ? 'text' : 'password'"
                                        name="password"
                                        x-model="pw"
                                        required
                                        autocomplete="new-password"
                                        minlength="8"
                                        class="focus-ring min-h-11 w-full rounded-lg border border-slate-200 px-4 pr-12 text-sm font-normal text-slate-800 transition-colors hover:border-slate-300 focus:border-[#0d5368]"
                                    >
                                    <button type="button" @@click="showNew = !showNew" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-400 transition-colors hover:text-slate-600">
                                        <svg x-show="!showNew" xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg x-show="showNew" x-cloak xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    </button>
                                </div>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                        <div :class="color" class="h-full rounded-full transition-all duration-300" :style="'width:' + (strength * 20) + '%'"></div>
                                    </div>
                                    <span x-text="pw.length > 0 ? label : ''" class="w-20 text-right text-xs font-medium" :class="strength <= 2 ? 'text-red-500' : strength <= 3 ? 'text-amber-600' : 'text-emerald-600'"></span>
                                </div>
                                <p class="text-xs text-slate-400">Minimum 8 characters. Use uppercase, lowercase, numbers, and symbols for a stronger password.</p>
                                @error('password') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            </label>

                            {{-- Confirm Password --}}
                            <label class="grid gap-1.5">
                                <span class="text-sm font-semibold text-slate-700">Confirm New Password</span>
                                <div class="relative">
                                    <input
                                        :type="showConfirm ? 'text' : 'password'"
                                        name="password_confirmation"
                                        required
                                        autocomplete="new-password"
                                        class="focus-ring min-h-11 w-full rounded-lg border border-slate-200 px-4 pr-12 text-sm font-normal text-slate-800 transition-colors hover:border-slate-300 focus:border-[#0d5368]"
                                    >
                                    <button type="button" @@click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-400 transition-colors hover:text-slate-600">
                                        <svg x-show="!showConfirm" xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg x-show="showConfirm" x-cloak xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    </button>
                                </div>
                                @error('password_confirmation') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            </label>
                        </div>

                        <div class="mt-5 flex items-center gap-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <span>Changing your password will not log you out of this session, but any other active sessions may need to re-authenticate.</span>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="focus-ring inline-flex min-h-11 items-center justify-center rounded-lg bg-[#0d5368] px-6 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#0b4658]">Update Password</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </x-dashboard-shell>
@endsection
