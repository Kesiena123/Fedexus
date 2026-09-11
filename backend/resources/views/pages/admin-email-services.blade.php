@extends('layouts.app')
@section('title', 'Email Services')
@php $hideHeaderFooter = true; @endphp
@section('content')
    <x-dashboard-shell title="Email Services">
        @if(session('success'))
            <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
            <form method="POST" action="{{ route('admin.email-services') }}" enctype="multipart/form-data" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                <h3 class="text-lg font-bold text-slate-900">Send Email</h3>
                <p class="mt-1 text-sm text-slate-500">Send operational messages to customers, senders, receivers, or selected users.</p>

                <div class="mt-5 grid gap-4">
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        Recipient Type
                        <select name="recipient_mode" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 font-normal">
                            <option value="user">Selected User</option>
                            <option value="email">Custom Email</option>
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        Selected User
                        <select name="recipient_user_id" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 font-normal">
                            <option value="">Choose user</option>
                            @foreach($users as $target)
                                <option value="{{ $target->id }}">{{ $target->name }} - {{ $target->email }} ({{ str_replace('_', ' ', $target->role) }})</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        Custom Email
                        <input name="recipient_email" type="email" class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 font-normal" placeholder="name@example.com">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        Subject
                        <input name="subject" required class="focus-ring min-h-11 rounded-md border border-slate-200 px-3 font-normal" value="{{ old('subject') }}">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        Message
                        <textarea name="message" required rows="8" class="focus-ring rounded-md border border-slate-200 px-3 py-3 font-normal">{{ old('message') }}</textarea>
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        Optional Attachment
                        <input name="attachment" type="file" class="focus-ring rounded-md border border-slate-200 px-3 py-2 font-normal">
                    </label>
                    <button class="focus-ring inline-flex min-h-11 items-center justify-center rounded-md bg-[#0d5368] px-5 text-sm font-bold text-white hover:bg-[#0b4658]">Send Email</button>
                </div>
            </form>

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Email History</h3>
                        <p class="mt-1 text-sm text-slate-500">Every sent or fallback-logged message is recorded here.</p>
                    </div>
                </div>
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="text-xs uppercase text-slate-500">
                            <tr>
                                <th class="border-b p-3">Recipient</th>
                                <th class="border-b p-3">Subject</th>
                                <th class="border-b p-3">Sender</th>
                                <th class="border-b p-3">Status</th>
                                <th class="border-b p-3">Date Sent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($emailLogs as $log)
                                <tr>
                                    <td class="border-b border-slate-100 p-3">{{ $log->recipient_email }}</td>
                                    <td class="border-b border-slate-100 p-3 font-semibold text-slate-800">{{ $log->subject }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ $log->sender?->name ?? 'System' }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ str_replace('_', ' ', $log->status) }}</td>
                                    <td class="border-b border-slate-100 p-3">{{ $log->sent_at?->format('M d, Y H:i') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="p-6 text-center text-slate-500">No email history yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $emailLogs->links() }}</div>
            </div>
        </div>
    </x-dashboard-shell>
@endsection
