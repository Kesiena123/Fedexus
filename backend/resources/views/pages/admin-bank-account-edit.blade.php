@extends('layouts.admin')
@section('title', 'Edit Bank Account - Admin')
@section('content')
<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    @if(session('success'))
        <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.bank-accounts') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50">
            <svg class="inline h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Edit Bank Account</h1>
            <p class="mt-1 text-sm text-slate-500">Update details for {{ $bankAccount->bank_name }}.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.bank-accounts.edit', $bankAccount) }}" enctype="multipart/form-data" class="mt-8 space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold text-slate-700">Bank Name *</label>
                <input type="text" name="bank_name" value="{{ old('bank_name', $bankAccount->bank_name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700">Account Name *</label>
                <input type="text" name="account_name" value="{{ old('account_name', $bankAccount->account_name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700">Account Number *</label>
                <input type="text" name="account_number" value="{{ old('account_number', $bankAccount->account_number) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700">SWIFT/BIC Code</label>
                <input type="text" name="swift_bic" value="{{ old('swift_bic', $bankAccount->swift_bic) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700">IBAN</label>
                <input type="text" name="iban" value="{{ old('iban', $bankAccount->iban) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700">Routing Number</label>
                <input type="text" name="routing_number" value="{{ old('routing_number', $bankAccount->routing_number) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700">Branch Name</label>
                <input type="text" name="branch_name" value="{{ old('branch_name', $bankAccount->branch_name) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700">Country</label>
                <select name="country" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
                    <option value="">Select Country</option>
                    @foreach(['US'=>'United States','GB'=>'United Kingdom','CA'=>'Canada','NG'=>'Nigeria','DE'=>'Germany','AU'=>'Australia','FR'=>'France','IT'=>'Italy','ES'=>'Spain','NL'=>'Netherlands','CH'=>'Switzerland','AE'=>'UAE','ZA'=>'South Africa','GH'=>'Ghana','KE'=>'Kenya'] as $code => $name)
                        <option value="{{ $code }}" {{ old('country', $bankAccount->country) === $code ? 'selected' : '' }}>{{ $name }} ({{ $code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700">Currency *</label>
                <select name="supported_currency" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
                    @foreach(['USD','EUR','GBP','NGN','GHS','KES','ZAR','CAD','AUD','CHF','AED'] as $cur)
                        <option value="{{ $cur }}" {{ old('supported_currency', $bankAccount->supported_currency) === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700">Branch Address</label>
            <input type="text" name="branch_address" value="{{ old('branch_address', $bankAccount->branch_address) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700">Payment Instructions</label>
            <textarea name="payment_instructions" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">{{ old('payment_instructions', $bankAccount->payment_instructions) }}</textarea>
        </div>
        <div class="flex items-center gap-6">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $bankAccount->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-[#0d5368] focus:ring-[#0d5368]">
                <span class="text-sm text-slate-700">Active</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_default" value="1" {{ old('is_default', $bankAccount->is_default) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-[#0d5368] focus:ring-[#0d5368]">
                <span class="text-sm text-slate-700">Default</span>
            </label>
        </div>
        <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
            <a href="{{ route('admin.bank-accounts') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-[#0d5368] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#0b4658]">Save Changes</button>
        </div>
    </form>
</div>
@endsection
