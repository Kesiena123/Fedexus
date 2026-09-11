<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\PaymentAuditLog;
use App\Models\PaymentProof;
use App\Models\PaymentRequest;
use App\Models\PaymentTransaction;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\NotificationDispatchService;
use App\Support\AdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        if ($request->isMethod('post') && $request->input('action') === 'create') {
            return $this->store($request, $user);
        }

        $bankAccounts = BankAccount::ordered()->get();

        return view('pages.admin-bank-accounts', compact('user', 'bankAccounts'));
    }

    public function store(Request $request, ?User $user = null)
    {
        $user = $user ?? Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:200'],
            'account_name' => ['required', 'string', 'max:200'],
            'account_number' => ['required', 'string', 'max:100'],
            'swift_bic' => ['nullable', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'max:50'],
            'routing_number' => ['nullable', 'string', 'max:100'],
            'branch_name' => ['nullable', 'string', 'max:200'],
            'branch_address' => ['nullable', 'string', 'max:500'],
            'country' => ['nullable', 'string', 'max:100'],
            'supported_currency' => ['required', 'string', 'max:8'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        if (! empty($data['bank_logo']) && $request->hasFile('bank_logo')) {
            $file = $request->file('bank_logo');
            if ($file->isValid()) {
                $data['bank_logo'] = $file->store('bank-logos', 'public');
            }
        }
        unset($data['bank_logo']);

        $bankAccount = BankAccount::create($data);

        if ($bankAccount->is_default) {
            BankAccount::where('id', '!=', $bankAccount->id)->update(['is_default' => false]);
        }

        return back()->with('success', "Bank account \"{$bankAccount->bank_name}\" added successfully.");
    }

    public function edit(Request $request, BankAccount $bankAccount)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'bank_name' => ['required', 'string', 'max:200'],
                'account_name' => ['required', 'string', 'max:200'],
                'account_number' => ['required', 'string', 'max:100'],
                'swift_bic' => ['nullable', 'string', 'max:50'],
                'iban' => ['nullable', 'string', 'max:50'],
                'routing_number' => ['nullable', 'string', 'max:100'],
                'branch_name' => ['nullable', 'string', 'max:200'],
                'branch_address' => ['nullable', 'string', 'max:500'],
                'country' => ['nullable', 'string', 'max:100'],
                'supported_currency' => ['required', 'string', 'max:8'],
                'payment_instructions' => ['nullable', 'string', 'max:2000'],
                'is_active' => ['nullable', 'boolean'],
                'is_default' => ['nullable', 'boolean'],
            ]);

            $data['is_active'] = (bool) ($data['is_active'] ?? false);
            $data['is_default'] = (bool) ($data['is_default'] ?? false);

            if ($request->hasFile('bank_logo')) {
                $file = $request->file('bank_logo');
                if ($file->isValid()) {
                    if ($bankAccount->bank_logo) {
                        Storage::disk('public')->delete($bankAccount->bank_logo);
                    }
                    $data['bank_logo'] = $file->store('bank-logos', 'public');
                }
            }
            unset($data['bank_logo']);

            $bankAccount->update($data);

            if ($bankAccount->is_default) {
                BankAccount::where('id', '!=', $bankAccount->id)->update(['is_default' => false]);
            }

            return back()->with('success', "Bank account \"{$bankAccount->bank_name}\" updated successfully.");
        }

        return view('pages.admin-bank-account-edit', compact('user', 'bankAccount'));
    }

    public function destroy(BankAccount $bankAccount)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        if ($bankAccount->bank_logo) {
            Storage::disk('public')->delete($bankAccount->bank_logo);
        }

        $name = $bankAccount->bank_name;
        $bankAccount->delete();

        return back()->with('success', "Bank account \"{$name}\" deleted.");
    }

    public function toggle(BankAccount $bankAccount)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        $bankAccount->update(['is_active' => ! $bankAccount->is_active]);
        $status = $bankAccount->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "\"{$bankAccount->bank_name}\" {$status}.");
    }

    public function setDefault(BankAccount $bankAccount)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        BankAccount::where('is_default', true)->update(['is_default' => false]);
        $bankAccount->update(['is_default' => true]);

        return back()->with('success', "\"{$bankAccount->bank_name}\" set as default.");
    }

    public function reorder(Request $request)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        $order = $request->input('order', []);
        foreach ($order as $index => $id) {
            BankAccount::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    public function review(Request $request)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        if ($request->isMethod('post')) {
            $action = $request->input('action');
            $proofId = $request->input('proof_id');

            if ($action === 'approve') {
                return $this->approve($request, $proofId);
            }
            if ($action === 'reject') {
                return $this->reject($request, $proofId);
            }
        }

        $query = PaymentProof::with(['paymentRequest.shipment', 'transaction'])
            ->orderBy('created_at', 'desc');

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('paymentRequest', fn ($pr) => $pr->where('title', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $proofs = $query->paginate(25)->withQueryString();

        $stats = [
            'pending' => PaymentProof::where('status', 'pending')->count(),
            'verified' => PaymentProof::where('status', 'verified')->count(),
            'rejected' => PaymentProof::where('status', 'rejected')->count(),
            'total_amount' => PaymentTransaction::where('provider', 'bank_transfer')
                ->where('status', 'verified')
                ->sum('amount'),
        ];

        return view('pages.admin-bank-transfers', compact('user', 'proofs', 'stats'));
    }

    public function approve(Request $request, int $proofId)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        $proof = PaymentProof::with(['paymentRequest', 'transaction'])->findOrFail($proofId);
        $notes = $request->input('notes', '');

        DB::transaction(function () use ($proof, $user, $notes, $request) {
            $proof->update([
                'status' => 'verified',
                'verified_at' => now(),
                'verified_by' => $user->id,
                'notes' => $notes,
            ]);

            if ($proof->transaction) {
                $proof->transaction->update([
                    'status' => 'verified',
                    'verified_at' => now(),
                    'provider_payload' => array_merge($proof->transaction->provider_payload ?? [], [
                        'admin_approved' => true,
                        'approved_at' => now()->toIso8601String(),
                        'approved_by' => $user->id,
                        'approval_notes' => $notes,
                    ]),
                ]);
            }

            $paymentRequest = $proof->paymentRequest;
            if ($paymentRequest) {
                $paymentRequest->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'verified_at' => now(),
                    'verified_by' => $user->id,
                ]);

                if ($paymentRequest->shipment) {
                    $shipment = $paymentRequest->shipment;
                    $shipment->update([
                        'payment_status' => 'paid',
                        'metadata' => array_merge($shipment->metadata ?? [], [
                            'payment_verified' => true,
                            'payment_verified_at' => now()->toIso8601String(),
                            'verified_by' => $user->id,
                            'payment_method' => 'bank_transfer',
                            'payment_reference' => $proof->payment_reference,
                        ]),
                    ]);

                    TrackingEvent::create([
                        'shipment_id' => $shipment->id,
                        'event' => 'Payment Verified',
                        'location' => 'Payment Department',
                        'description' => "Bank transfer payment of {$paymentRequest->currency} {$paymentRequest->amount} was verified (ref: {$proof->payment_reference}).",
                        'metadata' => [
                            'payment_request_id' => $paymentRequest->id,
                            'proof_id' => $proof->id,
                            'payment_reference' => $proof->payment_reference,
                            'method' => 'bank_transfer',
                        ],
                    ]);
                }
            }

            PaymentAuditLog::create([
                'payment_transaction_id' => $proof->transaction_id,
                'event' => 'bank_transfer.approved',
                'provider' => 'bank_transfer',
                'provider_reference' => $proof->payment_reference,
                'amount' => $proof->transaction?->amount,
                'currency' => $proof->transaction?->currency,
                'previous_status' => 'pending',
                'new_status' => 'verified',
                'admin_user_id' => $user->id,
                'ip_address' => $request->ip(),
                'payload' => [
                    'proof_id' => $proof->id,
                    'notes' => $notes,
                ],
            ]);

            Log::info('[BankTransfer] Payment approved', [
                'proof_id' => $proof->id,
                'payment_reference' => $proof->payment_reference,
                'approved_by' => $user->id,
            ]);

            try {
                $notifier = app(NotificationDispatchService::class);
                $notifier->send($user, 'Payment Approved - Bank Transfer', "Payment reference {$proof->payment_reference} has been approved.", [
                    'type' => 'payment',
                    'payment_reference' => $proof->payment_reference,
                    'proof_id' => $proof->id,
                    'amount' => $proof->transaction?->amount,
                    'currency' => $proof->transaction?->currency,
                    'event' => 'bank_transfer.approved',
                ], ['in_app']);

                $shipment = $proof->paymentRequest?->shipment;
                $guestEmail = $shipment?->metadata['sender']['email'] ?? $shipment?->metadata['receiver']['email'] ?? null;
                if ($guestEmail) {
                    $notifier->send($user, 'Your Payment Has Been Approved', "Your bank transfer payment of {$proof->transaction?->currency} {$proof->transaction?->amount} for shipment {$shipment?->tracking_number} has been approved. Your shipment is now marked as paid.", [
                        'type' => 'payment',
                        'payment_reference' => $proof->payment_reference,
                        'tracking_number' => $shipment?->tracking_number ?? '',
                        'event' => 'bank_transfer.approved.guest',
                    ], ['email'], ['email' => $guestEmail]);
                }
            } catch (Throwable $e) {
                Log::warning('[BankTransfer] Failed to send approval notification', ['error' => $e->getMessage()]);
            }
        });

        return back()->with('success', 'Payment approved successfully. Guest will see PAID status on tracking page.');
    }

    public function reject(Request $request, int $proofId)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        $proof = PaymentProof::with(['paymentRequest', 'transaction'])->findOrFail($proofId);
        $reason = $request->input('rejection_reason', '');

        abort_if(empty($reason), 422, 'Rejection reason is required.');

        DB::transaction(function () use ($proof, $user, $reason, $request) {
            $proof->update([
                'status' => 'rejected',
                'verified_at' => now(),
                'verified_by' => $user->id,
                'rejection_reason' => $reason,
            ]);

            if ($proof->transaction) {
                $proof->transaction->update([
                    'provider_payload' => array_merge($proof->transaction->provider_payload ?? [], [
                        'admin_rejected' => true,
                        'rejected_at' => now()->toIso8601String(),
                        'rejected_by' => $user->id,
                        'rejection_reason' => $reason,
                    ]),
                ]);
            }

            if ($proof->paymentRequest && $proof->paymentRequest->status !== 'paid') {
                $proof->paymentRequest->update(['status' => 'payment_required']);
            }

            PaymentAuditLog::create([
                'payment_transaction_id' => $proof->transaction_id,
                'event' => 'bank_transfer.rejected',
                'provider' => 'bank_transfer',
                'provider_reference' => $proof->payment_reference,
                'amount' => $proof->transaction?->amount,
                'currency' => $proof->transaction?->currency,
                'previous_status' => 'pending',
                'new_status' => 'rejected',
                'admin_user_id' => $user->id,
                'ip_address' => $request->ip(),
                'payload' => [
                    'proof_id' => $proof->id,
                    'rejection_reason' => $reason,
                ],
            ]);

            Log::info('[BankTransfer] Payment rejected', [
                'proof_id' => $proof->id,
                'payment_reference' => $proof->payment_reference,
                'rejected_by' => $user->id,
                'reason' => $reason,
            ]);

            try {
                $notifier = app(NotificationDispatchService::class);
                $notifier->send($user, 'Payment Rejected - Bank Transfer', "Payment reference {$proof->payment_reference} was rejected. Reason: {$reason}", [
                    'type' => 'payment',
                    'payment_reference' => $proof->payment_reference,
                    'proof_id' => $proof->id,
                    'rejection_reason' => $reason,
                    'event' => 'bank_transfer.rejected',
                ], ['in_app']);

                $shipment = $proof->paymentRequest?->shipment;
                $guestEmail = $shipment?->metadata['sender']['email'] ?? $shipment?->metadata['receiver']['email'] ?? null;
                if ($guestEmail) {
                    $notifier->send($user, 'Payment Proof Rejected', "Your bank transfer payment proof for shipment {$shipment?->tracking_number} was rejected. Reason: {$reason}. Please upload a corrected proof.", [
                        'type' => 'payment',
                        'payment_reference' => $proof->payment_reference,
                        'tracking_number' => $shipment?->tracking_number ?? '',
                        'rejection_reason' => $reason,
                        'event' => 'bank_transfer.rejected.guest',
                    ], ['email'], ['email' => $guestEmail]);
                }
            } catch (Throwable $e) {
                Log::warning('[BankTransfer] Failed to send rejection notification', ['error' => $e->getMessage()]);
            }
        });

        return back()->with('success', 'Payment rejected. Guest will see the rejection status on tracking page.');
    }

    public function addNotes(Request $request, PaymentProof $paymentProof)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        $data = $request->validate([
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $paymentProof->update(['internal_notes' => $data['internal_notes'] ?? '']);

        Log::info('[BankTransfer] Internal notes updated', [
            'proof_id' => $paymentProof->id,
            'updated_by' => $user->id,
        ]);

        return back()->with('success', 'Internal notes saved.');
    }

    public function requestResubmission(Request $request, PaymentProof $paymentProof)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        $data = $request->validate([
            'resubmission_reason' => ['required', 'string', 'max:2000'],
        ]);

        $paymentProof->update([
            'status' => 'pending',
            'resubmission_requested_at' => now(),
            'resubmission_reason' => $data['resubmission_reason'],
            'verified_by' => $user->id,
        ]);

        PaymentAuditLog::create([
            'payment_transaction_id' => $paymentProof->transaction_id,
            'event' => 'bank_transfer.resubmission_requested',
            'provider' => 'bank_transfer',
            'provider_reference' => $paymentProof->payment_reference,
            'amount' => $paymentProof->transaction?->amount,
            'currency' => $paymentProof->transaction?->currency,
            'previous_status' => $paymentProof->getOriginal('status'),
            'new_status' => 'pending',
            'admin_user_id' => $user->id,
            'ip_address' => $request->ip(),
            'payload' => [
                'proof_id' => $paymentProof->id,
                'resubmission_reason' => $data['resubmission_reason'],
            ],
        ]);

        Log::info('[BankTransfer] Resubmission requested', [
            'proof_id' => $paymentProof->id,
            'reason' => $data['resubmission_reason'],
        ]);

        try {
            $notifier = app(NotificationDispatchService::class);
            $shipment = $paymentProof->paymentRequest?->shipment;
            $guestEmail = $shipment?->metadata['sender']['email'] ?? $shipment?->metadata['receiver']['email'] ?? null;
            if ($guestEmail) {
                $notifier->send($user, 'Payment Proof Resubmission Required', "Your bank transfer payment proof for shipment {$shipment?->tracking_number} needs resubmission. Reason: {$data['resubmission_reason']}. Please upload a new proof.", [
                    'type' => 'payment',
                    'payment_reference' => $paymentProof->payment_reference,
                    'tracking_number' => $shipment?->tracking_number ?? '',
                    'resubmission_reason' => $data['resubmission_reason'],
                    'event' => 'bank_transfer.resubmission_requested.guest',
                ], ['email'], ['email' => $guestEmail]);
            }
        } catch (\Throwable $e) {
            Log::warning('[BankTransfer] Failed to send resubmission notification', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Resubmission requested. Guest can now upload a new proof.');
    }
}
