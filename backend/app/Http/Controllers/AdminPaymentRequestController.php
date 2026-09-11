<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\PaymentRequest;
use App\Models\PaymentSetting;
use App\Models\Shipment;
use App\Models\User;
use App\Services\AdminAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminPaymentRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = PaymentRequest::with('shipment', 'creator', 'transactions');

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('shipment', fn ($s) => $s->where('tracking_number', 'like', "%{$search}%")
                        ->orWhere('recipient_name', 'like', "%{$search}%"));
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($method = $request->get('method')) {
            $query->where('requested_method', $method);
        }
        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }
        if ($currency = $request->get('currency')) {
            $query->where('currency', strtoupper($currency));
        }
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        if ($request->get('archived') === '1') {
            $query->where('is_archived', true);
        } elseif ($request->get('archived') !== 'all') {
            $query->where('is_archived', false);
        }

        $paymentRequests = $query->latest()->paginate(25)->withQueryString();

        $stats = [
            'total' => PaymentRequest::count(),
            'active' => PaymentRequest::active()->count(),
            'payable' => PaymentRequest::payable()->count(),
            'paid' => PaymentRequest::completed()->sum('amount'),
            'completed_count' => PaymentRequest::completed()->count(),
            'failed_count' => PaymentRequest::failed()->count(),
            'awaiting_count' => PaymentRequest::where('status', 'awaiting_verification')->count(),
            'expired_count' => PaymentRequest::where('status', 'expired')->count(),
            'archived_count' => PaymentRequest::where('is_archived', true)->count(),
            'draft_count' => PaymentRequest::where('status', 'draft')->count(),
            'revenue_by_currency' => PaymentRequest::completed()
                ->selectRaw('currency, SUM(amount) as total')
                ->groupBy('currency')->pluck('total', 'currency'),
            'revenue_by_gateway' => DB::table('payment_transactions')
                ->whereIn('status', ['paid', 'verified'])
                ->selectRaw('provider, SUM(amount) as total')
                ->groupBy('provider')->pluck('total', 'provider'),
        ];

        $activeGateways = PaymentSetting::where('is_active', true)->orderBy('gateway_name')->get();
        $requestShipments = Shipment::whereNotNull('tracking_number')->latest()->limit(200)->get(['id', 'tracking_number', 'recipient_name']);
        $statuses = PaymentRequest::STATUSES;
        $priorities = PaymentRequest::PRIORITIES;

        return view('admin.payment-requests.index', compact(
            'user', 'paymentRequests', 'stats', 'activeGateways',
            'requestShipments', 'statuses', 'priorities'
        ));
    }

    public function create()
    {
        $user = Auth::user();
        $requestShipments = Shipment::whereNotNull('tracking_number')->latest()->limit(200)->get(['id', 'tracking_number', 'recipient_name']);
        $activeGateways = PaymentSetting::where('is_active', true)->orderBy('gateway_name')->get();
        $statuses = PaymentRequest::STATUSES;
        $priorities = PaymentRequest::PRIORITIES;

        return view('admin.payment-requests.create', compact('user', 'requestShipments', 'activeGateways', 'statuses', 'priorities'));
    }

    public function store(Request $request, AdminAuditLogger $audit)
    {
        $user = Auth::user();

        $request->merge([
            'allowed_methods' => array_values(array_filter(
                $request->input('allowed_methods', []),
                fn ($v) => is_string($v) && $v !== ''
            )),
        ]);

        $data = $request->validate([
            'shipment_id' => ['required', 'exists:shipments,id'],
            'title' => ['required', 'string', 'max:180'],
            'category' => ['nullable', 'string', 'max:80'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'reason' => ['required', 'string', 'max:3000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'max:8'],
            'allowed_methods' => ['nullable', 'array'],
            'allowed_methods.*' => ['string', 'max:80'],
            'due_at' => ['nullable', 'date'],
            'payment_instructions' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:' . implode(',', PaymentRequest::STATUSES)],
        ]);

        $allowedMethods = $data['allowed_methods'] ?? [];
        foreach ($allowedMethods as $method) {
            abort_unless(
                PaymentSetting::where('gateway_name', $method)->where('is_active', true)->exists(),
                422,
                "The payment method '{$method}' is not enabled."
            );
        }

        $metadata = [];
        if (! empty($allowedMethods)) {
            $metadata['allowed_methods'] = $allowedMethods;
        }

        $paymentRequest = PaymentRequest::create([
            'shipment_id' => $data['shipment_id'],
            'title' => $data['title'],
            'category' => $data['category'] ?? null,
            'priority' => $data['priority'],
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'currency' => strtoupper($data['currency']),
            'requested_method' => count($allowedMethods) === 1 ? $allowedMethods[0] : null,
            'metadata' => $metadata,
            'secure_token' => Str::random(64),
            'due_at' => $data['due_at'] ?? null,
            'payment_instructions' => $data['payment_instructions'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'status' => $data['status'],
            'created_by' => $user->id,
        ]);

        $audit->log($request, $user, 'payment_request.created', [
            'target_type' => 'payment_request',
            'target_id' => $paymentRequest->id,
            'new_values' => $paymentRequest->fresh()->toArray(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('admin.payment-requests.index')]);
        }

        return redirect()->route('admin.payment-requests.index')
            ->with('success', "Payment request '{$paymentRequest->title}' created successfully.");
    }

    public function show(PaymentRequest $paymentRequest)
    {
        $user = Auth::user();
        $paymentRequest->loadMissing(['shipment', 'creator', 'verifier', 'transactions', 'paymentProofs']);

        $history = AdminAuditLog::where('target_type', 'payment_request')
            ->where('target_id', (string) $paymentRequest->id)
            ->with('admin')
            ->latest()
            ->limit(100)
            ->get();

        return view('admin.payment-requests.show', compact('user', 'paymentRequest', 'history'));
    }

    public function edit(PaymentRequest $paymentRequest)
    {
        $user = Auth::user();
        $requestShipments = Shipment::whereNotNull('tracking_number')->latest()->limit(200)->get(['id', 'tracking_number', 'recipient_name']);
        $activeGateways = PaymentSetting::where('is_active', true)->orderBy('gateway_name')->get();
        $statuses = PaymentRequest::STATUSES;
        $priorities = PaymentRequest::PRIORITIES;

        $allowedMethods = $paymentRequest->metadata['allowed_methods'] ?? [];

        return view('admin.payment-requests.edit', compact('user', 'paymentRequest', 'requestShipments', 'activeGateways', 'statuses', 'priorities', 'allowedMethods'));
    }

    public function update(Request $request, PaymentRequest $paymentRequest, AdminAuditLogger $audit)
    {
        $user = Auth::user();

        abort_if($paymentRequest->isCompleted() && ! $request->boolean('force'), 422, 'Cannot edit a completed payment request. Reopen it first.');

        $request->merge([
            'allowed_methods' => array_values(array_filter(
                $request->input('allowed_methods', []),
                fn ($v) => is_string($v) && $v !== ''
            )),
        ]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category' => ['nullable', 'string', 'max:80'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'reason' => ['required', 'string', 'max:3000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'max:8'],
            'allowed_methods' => ['nullable', 'array'],
            'allowed_methods.*' => ['string', 'max:80'],
            'due_at' => ['nullable', 'date'],
            'payment_instructions' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:' . implode(',', PaymentRequest::STATUSES)],
        ]);

        $allowedMethods = $data['allowed_methods'] ?? [];
        foreach ($allowedMethods as $method) {
            abort_unless(
                PaymentSetting::where('gateway_name', $method)->where('is_active', true)->exists(),
                422,
                "The payment method '{$method}' is not enabled."
            );
        }

        $metadata = $paymentRequest->metadata ?? [];
        if (! empty($allowedMethods)) {
            $metadata['allowed_methods'] = $allowedMethods;
        } else {
            unset($metadata['allowed_methods']);
        }

        $previous = $paymentRequest->fresh()->toArray();

        $paymentRequest->update([
            'title' => $data['title'],
            'category' => $data['category'] ?? null,
            'priority' => $data['priority'],
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'currency' => strtoupper($data['currency']),
            'requested_method' => count($allowedMethods) === 1 ? $allowedMethods[0] : null,
            'metadata' => $metadata,
            'due_at' => $data['due_at'] ?? null,
            'payment_instructions' => $data['payment_instructions'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'status' => $data['status'],
        ]);

        $audit->log($request, $user, 'payment_request.updated', [
            'target_type' => 'payment_request',
            'target_id' => $paymentRequest->id,
            'previous_values' => $previous,
            'new_values' => $paymentRequest->fresh()->toArray(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('admin.payment-requests.index')]);
        }

        return redirect()->route('admin.payment-requests.index')
            ->with('success', "Payment request '{$paymentRequest->title}' updated successfully.");
    }

    public function destroy(Request $request, PaymentRequest $paymentRequest, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        $snapshot = $paymentRequest->fresh()->toArray();

        $paymentRequest->transactions()->delete();
        $paymentRequest->delete();

        $audit->log($request, $user, 'payment_request.deleted', [
            'target_type' => 'payment_request',
            'target_id' => $snapshot['id'],
            'previous_values' => $snapshot,
        ]);

        return redirect()->route('admin.payment-requests.index')
            ->with('success', 'Payment request deleted.');
    }

    public function duplicate(Request $request, PaymentRequest $paymentRequest, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        $new = $paymentRequest->replicate();
        $new->secure_token = Str::random(64);
        $new->status = 'draft';
        $new->is_active = true;
        $new->is_archived = false;
        $new->paid_at = null;
        $new->verified_at = null;
        $new->payment_reference = null;
        $new->duplicated_from_id = $paymentRequest->id;
        $new->created_by = $user->id;
        $new->save();

        $audit->log($request, $user, 'payment_request.duplicated', [
            'target_type' => 'payment_request',
            'target_id' => $new->id,
            'metadata' => ['duplicated_from_id' => $paymentRequest->id, 'original_title' => $paymentRequest->title],
            'new_values' => $new->toArray(),
        ]);

        return redirect()->route('admin.payment-requests.edit', $new->id)
            ->with('success', "Payment request duplicated from '{$paymentRequest->title}'. Adjust details and activate when ready.");
    }

    public function toggleActive(Request $request, PaymentRequest $paymentRequest, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        $previous = $paymentRequest->fresh()->toArray();

        $paymentRequest->update(['is_active' => ! $paymentRequest->is_active]);

        $audit->log($request, $user, $paymentRequest->is_active ? 'payment_request.activated' : 'payment_request.disabled', [
            'target_type' => 'payment_request',
            'target_id' => $paymentRequest->id,
            'previous_values' => $previous,
            'new_values' => $paymentRequest->fresh()->toArray(),
        ]);

        $msg = $paymentRequest->is_active ? 'activated' : 'disabled';

        return redirect()->route('admin.payment-requests.show', $paymentRequest->id)
            ->with('success', "Payment request '{$paymentRequest->title}' {$msg}.");
    }

    public function cancel(Request $request, PaymentRequest $paymentRequest, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        abort_if($paymentRequest->isTerminal(), 422, 'This payment request is already in a terminal state.');
        $previous = $paymentRequest->fresh()->toArray();

        $paymentRequest->update(['status' => 'cancelled']);
        $paymentRequest->transactions()->whereIn('status', ['initiated', 'pending'])->update(['status' => 'cancelled']);

        $audit->log($request, $user, 'payment_request.cancelled', [
            'target_type' => 'payment_request',
            'target_id' => $paymentRequest->id,
            'previous_values' => $previous,
            'new_values' => $paymentRequest->fresh()->toArray(),
        ]);

        return redirect()->route('admin.payment-requests.show', $paymentRequest->id)
            ->with('success', "Payment request '{$paymentRequest->title}' cancelled.");
    }

    public function expire(Request $request, PaymentRequest $paymentRequest, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        abort_if($paymentRequest->isTerminal(), 422, 'This payment request is already in a terminal state.');
        $previous = $paymentRequest->fresh()->toArray();

        $paymentRequest->update(['status' => 'expired']);

        $audit->log($request, $user, 'payment_request.expired', [
            'target_type' => 'payment_request',
            'target_id' => $paymentRequest->id,
            'previous_values' => $previous,
            'new_values' => $paymentRequest->fresh()->toArray(),
        ]);

        return redirect()->route('admin.payment-requests.show', $paymentRequest->id)
            ->with('success', "Payment request '{$paymentRequest->title}' marked as expired.");
    }

    public function extendDueDate(Request $request, PaymentRequest $paymentRequest, AdminAuditLogger $audit)
    {
        $user = Auth::user();

        $data = $request->validate([
            'due_at' => ['required', 'date', 'after:today'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $previous = $paymentRequest->fresh()->toArray();
        $paymentRequest->update(['due_at' => $data['due_at']]);

        $audit->log($request, $user, 'payment_request.due_date_extended', [
            'target_type' => 'payment_request',
            'target_id' => $paymentRequest->id,
            'previous_values' => $previous,
            'new_values' => $paymentRequest->fresh()->toArray(),
            'reason' => $data['reason'] ?? null,
        ]);

        return redirect()->route('admin.payment-requests.show', $paymentRequest->id)
            ->with('success', "Due date extended to {$paymentRequest->due_at->format('M d, Y')}.");
    }

    public function markCompleted(Request $request, PaymentRequest $paymentRequest, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        abort_if($paymentRequest->status !== 'awaiting_verification', 422, 'Only payment requests awaiting verification can be marked as completed. Verify the bank transfer proof first.');

        $previous = $paymentRequest->fresh()->toArray();

        $paymentRequest->update([
            'status' => 'paid',
            'paid_at' => now(),
            'verified_at' => now(),
            'verified_by' => $user->id,
        ]);

        $paymentRequest->transactions()->where('provider', 'bank_transfer')->where('status', 'pending')->update(['status' => 'verified', 'verified_at' => now()]);

        $audit->log($request, $user, 'payment_request.completed', [
            'target_type' => 'payment_request',
            'target_id' => $paymentRequest->id,
            'previous_values' => $previous,
            'new_values' => $paymentRequest->fresh()->toArray(),
        ]);

        return redirect()->route('admin.payment-requests.show', $paymentRequest->id)
            ->with('success', "Payment request '{$paymentRequest->title}' marked as completed.");
    }

    public function refund(Request $request, PaymentRequest $paymentRequest, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        abort_if(! $paymentRequest->isCompleted(), 422, 'Only completed payments can be refunded.');

        $previous = $paymentRequest->fresh()->toArray();
        $paymentRequest->update(['status' => 'refunded']);

        $paymentRequest->transactions()->whereIn('status', ['paid', 'verified'])->update(['status' => 'cancelled']);

        $audit->log($request, $user, 'payment_request.refunded', [
            'target_type' => 'payment_request',
            'target_id' => $paymentRequest->id,
            'previous_values' => $previous,
            'new_values' => $paymentRequest->fresh()->toArray(),
        ]);

        return redirect()->route('admin.payment-requests.show', $paymentRequest->id)
            ->with('success', "Payment request '{$paymentRequest->title}' marked as refunded. Process the refund externally via the payment gateway.");
    }

    public function reopen(Request $request, PaymentRequest $paymentRequest, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        abort_if(in_array($paymentRequest->status, ['paid', 'verified']), 422, 'Completed payments cannot be reopened. Create a new refund request instead.');
        abort_if($paymentRequest->status === 'draft', 422, 'This is already in draft state.');

        $previous = $paymentRequest->fresh()->toArray();
        $paymentRequest->update(['status' => 'payment_required']);

        $audit->log($request, $user, 'payment_request.reopened', [
            'target_type' => 'payment_request',
            'target_id' => $paymentRequest->id,
            'previous_values' => $previous,
            'new_values' => $paymentRequest->fresh()->toArray(),
        ]);

        return redirect()->route('admin.payment-requests.show', $paymentRequest->id)
            ->with('success', "Payment request '{$paymentRequest->title}' reopened.");
    }

    public function archive(Request $request, PaymentRequest $paymentRequest, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        $previous = $paymentRequest->fresh()->toArray();

        $paymentRequest->update(['is_archived' => true, 'status' => 'archived']);

        $audit->log($request, $user, 'payment_request.archived', [
            'target_type' => 'payment_request',
            'target_id' => $paymentRequest->id,
            'previous_values' => $previous,
            'new_values' => $paymentRequest->fresh()->toArray(),
        ]);

        return redirect()->route('admin.payment-requests.show', $paymentRequest->id)
            ->with('success', "Payment request '{$paymentRequest->title}' archived.");
    }

    public function restore(Request $request, PaymentRequest $paymentRequest, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        $previous = $paymentRequest->fresh()->toArray();

        $paymentRequest->update(['is_archived' => false, 'status' => 'draft']);

        $audit->log($request, $user, 'payment_request.restored', [
            'target_type' => 'payment_request',
            'target_id' => $paymentRequest->id,
            'previous_values' => $previous,
            'new_values' => $paymentRequest->fresh()->toArray(),
        ]);

        return redirect()->route('admin.payment-requests.show', $paymentRequest->id)
            ->with('success', "Payment request '{$paymentRequest->title}' restored from archive.");
    }
}
