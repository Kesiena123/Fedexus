@extends('layouts.admin')
@section('title', 'Bank Transfer Reviews - Admin')
@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    @if(session('success'))
        <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <h1 class="text-2xl font-bold text-slate-900">Bank Transfer Reviews</h1>
    <p class="mt-1 text-sm text-slate-500">Review and approve/reject manual bank transfer payments.</p>

    {{-- Stats Bar --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-xs font-semibold uppercase text-amber-600">Pending</p>
            <p class="mt-1 text-3xl font-bold text-amber-700">{{ $stats['pending'] }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-xs font-semibold uppercase text-emerald-600">Verified</p>
            <p class="mt-1 text-3xl font-bold text-emerald-700">{{ $stats['verified'] }}</p>
        </div>
        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="text-xs font-semibold uppercase text-red-600">Rejected</p>
            <p class="mt-1 text-3xl font-bold text-red-700">{{ $stats['rejected'] }}</p>
        </div>
        <div class="rounded-xl border border-[#0d5368]/20 bg-[#0d5368]/5 p-4">
            <p class="text-xs font-semibold uppercase text-[#0d5368]">Total Verified Amount</p>
            <p class="mt-1 text-3xl font-bold text-[#0d5368]">{{ number_format($stats['total_amount'], 2) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mt-6 flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('admin.bank-transfers') }}" class="flex flex-wrap items-center gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tracking #, reference, title..." class="w-72 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Verified</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <button type="submit" class="rounded-lg bg-[#0d5368] px-4 py-2 text-sm font-bold text-white hover:bg-[#0b4658]">Filter</button>
            @if(request('search') || request('status'))
                <a href="{{ route('admin.bank-transfers') }}" class="text-sm font-bold text-slate-500 hover:text-slate-700">Clear</a>
            @endif
        </form>
    </div>

    {{-- Proofs Table --}}
    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        @if($proofs->isEmpty())
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="mt-3 text-sm text-slate-500">No payment proofs found.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Tracking #</th>
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">File</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Uploaded</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($proofs as $proof)
                            <tr class="hover:bg-slate-50" id="proof-{{ $proof->id }}">
                                <td class="px-4 py-3">
                                    <span class="font-mono font-bold text-slate-900">{{ $proof->tracking_number ?: '-' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs text-slate-700">{{ $proof->payment_reference }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($proof->transaction)
                                        <span class="font-bold text-slate-900">{{ number_format($proof->transaction->amount, 2) }} {{ $proof->transaction->currency }}</span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($proof->file_path)
                                        <div class="flex items-center gap-2">
                                            <button type="button" onclick="openPreviewModal('{{ Storage::url('private/' . $proof->file_path) }}')" class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-1 text-xs font-bold text-[#0d5368] hover:bg-slate-200">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Preview
                                            </button>
                                            <a href="{{ Storage::url('private/' . $proof->file_path) }}" download="{{ basename($proof->file_path) }}" class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600 hover:bg-slate-200">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                Download
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-slate-400">No file</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($proof->status === 'pending')
                                        <span class="rounded bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">
                                            @if($proof->resubmission_requested_at)Resubmission Requested @else Pending @endif
                                        </span>
                                    @elseif($proof->status === 'verified')
                                        <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">Verified</span>
                                    @else
                                        <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Rejected</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs text-slate-500">{{ $proof->created_at->format('M d, Y H:i') }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($proof->status === 'pending')
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Notes --}}
                                            <button type="button" onclick="openNotesModal({{ $proof->id }}, {{ json_encode($proof->internal_notes ?? '') }})" class="rounded bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600 hover:bg-slate-200" title="{{ $proof->internal_notes ? 'Has notes' : 'Add notes' }}">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                {{ $proof->internal_notes ? 'Notes' : 'Note' }}
                                            </button>
                                            {{-- Approve --}}
                                            <form method="POST" action="{{ route('admin.bank-transfers.approve', $proof) }}" class="inline">
                                                @csrf
                                                <button type="submit" onclick="return confirm('Approve this payment proof?')" class="rounded bg-emerald-500 px-3 py-1 text-xs font-bold text-white hover:bg-emerald-600">
                                                    Approve
                                                </button>
                                            </form>
                                            {{-- Reject --}}
                                            <button type="button" onclick="openRejectModal({{ $proof->id }})" class="rounded bg-red-500 px-3 py-1 text-xs font-bold text-white hover:bg-red-600">
                                                Reject
                                            </button>
                                        </div>
                                    @elseif($proof->status === 'verified')
                                        <span class="text-xs text-emerald-600">Verified {{ $proof->verified_at ? $proof->verified_at->format('M d') : '' }}</span>
                                    @else
                                        <div class="flex flex-col items-end gap-1">
                                            <span class="text-xs text-red-600" title="{{ $proof->rejection_reason }}">Rejected {{ $proof->verified_at ? $proof->verified_at->format('M d') : '' }}</span>
                                            <button type="button" onclick="openResubmitModal({{ $proof->id }})" class="rounded bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700 hover:bg-amber-200">
                                                Request Resubmission
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $proofs->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Image Preview Modal --}}
<div id="previewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80" onclick="this.classList.add('hidden')">
    <div class="relative mx-4 max-h-[90vh] max-w-[90vw]">
        <button type="button" onclick="document.getElementById('previewModal').classList.add('hidden')" class="absolute -right-3 -top-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white shadow-lg hover:bg-slate-100">
            <svg class="h-4 w-4 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <img id="previewImage" src="" alt="Payment Proof" class="max-h-[85vh] max-w-full rounded-xl shadow-2xl" style="object-fit:contain;">
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="mx-4 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-bold text-slate-900">Reject Payment Proof</h2>
        <p class="mt-1 text-sm text-slate-500">Provide a reason for rejection. The guest will see this reason.</p>
        <form method="POST" id="rejectForm" class="mt-4 space-y-3">
            @csrf
            <input type="hidden" name="action" value="reject">
            <textarea name="rejection_reason" rows="3" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="e.g. Proof shows different amount or name..."></textarea>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-bold text-white hover:bg-red-600">Reject Payment</button>
            </div>
        </form>
    </div>
</div>

{{-- Notes Modal --}}
<div id="notesModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="mx-4 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-bold text-slate-900">Internal Notes</h2>
        <p class="mt-1 text-sm text-slate-500">Add internal notes about this payment proof (not visible to guest).</p>
        <form method="POST" id="notesForm" class="mt-4 space-y-3">
            @csrf
            <textarea name="internal_notes" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0d5368] focus:ring-1 focus:ring-[#0d5368]" placeholder="e.g. Contacted bank to verify transfer..."></textarea>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('notesModal').classList.add('hidden')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-[#0d5368] px-4 py-2 text-sm font-bold text-white hover:bg-[#0b4658]">Save Notes</button>
            </div>
        </form>
    </div>
</div>

{{-- Request Resubmission Modal --}}
<div id="resubmitModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="mx-4 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-bold text-slate-900">Request Resubmission</h2>
        <p class="mt-1 text-sm text-slate-500">Ask the guest to upload a corrected payment proof.</p>
        <form method="POST" id="resubmitForm" class="mt-4 space-y-3">
            @csrf
            <input type="hidden" name="action" value="request_resubmission">
            <textarea name="resubmission_reason" rows="3" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500" placeholder="e.g. The proof image is blurry, please upload a clearer one..."></textarea>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('resubmitModal').classList.add('hidden')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-bold text-white hover:bg-amber-600">Request Resubmission</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(proofId) {
    var form = document.getElementById('rejectForm');
    form.action = '/admin/bank-transfers/' + proofId + '/reject';
    document.getElementById('rejectModal').classList.remove('hidden');
}

function openPreviewModal(url) {
    document.getElementById('previewImage').src = url;
    document.getElementById('previewModal').classList.remove('hidden');
}

function openNotesModal(proofId, notes) {
    var form = document.getElementById('notesForm');
    form.action = '/admin/bank-transfers/' + proofId + '/notes';
    form.querySelector('textarea[name="internal_notes"]').value = notes || '';
    document.getElementById('notesModal').classList.remove('hidden');
}

function openResubmitModal(proofId) {
    var form = document.getElementById('resubmitForm');
    form.action = '/admin/bank-transfers/' + proofId + '/request-resubmission';
    document.getElementById('resubmitModal').classList.remove('hidden');
}
</script>
@endsection
