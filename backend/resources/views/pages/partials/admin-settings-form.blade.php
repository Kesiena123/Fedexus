@if(session('success'))
    <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ url()->current() }}" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    <div class="grid gap-5 md:grid-cols-2">
        @foreach($definitions as $key => $definition)
            @php($setting = $settings[$key] ?? null)
            <label class="{{ $definition['type'] === 'text' ? 'md:col-span-2' : '' }}">
                <span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ $definition['label'] }}</span>
                @if($definition['type'] === 'boolean')
                    <select name="settings[{{ $key }}]" class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm">
                        <option value="1" @selected(($setting?->value ?? $definition['default']) === '1')>Enabled</option>
                        <option value="0" @selected(($setting?->value ?? $definition['default']) === '0')>Disabled</option>
                    </select>
                @elseif($definition['type'] === 'text')
                    <textarea name="settings[{{ $key }}]" rows="4" class="focus-ring w-full rounded-md border border-slate-200 px-3 py-2 text-sm">{{ old("settings.$key", $setting?->value ?? $definition['default']) }}</textarea>
                @else
                    <input
                        name="settings[{{ $key }}]"
                        type="{{ $definition['type'] === 'password' ? 'password' : 'text' }}"
                        value="{{ old("settings.$key", $definition['type'] === 'password' ? '' : ($setting?->value ?? $definition['default'])) }}"
                        class="focus-ring min-h-11 w-full rounded-md border border-slate-200 px-3 text-sm"
                    >
                @endif
            </label>
        @endforeach
    </div>

    @if($errors->any())
        <div class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="mt-6">
        <button class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md bg-[#0d5368] px-5 text-sm font-bold text-white hover:bg-[#0b4658]">Save Settings</button>
    </div>
</form>
