@extends('layouts.app')
@section('title', 'Reports & Analytics')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Reports & Analytics">
        <div class="mb-4 flex justify-end">
            <a href="{{ route('admin.reports.export') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#0d5368] px-4 py-2 text-sm font-bold text-white hover:bg-[#0b4658]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
        </div>
        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Payment Summary --}}
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900">Payment Summary</h3>
                <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Total Requests</p>
                        <p class="mt-1 text-xl font-bold text-navy-900">{{ $paymentStats['total_payment_requests'] }}</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-3">
                        <p class="text-xs text-emerald-600">Verified</p>
                        <p class="mt-1 text-xl font-bold text-emerald-700">{{ $paymentStats['verified_payments'] }}</p>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-3">
                        <p class="text-xs text-amber-600">Pending</p>
                        <p class="mt-1 text-xl font-bold text-amber-700">{{ $paymentStats['pending_payments'] }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Verified Amount</p>
                        <p class="mt-1 text-xl font-bold text-navy-900">{{ number_format((float)$paymentStats['total_amount_verified'], 2) }}</p>
                    </div>
                </div>
            </div>

            {{-- Bank Transfer Stats --}}
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900">Bank Transfer Payments</h3>
                <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Total Proofs</p>
                        <p class="mt-1 text-xl font-bold text-navy-900">{{ $bankTransferStats['total_proofs'] }}</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-3">
                        <p class="text-xs text-emerald-600">Verified</p>
                        <p class="mt-1 text-xl font-bold text-emerald-700">{{ $bankTransferStats['verified_proofs'] }}</p>
                    </div>
                    <div class="rounded-lg bg-red-50 p-3">
                        <p class="text-xs text-red-600">Rejected</p>
                        <p class="mt-1 text-xl font-bold text-red-700">{{ $bankTransferStats['rejected_proofs'] }}</p>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-3">
                        <p class="text-xs text-amber-600">Pending Review</p>
                        <p class="mt-1 text-xl font-bold text-amber-700">{{ $bankTransferStats['pending_proofs'] }}</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-3 col-span-2">
                        <p class="text-xs text-emerald-600">Verified Amount (Bank Transfer)</p>
                        <p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format((float)$bankTransferStats['total_verified_amount'], 2) }}</p>
                    </div>
                </div>
            </div>

            {{-- Gateway Breakdown Table --}}
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                <h3 class="text-sm font-bold text-slate-900">Payment Gateway Performance</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase text-slate-500">
                            <tr>
                                <th class="border-b p-3">Gateway</th>
                                <th class="border-b p-3">Status</th>
                                <th class="border-b p-3">Total Transactions</th>
                                <th class="border-b p-3">Verified</th>
                                <th class="border-b p-3">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gatewayBreakdown as $gw)
                                <tr class="border-b border-slate-100">
                                    <td class="p-3 font-semibold text-navy-900">{{ $gw['label'] }}</td>
                                    <td class="p-3">
                                        @if($gw['is_active'])
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">Active</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-500">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="p-3">{{ $gw['total_transactions'] }}</td>
                                    <td class="p-3">{{ $gw['verified_transactions'] }}</td>
                                    <td class="p-3 font-mono text-xs">{{ number_format((float)$gw['total_amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="p-6 text-center text-slate-400">No gateways configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-dashboard-shell>
@endsection