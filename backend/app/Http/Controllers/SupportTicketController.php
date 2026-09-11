<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shipment_id' => ['nullable', 'exists:shipments,id'],
            'subject' => ['required', 'string', 'max:180'],
            'category' => ['required', 'in:delivery,billing,customs,damage,account,other'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'body' => ['required', 'string'],
        ]);

        $ticket = SupportTicket::create($data + [
            'user_id' => $request->user()->id,
            'priority' => $data['priority'] ?? 'normal',
        ]);

        return response()->json(['ticket' => $ticket], 201);
    }

    public function storeFromForm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tracking' => ['nullable', 'string', 'max:60'],
            'category' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $categoryMap = [
            'Delivery issue' => 'delivery',
            'Billing and payment' => 'billing',
            'Customs and documents' => 'customs',
            'Damage claim' => 'damage',
        ];

        $shipmentId = null;
        if (! empty($validated['tracking'])) {
            $shipmentId = Shipment::where('tracking_number', $validated['tracking'])->value('id');
        }

        SupportTicket::create([
            'user_id' => null,
            'shipment_id' => $shipmentId,
            'subject' => $validated['subject'],
            'category' => $categoryMap[$validated['category']] ?? 'other',
            'priority' => 'normal',
            'status' => 'open',
            'body' => $validated['message'],
        ]);

        return redirect()->route('support')->with('ticket_success', true);
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,pending,resolved,closed'],
        ]);

        $ticket->update(['status' => $validated['status']]);

        return redirect()->route('admin.support')->with('status_updated', true);
    }
}
