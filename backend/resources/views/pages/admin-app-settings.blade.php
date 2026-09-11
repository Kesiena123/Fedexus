@extends('layouts.app')
@section('title', 'App Settings')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="App Settings">
        @if(session('success'))
            <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.app-settings') }}" enctype="multipart/form-data" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div class="grid gap-6 lg:grid-cols-2">
                @foreach($definitions as $key => $definition)
                    @php($setting = $settings[$key] ?? null)
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        {{ $definition['label'] }}
                        @if($definition['type'] === 'image')
                            @if($setting?->value)
                                <span class="text-xs font-normal text-slate-500">Current: {{ $setting->value }}</span>
                            @endif
                            <input type="file" name="{{ $key }}_file" accept="image/*" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 py-2 font-normal">
                            <input type="hidden" name="settings[{{ $key }}]" value="{{ old("settings.$key", $setting?->value) }}">
                        @elseif($definition['type'] === 'text')
                            <textarea name="settings[{{ $key }}]" rows="3" class="focus-ring rounded-md border border-slate-200 px-3 py-3 font-normal">{{ old("settings.$key", $setting?->value) }}</textarea>
                        @elseif($definition['type'] === 'boolean')
                            <select name="settings[{{ $key }}]" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 font-normal">
                                <option value="1" @selected(old("settings.$key", $setting?->value) === '1')>Enabled</option>
                                <option value="0" @selected(old("settings.$key", $setting?->value) === '0')>Disabled</option>
                            </select>
                        @else
                            <input name="settings[{{ $key }}]" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 font-normal" value="{{ old("settings.$key", $setting?->value) }}">
                        @endif
                    </label>
                @endforeach
            </div>
            <div class="mt-6 flex justify-end">
                <button class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md bg-[#0d5368] px-5 text-sm font-bold text-white hover:bg-[#0b4658]">Save App Settings</button>
            </div>
        </form>
    </x-dashboard-shell>
@endsection
