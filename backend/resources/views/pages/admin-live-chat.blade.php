@extends('layouts.admin')
@section('title', 'Live Chat')
@section('content')
<div x-data="chatApp()" x-init="init()" class="flex h-[calc(100vh-8rem)] gap-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    {{-- SIDEBAR: Conversation List --}}
    <div class="flex w-80 shrink-0 flex-col border-r border-slate-200 bg-slate-50">
        <div class="border-b border-slate-200 p-4">
            <h2 class="text-lg font-bold text-slate-900">Live Chat</h2>
            <div class="mt-3 flex gap-1">
                <button @click="setFilter('active')" :class="filter === 'active' ? 'bg-[#0d5368] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="rounded-full px-3 py-1 text-xs font-semibold transition-colors">Active</button>
                <button @click="setFilter('unread')" :class="filter === 'unread' ? 'bg-[#0d5368] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="rounded-full px-3 py-1 text-xs font-semibold transition-colors">Unread</button>
                <button @click="setFilter('closed')" :class="filter === 'closed' ? 'bg-[#0d5368] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="rounded-full px-3 py-1 text-xs font-semibold transition-colors">Closed</button>
                <button @click="setFilter('archived')" :class="filter === 'archived' ? 'bg-[#0d5368] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="rounded-full px-3 py-1 text-xs font-semibold transition-colors">Archv</button>
            </div>
            <div class="mt-3 flex gap-1 text-xs text-slate-500">
                <span><span x-text="counts.active"></span> active</span>
                <span class="text-slate-300">|</span>
                <span class="font-semibold text-red-600"><span x-text="counts.unread"></span> unread</span>
                <span class="text-slate-300">|</span>
                <span><span x-text="counts.closed"></span> closed</span>
            </div>
            <input @input.debounce="search = $event.target.value; loadConversations()" class="mt-3 min-h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm" placeholder="Search tracking, name, email...">
        </div>
        <div class="flex-1 space-y-1 overflow-y-auto p-2">
            <template x-for="conv in conversations" :key="conv.id">
                <div @click="selectConversation(conv.id)" :class="selectedId === conv.id ? 'border-[#0d5368] bg-[#0d5368]/5' : 'border-transparent hover:bg-slate-100'" class="cursor-pointer rounded-lg border-2 p-3 transition-all">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-slate-900" x-text="conv.guest_name || 'Guest'"></p>
                            <p class="truncate text-xs text-slate-500" x-text="conv.tracking_number"></p>
                        </div>
                        <span :class="conv.chat_status === 'closed' ? 'bg-slate-200 text-slate-600' : conv.chat_status === 'waiting_for_admin' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'" class="ml-2 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold" x-text="statusLabel(conv.chat_status)"></span>
                    </div>
                    <p class="mt-1 truncate text-xs text-slate-400" x-text="conv.latest_message?.body || 'No messages'"></p>
                    <p class="mt-0.5 text-[10px] text-slate-400" x-text="timeAgo(conv.updated_at)"></p>
                </div>
            </template>
            <template x-if="!loading && conversations.length === 0">
                <p class="p-6 text-center text-sm text-slate-400">No conversations found.</p>
            </template>
            <template x-if="loading">
                <p class="p-6 text-center text-sm text-slate-400">Loading...</p>
            </template>
        </div>
    </div>

    {{-- MAIN: Chat Window --}}
    <div class="flex flex-1 flex-col overflow-hidden" x-show="selectedId" x-cloak>
        <template x-if="current">
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                    <div>
                        <h3 class="font-bold text-slate-900" x-text="current.guest_name || 'Guest'"></h3>
                        <p class="text-xs text-slate-500" x-text="current.tracking_number"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="closeChat(current.id)" x-show="current.chat_status !== 'closed'" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Close</button>
                        <button @click="reopenChat(current.id)" x-show="current.chat_status === 'closed'" class="rounded-md border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50">Reopen</button>
                        <button @click="archiveChat(current.id)" x-show="current.chat_status !== 'archived'" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-50">Archive</button>
                        <button @click="deleteChat(current.id)" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                    </div>
                </div>

                {{-- Messages --}}
                <div x-ref="messages" class="flex-1 space-y-3 overflow-y-auto p-5">
                    <template x-for="msg in messages" :key="msg.id">
                        <div :class="msg.sender_type === 'admin' ? 'ml-12' : 'mr-12'" class="flex items-start gap-3">
                            <div :class="msg.sender_type === 'admin' ? 'order-1 bg-[#0d5368] text-white' : 'order-1 bg-slate-100 text-slate-800'" class="max-w-[75%] rounded-xl px-4 py-2.5 shadow-sm">
                                <p class="text-[10px] font-semibold uppercase tracking-wide opacity-70" x-text="msg.sender_type === 'admin' ? (msg.admin_name || 'You') : current.guest_name || 'Guest'"></p>
                                <p class="mt-0.5 whitespace-pre-wrap text-sm" x-text="msg.body"></p>
                                <div x-show="msg.attachments && msg.attachments.length" class="mt-2 space-y-1">
                                    <template x-for="att in (msg.attachments || [])" :key="att.name">
                                        <a :href="att.url" target="_blank" class="flex items-center gap-1.5 rounded-md bg-black/10 px-2 py-1 text-xs font-semibold hover:bg-black/20">
                                            <span x-text="att.name"></span>
                                            <span class="opacity-60" x-text="formatSize(att.size)"></span>
                                        </a>
                                    </template>
                                </div>
                                <div class="mt-1 flex items-center gap-2 text-[10px] opacity-60">
                                    <span x-text="formatTime(msg.created_at)"></span>
                                    <span x-text="msg.read_at ? 'Read' : msg.delivery_status === 'delivered' ? 'Delivered' : 'Sent'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="loadingMessages">
                        <p class="text-center text-sm text-slate-400">Loading messages...</p>
                    </template>
                </div>

                {{-- Reply Box --}}
                <div x-show="!isClosed" class="border-t border-slate-200 p-4">
                    <form @submit.prevent="sendReply" class="flex gap-3">
                        <div class="relative flex-1">
                            <textarea x-model="replyText" @keydown.enter.prevent="sendReply" rows="2" class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0d5368]/20" placeholder="Type your reply..."></textarea>
                            <div class="mt-1 flex items-center gap-2">
                                <label class="flex cursor-pointer items-center gap-1 text-xs text-slate-400 hover:text-[#0d5368]">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    Attach
                                    <input type="file" @change="addFiles" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt" class="hidden">
                                </label>
                                <span x-show="replyFiles.length" class="text-xs text-slate-500" x-text="replyFiles.length + ' file(s)'"></span>
                            </div>
                        </div>
                        <button type="submit" :disabled="!replyText.trim() && !replyFiles.length" class="shrink-0 self-end rounded-lg bg-[#0d5368] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#0b4658] disabled:opacity-40">Send</button>
                    </form>
                </div>
                <div x-show="isClosed" class="border-t border-slate-200 p-4 text-center text-sm text-slate-400">
                    This conversation is closed.
                </div>
            </div>
        </template>
    </div>

    {{-- SHIPMENT SIDEBAR --}}
    <div class="w-80 shrink-0 border-l border-slate-200 overflow-y-auto" x-show="shipment" x-cloak>
        <template x-if="shipment">
            <div class="p-4 space-y-4">
                <h3 class="text-sm font-bold text-slate-900">Shipment Details</h3>

                <div class="rounded-lg bg-slate-50 p-3 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500">Tracking</span>
                        <span class="font-mono text-sm font-bold text-slate-900" x-text="shipment.tracking_number"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500">Status</span>
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700" x-text="shipment.status"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500">Service</span>
                        <span class="text-sm font-semibold text-slate-900" x-text="shipment.service_level"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500">Weight</span>
                        <span class="text-sm font-semibold text-slate-900" x-text="shipment.weight_kg + ' kg'"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500">Value</span>
                        <span class="text-sm font-semibold text-slate-900" x-text="'$' + Number(shipment.declared_value).toFixed(2)"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500">Est. Delivery</span>
                        <span class="text-sm font-semibold text-slate-900" x-text="shipment.estimated_delivery_at"></span>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">Sender</h4>
                    <p class="mt-1 text-sm font-semibold text-slate-900" x-text="shipment.sender_name"></p>
                    <p class="text-xs text-slate-500" x-text="typeof shipment.origin_address === 'object' ? (shipment.origin_address.city + ', ' + shipment.origin_address.country) : shipment.origin_address"></p>
                </div>

                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">Recipient</h4>
                    <p class="mt-1 text-sm font-semibold text-slate-900" x-text="shipment.recipient_name"></p>
                    <p class="text-xs text-slate-500" x-text="typeof shipment.destination_address === 'object' ? (shipment.destination_address.city + ', ' + shipment.destination_address.country) : shipment.destination_address"></p>
                </div>

                <div x-show="shipment.payment_requests && shipment.payment_requests.length">
                    <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">Payments</h4>
                    <div class="mt-1 space-y-1">
                        <template x-for="pr in shipment.payment_requests" :key="pr.id">
                            <div class="flex items-center justify-between rounded-md bg-slate-50 px-2 py-1.5">
                                <div>
                                    <p class="text-xs font-semibold text-slate-900" x-text="pr.title"></p>
                                    <p class="text-[10px] text-slate-500" x-text="pr.currency + ' ' + Number(pr.amount).toFixed(2)"></p>
                                </div>
                                <span :class="pr.status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'" class="rounded-full px-2 py-0.5 text-[10px] font-bold" x-text="pr.status"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div x-show="shipment.tracking_events && shipment.tracking_events.length">
                    <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">Tracking History</h4>
                    <div class="mt-1 space-y-1">
                        <template x-for="evt in shipment.tracking_events.slice(0, 5)" :key="evt.status">
                            <div class="flex items-start gap-2 border-l-2 border-slate-200 pl-2">
                                <div>
                                    <p class="text-xs font-semibold text-slate-900" x-text="evt.description || evt.status"></p>
                                    <p class="text-[10px] text-slate-500" x-text="evt.location ? evt.location + ' - ' : '' + evt.occurred_at"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <a :href="'{{ url('/admin/shipments') }}/' + shipment.id" target="_blank" class="mt-2 block rounded-md bg-[#0d5368] px-3 py-2 text-center text-xs font-bold text-white hover:bg-[#0b4658]">Open Shipment</a>
            </div>
        </template>
    </div>

    {{-- Empty State --}}
    <div class="flex flex-1 items-center justify-center" x-show="!selectedId" x-cloak>
        <div class="text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mx-auto text-slate-300"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <p class="mt-3 text-sm font-semibold text-slate-400">Select a conversation</p>
            <p class="text-xs text-slate-300">Choose a chat from the sidebar to view messages</p>
        </div>
    </div>
