@extends('layouts.admin')
@section('title', 'Bank Accounts - Admin')
@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    @if(session('success'))
        <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Bank Accounts</h1>
            <p class="mt-1 text-sm text-slate-500">Manage bank accounts for manual transfers.</p>
        </div>
        <button onclick="document.getElementById('addBankModal').classList.remove('hidden')" class="rounded-lg bg-[#0d5368] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#0b4658]">
            + Add Bank Account
        </button>
    </div>

    {{-- Add Bank Modal --}}
    <div id="addBankModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-900">Add Bank Account</h2>
                <button onclick="document.getElementById('addBankModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.bank-accounts') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="action" value="create">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Bank Name *</label>
                        <input type="text" name="bank_name" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Account Name *</label>
                        <input type="text" name="account_name" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Account Number *</label>
                        <input type="text" name="account_number" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">SWIFT/BIC Code</label>
                        <input type="text" name="swift_bic" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">IBAN</label>
                        <input type="text" name="iban" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Routing Number</label>
                        <input type="text" name="routing_number" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Branch Name</label>
                        <input type="text" name="branch_name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Country</label>
                        <select name="country" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
                            <option value="">Select Country</option>
                            @foreach(['US'=>'United States','GB'=>'United Kingdom','CA'=>'Canada','NG'=>'Nigeria','DE'=>'Germany','AU'=>'Australia','FR'=>'France','IT'=>'Italy','ES'=>'Spain','NL'=>'Netherlands','CH'=>'Switzerland','AE'=>'UAE','ZA'=>'South Africa','GH'=>'Ghana','KE'=>'Kenya'] as $code => $name)
                                <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Currency *</label>
                        <select name="supported_currency" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
                            @foreach(['USD','EUR','GBP','NGN','GHS','KES','ZAR','CAD','AUD','CHF','AED'] as $cur)
                                <option value="{{ $cur }}">{{ $cur }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Branch Address</label>
                    <input type="text" name="branch_address" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Payment Instructions</label>
                    <textarea name="payment_instructions" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]" placeholder="Please include your payment reference in the transfer description."></textarea>
                </div>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-slate-300 text-[#0d5368] focus:ring-[#0d5368]">
                        <span class="text-sm text-slate-700">Active</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_default" value="1" class="h-4 w-4 rounded border-slate-300 text-[#0d5368] focus:ring-[#0d5368]">
                        <span class="text-sm text-slate-700">Default</span>
                    </label>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" onclick="document.getElementById('addBankModal').classList.add('hidden')" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-[#0d5368] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#0b4658]">Add Bank Account</button>
                </div>
            </form>
        </div>
        </div>
    </div>

    {{-- Bank Accounts Table --}}
    <div class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        @if($bankAccounts->isEmpty())
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4z"/></svg>
                <p class="mt-3 text-sm text-slate-500">No bank accounts configured yet.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Bank Name</th>
                            <th class="px-4 py-3">Account Name</th>
                            <th class="px-4 py-3">Account Number</th>
                            <th class="px-4 py-3">SWIFT</th>
                            <th class="px-4 py-3">Currency</th>
                            <th class="px-4 py-3">Country</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Default</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($bankAccounts as $bank)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-bold text-slate-900">{{ $bank->bank_name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $bank->account_name }}</td>
                                <td class="px-4 py-3 font-mono text-slate-700">{{ $bank->account_number }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $bank->swift_bic ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $bank->supported_currency }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ $bank->country ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @if($bank->is_active)
                                        <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">Active</span>
                                    @else
                                        <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($bank->is_default)
                                        <span class="rounded bg-[#0d5368]/10 px-2 py-0.5 text-xs font-bold text-[#0d5368]">Default</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.bank-accounts.edit', $bank) }}" class="rounded border border-slate-200 px-3 py-1 text-xs font-bold text-slate-600 hover:bg-slate-50">Edit</a>
                                        <form method="POST" action="{{ route('admin.bank-accounts.toggle', $bank) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded border border-slate-200 px-3 py-1 text-xs font-bold {{ $bank->is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }}">
                                                {{ $bank->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                        @if(!$bank->is_default)
                                            <form method="POST" action="{{ route('admin.bank-accounts.default', $bank) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="rounded border border-slate-200 px-3 py-1 text-xs font-bold text-[#0d5368] hover:bg-[#0d5368]/5">Set Default</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.bank-accounts.destroy', $bank) }}" class="inline" onsubmit="return confirm('Delete this bank account?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded border border-red-200 px-3 py-1 text-xs font-bold text-red-600 hover:bg-red-50">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