</div>

<script>
    function chatApp() {
        return {
            conversations: [],
            selectedId: null,
            current: null,
            messages: [],
            shipment: null,
            filter: 'active',
            search: '',
            replyText: '',
            replyFiles: [],
            loading: false,
            loadingMessages: false,
            pollTimer: null,
            notifCount: 0,
            counts: { active: 0, unread: 0, closed: 0, archived: 0, total: 0 },

            get isClosed() {
                return this.current && (this.current.chat_status === 'closed' || this.current.chat_status === 'archived');
            },

            init() {
                this.loadConversations();
                this.pollTimer = setInterval(() => this.poll(), 5000);
            },

            setFilter(f) {
                this.filter = f;
                this.loadConversations();
            },

            async loadConversations() {
                this.loading = true;
                try {
                    const params = new URLSearchParams({ filter: this.filter });
                    if (this.search) params.set('search', this.search);
                    const res = await fetch('{{ route('admin.live-chat.conversations') }}?' + params);
                    const data = await res.json();
                    this.conversations = data.conversations || [];
                    this.counts = data.counts || this.counts;
                } catch (e) {}
                this.loading = false;
            },

            async selectConversation(id) {
                this.selectedId = id;
                this.loadingMessages = true;
                try {
                    const res = await fetch('{{ url('/admin/api/live-chat/conversations') }}/' + id);
                    const data = await res.json();
                    this.current = data.conversation;
                    this.messages = data.conversation?.messages || [];
                    this.shipment = data.shipment || null;
                    this.$nextTick(() => this.scrollDown());
                } catch (e) {}
                this.loadingMessages = false;
            },

            async poll() {
                const oldUnread = this.counts.unread;
                if (this.selectedId) {
                    try {
                        const res = await fetch('{{ url('/admin/api/live-chat/conversations') }}/' + this.selectedId);
                        const data = await res.json();
                        if (data.conversation) {
                            const oldLen = this.messages.length;
                            this.messages = data.conversation.messages || [];
                            if (this.messages.length > oldLen) this.$nextTick(() => this.scrollDown());
                        }
                    } catch (e) {}
                }
                const params = new URLSearchParams({ filter: this.filter });
                if (this.search) params.set('search', this.search);
                try {
                    const res = await fetch('{{ route('admin.live-chat.conversations') }}?' + params);
                    const data = await res.json();
                    this.conversations = data.conversations || [];
                    this.counts = data.counts || this.counts;
                    if (this.counts.unread > oldUnread && oldUnread !== undefined) {
                        this.playNotifSound();
                    }
                } catch (e) {}
            },

            playNotifSound() {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.frequency.value = 800;
                    gain.gain.value = 0.1;
                    osc.start();
                    osc.stop(ctx.currentTime + 0.15);
                } catch (e) {}
            },

            async sendReply() {
                if (!this.replyText.trim() && !this.replyFiles.length) return;
                const formData = new FormData();
                formData.append('message', this.replyText);
                for (const file of this.replyFiles) formData.append('attachments[]', file);
                try {
                    const res = await fetch('{{ url('/admin/api/live-chat/conversations') }}/' + this.selectedId + '/reply', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        data.message.read_at = new Date().toISOString();
                        data.message.delivery_status = 'read';
                        this.messages.push(data.message);
                        this.replyText = '';
                        this.replyFiles = [];
                        this.$nextTick(() => this.scrollDown());
                        this.loadConversations();
                    }
                } catch (e) {}
            },

            addFiles(e) {
                this.replyFiles = [...e.target.files];
                e.target.value = '';
            },

            async closeChat(id) {
                if (!confirm('Close this conversation?')) return;
                try {
                    await fetch('{{ url('/admin/api/live-chat/conversations') }}/' + id + '/close', {
                        method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    if (this.selectedId === id) this.current.chat_status = 'closed';
                    this.loadConversations();
                } catch (e) {}
            },

            async reopenChat(id) {
                try {
                    await fetch('{{ url('/admin/api/live-chat/conversations') }}/' + id + '/reopen', {
                        method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    if (this.selectedId === id) this.current.chat_status = 'waiting_for_admin';
                    this.loadConversations();
                } catch (e) {}
            },

            async archiveChat(id) {
                if (!confirm('Archive this conversation?')) return;
                try {
                    await fetch('{{ url('/admin/api/live-chat/conversations') }}/' + id + '/archive', {
                        method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    if (this.selectedId === id) this.current.chat_status = 'archived';
                    this.loadConversations();
                } catch (e) {}
            },

            async deleteChat(id) {
                if (!confirm('Permanently delete this entire conversation?')) return;
                if (!confirm('This cannot be undone. Delete?')) return;
                try {
                    await fetch('{{ url('/admin/api/live-chat/conversations') }}/' + id, {
                        method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    this.selectedId = null;
                    this.current = null;
                    this.messages = [];
                    this.shipment = null;
                    this.loadConversations();
                } catch (e) {}
            },

            scrollDown() {
                const el = this.$refs.messages;
                if (el) el.scrollTop = el.scrollHeight;
            },

            statusLabel(s) {
                return {
                    'open': 'Open',
                    'waiting_for_admin': 'Waiting',
                    'waiting_for_guest': 'Replied',
                    'closed': 'Closed',
                    'archived': 'Archived'
                }[s] || s;
            },

            formatTime(t) {
                if (!t) return '';
                const d = new Date(t);
                const now = new Date();
                const isToday = d.toDateString() === now.toDateString();
                const time = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                if (isToday) return time;
                const yesterday = new Date(now); yesterday.setDate(yesterday.getDate() - 1);
                if (d.toDateString() === yesterday.toDateString()) return 'Yesterday ' + time;
                return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' ' + time;
            },

            formatSize(bytes) {
                if (!bytes) return '';
                if (bytes < 1024) return bytes + 'B';
                return (bytes / 1024).toFixed(1) + 'KB';
            },

            timeAgo(t) {
                if (!t) return '';
                const diff = Date.now() - new Date(t).getTime();
                const mins = Math.floor(diff / 60000);
                if (mins < 1) return 'just now';
                if (mins < 60) return mins + 'm ago';
                const hrs = Math.floor(mins / 60);
                if (hrs < 24) return hrs + 'h ago';
                return new Date(t).toLocaleDateString();
            }
        };
    }
</script>
<style>
    [x-cloak] { display: none !important; }
</style>
@endSection
