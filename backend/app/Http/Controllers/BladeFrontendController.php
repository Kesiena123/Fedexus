<?php

namespace App\Http\Controllers;

use App\Events\ShipmentUpdated;
use App\Models\AdminAuditLog;
use App\Models\AdminSetting;
use App\Models\EmailLog;
use App\Models\GuestChatConversation;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\PaymentSetting;
use App\Models\Shipment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AdminAuditLogger;
use App\Services\LocationCoordinateResolver;
use App\Services\MapPointService;
use App\Services\NotificationDispatchService;
use App\Services\PaymentGatewayManager;
use App\Services\PaymentStageService;
use App\Services\ShipmentReceiptService;
use App\Services\TrackingNumberService;
use App\Support\AdminPermissions;
use App\Support\AdminSecurity;
use App\Support\AppSettings;
use App\Support\ShipmentStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Picqer\Barcode\Barcode;
use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Throwable;

class BladeFrontendController extends Controller
{
    // ---- STATIC PAGES ----

    public function home()
    {
        return view('pages.home');
    }

    public function about()
    {
        return view('pages.about');
    }

    public function services()
    {
        return view('pages.services');
    }

    public function servicesInternational()
    {
        return view('pages.services-international');
    }

    public function pricing()
    {
        return view('pages.rates');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function support()
    {
        return view('pages.support');
    }

    public function careers()
    {
        return view('pages.careers');
    }

    public function blog()
    {
        return view('pages.blog');
    }

    public function faq()
    {
        return view('pages.faq');
    }

    public function shipping()
    {
        return redirect()->route('admin.login');
    }

    public function rates()
    {
        return view('pages.rates');
    }

    public function adminLogin(Request $request, AdminAuditLogger $audit, NotificationDispatchService $notifications)
    {
        if ($request->isMethod('post')) {
            $needsTwoFactor = $request->boolean('needs_two_factor');

            if ($needsTwoFactor) {
                $data = $request->validate([
                    'email' => ['required', 'email'],
                    'two_factor_code' => ['required', 'string', 'size:6'],
                ]);

                $user = User::where('email', $data['email'])->first();

                if (! $user || ! $user->two_factor_secret || ! Hash::check($data['two_factor_code'], $user->two_factor_secret)) {
                    return back()->withInput()->withErrors(['two_factor_code' => 'Invalid two-factor code.'])->with('requires_two_factor', true);
                }

                Auth::login($user);
                $user->forceFill(['last_login_at' => now()])->save();

                $audit->log($request, $user, 'admin.login', [
                    'target_type' => 'user',
                    'target_id' => $user->id,
                ]);

                return redirect()->intended(route('admin.dashboard'));
            }

            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
                'captcha_token' => ['nullable', 'string'],
                'captcha_answer' => ['nullable', 'integer'],
            ]);

            $captchaToken = $credentials['captcha_token'] ?? null;
            $captchaAnswer = $credentials['captcha_answer'] ?? null;
            if ($captchaToken && $captchaAnswer !== null) {
                $hashed = Cache::pull('admin-captcha:'.$captchaToken);
                if (! $hashed || ! Hash::check((string) $captchaAnswer, $hashed)) {
                    return back()->withErrors(['captcha_answer' => 'Invalid CAPTCHA response.'])->withInput();
                }
            }

            $rateLimitKey = 'admin-login:'.strtolower($credentials['email']).':'.$request->ip();

            if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
                return back()->withErrors(['email' => 'Too many attempts. Try again later.'])->withInput();
            }

            $user = User::where('email', $credentials['email'])->first();

            if (! $user || ! Hash::check($credentials['password'], $user->password) || ! AdminSecurity::isAdminRole($user->role)) {
                RateLimiter::hit($rateLimitKey, 900);

                return back()->withErrors(['email' => 'Invalid credentials or insufficient permissions.'])->withInput();
            }

            if ($user->is_suspended) {
                return back()->withErrors(['email' => 'This account is suspended.'])->withInput();
            }

            RateLimiter::clear($rateLimitKey);

            if ($user->two_factor_secret) {
                session()->flash('requires_two_factor', true);
                session()->flash('message', 'Enter your two-factor authentication code.');

                return back()->withInput();
            }

            Auth::login($user);
            $user->forceFill(['last_login_at' => now()])->save();

            $audit->log($request, $user, 'admin.login', [
                'target_type' => 'user',
                'target_id' => $user->id,
            ]);

            return redirect()->intended(route('admin.dashboard'));
        }

        return view('pages.admin-login');
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    }

    // ---- ADMIN PAGES ----

    public function adminDashboard(Request $request, TrackingNumberService $tracking, PaymentStageService $payments, AdminAuditLogger $audit, NotificationDispatchService $notifications)
    {
        $user = Auth::user();

        if ($request->isMethod('post')) {
            if ($request->query('approve')) {
                AdminPermissions::authorize($user, AdminPermissions::SHIPMENTS_APPROVE);
                $shipment = Shipment::findOrFail($request->query('approve'));
                abort_if($shipment->tracking_number, 422, 'Shipment already has a tracking number.');
                abort_unless(in_array($shipment->status, ['shipment_requested', 'admin_review'], true), 422, 'Shipment is not awaiting approval.');
                $prev = $shipment->toArray();
                $format = $request->query('format', 'FDX');
                $newTracking = $tracking->generate($format);
                $shipment->update([
                    'status' => 'approved',
                    'tracking_number' => $newTracking,
                ]);
                if (($shipment->metadata['workflow']['payment_enabled'] ?? true) !== false) {
                    $payments->createStages($shipment);
                }
                $shipment->trackingEvents()->create([
                    'status' => 'approved',
                    'location' => $shipment->origin_address['city'] ?? null,
                    'description' => 'Shipment approved by administrator and tracking number generated.',
                    'created_by' => $user->id,
                    'occurred_at' => now(),
                ]);
                $audit->log($request, $user, 'shipment.approved', [
                    'target_type' => 'shipment', 'target_id' => $shipment->id,
                    'previous_values' => $prev,
                    'new_values' => ['status' => 'approved', 'tracking_number' => $newTracking],
                ]);
                event(new ShipmentUpdated($shipment));

                return response()->json(['message' => 'Shipment approved.', 'tracking_number' => $shipment->tracking_number]);
            }
            if ($request->query('reject')) {
                AdminPermissions::authorize($user, AdminPermissions::SHIPMENTS_APPROVE);
                $shipment = Shipment::findOrFail($request->query('reject'));
                $prev = $shipment->toArray();
                $shipment->update([
                    'status' => 'rejected',
                    'metadata' => array_merge($shipment->metadata ?? [], ['rejection_reason' => 'Rejected by administrator.']),
                ]);
                $shipment->trackingEvents()->create([
                    'status' => 'rejected',
                    'location' => $shipment->origin_address['city'] ?? null,
                    'description' => 'Rejected by administrator.',
                    'created_by' => $user->id,
                    'occurred_at' => now(),
                ]);
                $audit->log($request, $user, 'shipment.rejected', [
                    'target_type' => 'shipment', 'target_id' => $shipment->id,
                    'previous_values' => $prev,
                    'new_values' => ['status' => 'rejected'],
                ]);
                event(new ShipmentUpdated($shipment));

                return response()->json(['message' => 'Shipment rejected.']);
            }
            if ($request->query('verify')) {
                AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);
                $payment = Payment::findOrFail($request->query('verify'));
                $payment->update(['status' => 'verified', 'verified_at' => now(), 'verified_by' => $user->id]);

                return response()->json(['message' => 'Payment verified.']);
            }
        }

        $metrics = [
            'shipments' => Shipment::count(),
            'total_shipments' => Shipment::count(),
            'pending_shipments' => Shipment::whereIn('status', ['shipment_requested', 'admin_review', 'approved', 'booked', 'pickup_scheduled'])->count(),
            'in_transit' => Shipment::whereNotNull('tracking_number')->whereIn('status', ['picked_up', 'warehouse_processing', 'international_processing', 'destination_hub', 'out_for_delivery'])->count(),
            'delivered' => Shipment::where('status', 'delivered')->count(),
            'cancelled' => Shipment::where('status', 'cancelled')->count(),
            'revenue' => Payment::whereIn('status', ['paid', 'verified'])->sum('amount'),
            'customers' => User::where('role', 'customer')->count(),
            'administrators' => User::whereIn('role', ['super_admin', 'admin', 'manager', 'support'])->count(),
            'pending_payment_amount' => Payment::where('status', '!=', 'paid')->sum('amount'),
            'open_tickets' => SupportTicket::whereIn('status', ['open', 'pending'])->count(),
        ];
        $metrics['active_shipments'] = $metrics['pending_shipments'] + $metrics['in_transit'];

        $approvalQueue = Shipment::with('user')
            ->whereNull('tracking_number')
            ->whereIn('status', ['shipment_requested', 'admin_review'])
            ->latest()
            ->limit(10)
            ->get();

        $trackedShipments = Shipment::with(['user', 'trackingEvents', 'payments'])
            ->whereNotNull('tracking_number')
            ->latest()
            ->limit(15)
            ->get();

        $paymentQueue = Payment::with('shipment')
            ->where('status', 'paid')
            ->latest()
            ->limit(10)
            ->get();

        $monthStarts = collect(range(5, 0))->map(fn ($offset) => now()->startOfMonth()->subMonths($offset));
        $chartData = [
            'months' => $monthStarts->map(fn (Carbon $date) => $date->format('M Y'))->values(),
            'shipments' => $monthStarts->map(fn (Carbon $date) => Shipment::whereBetween('created_at', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])->count())->values(),
            'revenue' => $monthStarts->map(fn (Carbon $date) => (float) Payment::whereIn('status', ['paid', 'verified'])->whereBetween('created_at', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])->sum('amount'))->values(),
            'status_labels' => ['Pending', 'In Transit', 'Delivered', 'Cancelled'],
            'status_values' => [$metrics['pending_shipments'], $metrics['in_transit'], $metrics['delivered'], $metrics['cancelled']],
        ];

        $topRoutes = Shipment::latest()
            ->get()
            ->groupBy(fn (Shipment $shipment) => ($shipment->origin_address['city'] ?? 'Unknown').' to '.($shipment->destination_address['city'] ?? 'Unknown'))
            ->map(fn ($rows, $route) => ['route' => $route, 'count' => $rows->count(), 'revenue' => $rows->sum('quoted_amount')])
            ->sortByDesc('count')
            ->take(5)
            ->values();

        $recentActivities = AdminAuditLog::with('admin')->latest()->limit(8)->get();

        $liveChatConversations = GuestChatConversation::with('messages')
            ->where('status', '!=', 'closed')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($conversation) {
                $lastMsg = $conversation->messages->last();
                $conversation->lastMsg = $lastMsg?->body;
                $conversation->needsReply = $lastMsg && $lastMsg->sender_type === 'guest';
                $conversation->guestDisplay = $conversation->guest_name ?: 'Guest';
                return $conversation;
            });

        $openChatCount = GuestChatConversation::where('status', '!=', 'closed')->count();

        return view('pages.admin-dashboard', compact('user', 'metrics', 'approvalQueue', 'trackedShipments', 'paymentQueue', 'chartData', 'topRoutes', 'recentActivities', 'liveChatConversations', 'openChatCount'));
    }

    // ---- ADMIN SUB-PAGES ----

    public function adminShipments(Request $request)
    {
        $user = Auth::user();
        $query = Shipment::with(['user', 'trackingEvents', 'payments']);

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($destination = trim((string) $request->get('destination'))) {
            $query->where('destination_address', 'like', "%{$destination}%");
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $shipments = $query->latest()->paginate(20)->withQueryString();
        $approvalQueue = Shipment::with('user')
            ->whereNull('tracking_number')
            ->whereIn('status', ['shipment_requested', 'admin_review'])
            ->latest()
            ->limit(10)
            ->get();
        $statuses = ShipmentStatus::databaseStatuses();

        return view('pages.admin-shipments', compact('user', 'approvalQueue', 'shipments', 'statuses'));
    }

    public function adminShipmentsExport(Request $request)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::SHIPMENTS_VIEW);

        $query = Shipment::with(['user', 'payments']);
        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%");
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($destination = trim((string) $request->get('destination'))) {
            $query->where('destination_address', 'like', "%{$destination}%");
        }
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $shipments = $query->latest()->get();
        $rows = [[
            'Tracking Number', 'Sender', 'Receiver', 'Origin', 'Destination', 'Shipment Type',
            'Status', 'Estimated Delivery', 'Shipment Date', 'Amount',
        ]];

        foreach ($shipments as $shipment) {
            $rows[] = [
                $shipment->tracking_number ?? 'REQ-'.$shipment->id,
                $shipment->sender_name,
                $shipment->recipient_name,
                trim(($shipment->origin_address['city'] ?? '').', '.($shipment->origin_address['country'] ?? ''), ', '),
                trim(($shipment->destination_address['city'] ?? '').', '.($shipment->destination_address['country'] ?? ''), ', '),
                str_replace('_', ' ', $shipment->service_level),
                str_replace('_', ' ', $shipment->status),
                optional($shipment->estimated_delivery_at)->format('Y-m-d'),
                $shipment->created_at->format('Y-m-d'),
                number_format((float) $shipment->quoted_amount, 2, '.', ''),
            ];
        }

        $csv = collect($rows)->map(fn ($row) => collect($row)->map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"')->implode(','))->implode("\n");

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="shipments-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    public function adminShipmentShow(Request $request, Shipment $shipment, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::SHIPMENTS_VIEW);

        if ($request->isMethod('post')) {
            AdminPermissions::authorize($user, AdminPermissions::SHIPMENTS_UPDATE);

            $data = $request->validate([
                'status' => ['required', 'string', 'max:80'],
                'location' => ['required', 'string', 'max:180'],
                'description' => ['required', 'string', 'max:2000'],
                'occurred_at' => ['required', 'date'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ]);
            $data['status'] = ShipmentStatus::normalizeForDatabase($data['status']);
            abort_unless(in_array($data['status'], ShipmentStatus::databaseStatuses(), true), 422, 'Unsupported shipment status.');

            $previous = $shipment->only(['status', 'delivered_at']);
            $shipment->update([
                'status' => $data['status'],
                'delivered_at' => $data['status'] === 'delivered' ? Carbon::parse($data['occurred_at']) : $shipment->delivered_at,
            ]);

            $event = $shipment->trackingEvents()->create([
                'status' => $data['status'],
                'location' => $data['location'],
                'description' => $data['description'],
                'created_by' => $user->id,
                'occurred_at' => Carbon::parse($data['occurred_at']),
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);
            $this->storeRoutePointFromTrackingData($shipment, $data, $user->id);

            $audit->log($request, $user, 'shipment.tracking_event.created', [
                'target_type' => 'tracking_event',
                'target_id' => $event->id,
                'previous_values' => $previous,
                'new_values' => ['status' => $shipment->status, 'location' => $event->location],
            ]);

            event(new ShipmentUpdated($shipment));

            return back()->with('success', 'Tracking update added.');
        }

        $shipment->load(['user', 'trackingEvents', 'payments', 'attachments', 'driver', 'warehouse', 'routePoints']);
        $statuses = ShipmentStatus::databaseStatuses();

        $mapPoints = app(MapPointService::class)->buildForShipment($shipment);
        $mapSettings = [
            'defaultLatitude' => (float) AppSettings::mapDefaultLatitude(),
            'defaultLongitude' => (float) AppSettings::mapDefaultLongitude(),
            'zoom' => (int) AppSettings::mapZoom(),
        ];

        return view('pages.admin-shipment-show', compact('user', 'shipment', 'statuses', 'mapPoints', 'mapSettings'));
    }

    public function adminShipmentEdit(Request $request, Shipment $shipment, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::SHIPMENTS_VIEW);

        if ($request->isMethod('post')) {
            AdminPermissions::authorize($user, AdminPermissions::SHIPMENTS_UPDATE);

            $data = $request->validate([
                'sender_name' => ['required', 'string', 'max:140'],
                'sender_company' => ['nullable', 'string', 'max:140'],
                'sender_phone' => ['nullable', 'string', 'max:40'],
                'sender_email' => ['nullable', 'email', 'max:120'],
                'sender_country' => ['nullable', 'string', 'max:100'],
                'sender_state' => ['nullable', 'string', 'max:100'],
                'sender_city' => ['nullable', 'string', 'max:100'],
                'sender_address' => ['nullable', 'string', 'max:200'],
                'sender_postal' => ['nullable', 'string', 'max:20'],

                'recipient_name' => ['required', 'string', 'max:140'],
                'recipient_company' => ['nullable', 'string', 'max:140'],
                'recipient_phone' => ['nullable', 'string', 'max:40'],
                'recipient_email' => ['nullable', 'email', 'max:120'],
                'recipient_country' => ['nullable', 'string', 'max:100'],
                'recipient_state' => ['nullable', 'string', 'max:100'],
                'recipient_city' => ['nullable', 'string', 'max:100'],
                'recipient_address' => ['nullable', 'string', 'max:200'],
                'recipient_postal' => ['nullable', 'string', 'max:20'],

                'service_level' => ['required', 'string', 'max:80'],
                'shipment_description' => ['nullable', 'string', 'max:2000'],
                'estimated_delivery_at' => ['nullable', 'date'],
                'priority' => ['nullable', 'string', 'max:40'],
                'status' => ['required', 'string', 'max:80'],

                'package_type' => ['nullable', 'string', 'max:80'],
                'weight_kg' => ['nullable', 'numeric', 'min:0'],
                'length_cm' => ['nullable', 'numeric', 'min:0'],
                'width_cm' => ['nullable', 'numeric', 'min:0'],
                'height_cm' => ['nullable', 'numeric', 'min:0'],
                'quantity' => ['nullable', 'integer', 'min:1'],
                'package_description' => ['nullable', 'string', 'max:1000'],

                'shipping_cost' => ['nullable', 'numeric', 'min:0'],
                'handling_fee' => ['nullable', 'numeric', 'min:0'],
                'insurance' => ['nullable', 'numeric', 'min:0'],
                'customs_fee' => ['nullable', 'numeric', 'min:0'],
                'tax' => ['nullable', 'numeric', 'min:0'],
                'discount' => ['nullable', 'numeric', 'min:0'],
                'additional_charges' => ['nullable', 'numeric', 'min:0'],

                'origin_latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'origin_longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'destination_latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'destination_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ]);

            $data['status'] = ShipmentStatus::normalizeForDatabase($data['status']);
            abort_unless(in_array($data['status'], ShipmentStatus::databaseStatuses(), true), 422, 'Unsupported shipment status.');

            $existingOrigin = $shipment->origin_address;
            $existingDest = $shipment->destination_address;

            $originAddress = [
                'address' => $data['sender_address'] ?? ($existingOrigin['address'] ?? ''),
                'city' => $data['sender_city'] ?? ($existingOrigin['city'] ?? ''),
                'state' => $data['sender_state'] ?? ($existingOrigin['state'] ?? ''),
                'country' => $data['sender_country'] ?? ($existingOrigin['country'] ?? ''),
                'postal_code' => $data['sender_postal'] ?? ($existingOrigin['postal_code'] ?? ''),
            ];
            if (! empty($data['origin_latitude']) && ! empty($data['origin_longitude'])) {
                $originAddress['latitude'] = $data['origin_latitude'];
                $originAddress['longitude'] = $data['origin_longitude'];
            }

            $destinationAddress = [
                'address' => $data['recipient_address'] ?? ($existingDest['address'] ?? ''),
                'city' => $data['recipient_city'] ?? ($existingDest['city'] ?? ''),
                'state' => $data['recipient_state'] ?? ($existingDest['state'] ?? ''),
                'country' => $data['recipient_country'] ?? ($existingDest['country'] ?? ''),
                'postal_code' => $data['recipient_postal'] ?? ($existingDest['postal_code'] ?? ''),
            ];
            if (! empty($data['destination_latitude']) && ! empty($data['destination_longitude'])) {
                $destinationAddress['latitude'] = $data['destination_latitude'];
                $destinationAddress['longitude'] = $data['destination_longitude'];
            }

            $shippingCost = (float) ($data['shipping_cost'] ?? 0);
            $handlingFee = (float) ($data['handling_fee'] ?? 0);
            $insurance = (float) ($data['insurance'] ?? 0);
            $customsFee = (float) ($data['customs_fee'] ?? 0);
            $tax = (float) ($data['tax'] ?? 0);
            $discount = (float) ($data['discount'] ?? 0);
            $additionalCharges = (float) ($data['additional_charges'] ?? 0);
            $totalAmount = $shippingCost + $handlingFee + $insurance + $customsFee + $tax + $additionalCharges - $discount;

            $metadata = $shipment->metadata ?? [];
            $existingMeta = $metadata;
            $metadata['sender'] = [
                'company' => $data['sender_company'] ?? ($existingMeta['sender']['company'] ?? ''),
                'phone' => $data['sender_phone'] ?? ($existingMeta['sender']['phone'] ?? ''),
                'email' => $data['sender_email'] ?? ($existingMeta['sender']['email'] ?? ''),
            ];
            $metadata['receiver'] = [
                'company' => $data['recipient_company'] ?? ($existingMeta['receiver']['company'] ?? ''),
                'phone' => $data['recipient_phone'] ?? ($existingMeta['receiver']['phone'] ?? ''),
                'email' => $data['recipient_email'] ?? ($existingMeta['receiver']['email'] ?? ''),
            ];
            $metadata['description'] = $data['shipment_description'] ?? ($existingMeta['description'] ?? '');
            $metadata['priority'] = $data['priority'] ?? ($existingMeta['priority'] ?? '');
            $metadata['cost'] = [
                'shipping_cost' => $shippingCost,
                'handling_fee' => $handlingFee,
                'insurance' => $insurance,
                'customs_fee' => $customsFee,
                'tax' => $tax,
                'discount' => $discount,
                'additional_charges' => $additionalCharges,
            ];
            $metadata['packages'] = [[
                'package_type' => $data['package_type'] ?? '',
                'weight_kg' => $data['weight_kg'] ?? 0,
                'length_cm' => $data['length_cm'] ?? 0,
                'width_cm' => $data['width_cm'] ?? 0,
                'height_cm' => $data['height_cm'] ?? 0,
                'quantity' => (int) ($data['quantity'] ?? 1),
                'description' => $data['package_description'] ?? '',
            ]];

            $previousValues = $shipment->toArray();

            $shipment->update([
                'sender_name' => $data['sender_name'],
                'recipient_name' => $data['recipient_name'],
                'origin_address' => $originAddress,
                'destination_address' => $destinationAddress,
                'service_level' => $data['service_level'],
                'weight_kg' => $data['weight_kg'] ?? $shipment->weight_kg,
                'quoted_amount' => max(0, $totalAmount),
                'estimated_delivery_at' => $data['estimated_delivery_at'] ?? $shipment->estimated_delivery_at,
                'status' => $data['status'],
                'metadata' => $metadata,
            ]);

            if (! empty($data['origin_latitude']) && ! empty($data['origin_longitude'])) {
                $shipment->routePoints()->updateOrCreate(
                    ['shipment_id' => $shipment->id, 'type' => 'origin'],
                    [
                        'sort_order' => 1,
                        'label' => 'Origin',
                        'location' => trim($data['sender_city'].', '.$data['sender_country'], ', '),
                        'latitude' => $data['origin_latitude'],
                        'longitude' => $data['origin_longitude'],
                        'description' => 'Shipment origin',
                        'created_by' => $user->id,
                    ]
                );
            }

            if (! empty($data['destination_latitude']) && ! empty($data['destination_longitude'])) {
                $shipment->routePoints()->updateOrCreate(
                    ['shipment_id' => $shipment->id, 'type' => 'destination'],
                    [
                        'sort_order' => 100,
                        'label' => 'Destination',
                        'location' => trim($data['recipient_city'].', '.$data['recipient_country'], ', '),
                        'latitude' => $data['destination_latitude'],
                        'longitude' => $data['destination_longitude'],
                        'description' => 'Shipment destination',
                        'created_by' => $user->id,
                    ]
                );
            }

            $shipment->trackingEvents()->create([
                'status' => $shipment->status,
                'location' => $data['sender_city'] ?? null,
                'description' => 'Shipment details updated by administrator.',
                'created_by' => $user->id,
                'occurred_at' => now(),
            ]);

            $audit->log($request, $user, 'shipment.updated', [
                'target_type' => 'shipment',
                'target_id' => $shipment->id,
                'previous_values' => $previousValues,
                'new_values' => $shipment->toArray(),
            ]);

            event(new ShipmentUpdated($shipment));

            return redirect()->route('admin.shipments.edit', $shipment)->with('success', 'Shipment updated successfully.');
        }

        $shipment->load(['trackingEvents', 'payments', 'attachments', 'routePoints']);
        $statuses = ShipmentStatus::databaseStatuses();
        $metadata = $shipment->metadata ?? [];
        $senderMeta = $metadata['sender'] ?? [];
        $receiverMeta = $metadata['receiver'] ?? [];
        $costMeta = $metadata['cost'] ?? [];
        $packages = $metadata['packages'] ?? [];
        $firstPackage = $packages[0] ?? [];
        $originAddr = $shipment->origin_address ?? [];
        $destAddr = $shipment->destination_address ?? [];

        return view('pages.admin-shipment-edit', compact(
            'user', 'shipment', 'statuses', 'metadata',
            'senderMeta', 'receiverMeta', 'costMeta', 'firstPackage',
            'originAddr', 'destAddr'
        ));
    }

    public function adminShipmentDelete(Request $request, Shipment $shipment, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::SHIPMENTS_DELETE);

        $previous = $shipment->toArray();
        $shipment->delete();
        $audit->log($request, $user, 'shipment.deleted', [
            'target_type' => 'shipment',
            'target_id' => $shipment->id,
            'previous_values' => $previous,
        ]);

        return redirect()->route('admin.shipments')->with('success', 'Shipment deleted.');
    }

    public function adminShipmentReceipt(Shipment $shipment)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::SHIPMENTS_VIEW);

        $shipment->load(['trackingEvents', 'payments', 'routePoints', 'warehouse', 'paymentRequests.transactions', 'paymentRequests.paymentProofs']);

        $receipt = app(ShipmentReceiptService::class);
        $parsed = $receipt->parseMeta($shipment);
        $totalCost = $receipt->calculateTotalCost($shipment, $parsed['costs']);

        $paymentRequests = $shipment->paymentRequests;
        $completedPaymentRequest = $paymentRequests->filter(fn ($pr) => $pr->status === 'paid')->first();
        $pendingPaymentRequest = $paymentRequests->filter(fn ($pr) => !in_array($pr->status, ['paid', 'cancelled']))->first();

        return view('pages.receipt', array_merge(
            compact('shipment', 'user', 'totalCost', 'paymentRequests', 'completedPaymentRequest', 'pendingPaymentRequest'), $parsed
        ));
    }

    public function adminShipmentReceiptPdf(Shipment $shipment)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::SHIPMENTS_VIEW);

        $shipment->load(['trackingEvents', 'payments', 'routePoints', 'warehouse']);

        $receipt = app(ShipmentReceiptService::class);
        $parsed = $receipt->parseMeta($shipment);
        $totalCost = $receipt->calculateTotalCost($shipment, $parsed['costs']);

        $trackingUrl = route('tracking.number', $shipment->tracking_number);
        $barcodeHtml = $this->generateBarcodeTable($shipment->tracking_number);

        $pdf = Pdf::loadView('pages.receipt-pdf', array_merge(
            compact('shipment', 'user', 'totalCost', 'trackingUrl', 'barcodeHtml'), $parsed
        ));
        $pdf->setPaper('a4');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf->download("receipt-{$shipment->tracking_number}.pdf");
    }

    public function adminCreateShipment()
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::SHIPMENTS_CREATE);

        $countries = config('shipment.countries', []);
        $serviceLevels = config('shipment.service_levels', []);
        $warehouses = Warehouse::orderBy('name')->get();
        $drivers = User::whereIn('role', ['driver', 'warehouse', 'support', 'manager', 'admin'])->get(['id', 'name', 'role']);

        $sampleData = $this->generateShipmentSampleData($countries);

        return view('pages.admin-create-shipment', compact('user', 'countries', 'serviceLevels', 'warehouses', 'drivers', 'sampleData'));
    }

    private function generateBarcodeTable(string $code, int $barWidth = 2, int $height = 30, string $color = '#0f172a'): string
    {
        $generator = new class extends BarcodeGeneratorSVG
        {
            public function getBarcodeData(string $code, string $type): Barcode
            {
                return parent::getBarcodeData($code, $type);
            }
        };

        $barcodeData = $generator->getBarcodeData($code, BarcodeGenerator::TYPE_CODE_128);

        $totalWidth = 0;
        $bars = [];
        foreach ($barcodeData->getBars() as $bar) {
            $w = round($bar->getWidth() * $barWidth, 1);
            $bars[] = ['width' => $w, 'isBar' => $bar->isBar()];
            $totalWidth += $w;
        }

        $html = '<table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; width:'.$totalWidth.'px; height:'.$height.'px;">';
        $html .= '<tr>';
        foreach ($bars as $bar) {
            if ($bar['width'] > 0) {
                $bg = $bar['isBar'] ? $color : '#ffffff';
                $html .= '<td style="width:'.$bar['width'].'px; height:'.$height.'px; background-color:'.$bg.'; margin:0; padding:0; border:none; font-size:0; line-height:0;">&nbsp;</td>';
            }
        }
        $html .= '</tr></table>';

        return $html;
    }

    private function generateShipmentSampleData(array $countries): array
    {
        $firstNames = ['James', 'Maria', 'Chen', 'Olga', 'Ahmed', 'Sofia', 'Liam', 'Yuki', 'Carlos', 'Fatima', 'Noah', 'Aisha', 'Ethan', 'Priya', 'Omar', 'Emma', 'Tariq', 'Lucia', 'Diego', 'Hana'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin'];
        $streets = ['123 Main St', '456 Oak Ave', '789 Pine Rd', '321 Elm Blvd', '654 Maple Dr', '987 Cedar Ln', '147 Birch Way', '258 Walnut Ct', '369 Spruce Pl', '741 Ash Ter'];
        $companies = ['GlobalTech Solutions', 'Summit Industries', 'Pacific Trading Co', 'Atlas Logistics', 'Vertex Manufacturing', 'Orion Enterprises', 'Horizon Supply', 'Pinnacle Goods', 'Evergreen Corp', 'Sterling Distributors'];
        $cities = [
            'New York' => ['state' => 'New York', 'country' => 'United States (US)', 'postal' => '10001'],
            'Los Angeles' => ['state' => 'California', 'country' => 'United States (US)', 'postal' => '90001'],
            'Chicago' => ['state' => 'Illinois', 'country' => 'United States (US)', 'postal' => '60601'],
            'London' => ['state' => 'England', 'country' => 'United Kingdom (GB)', 'postal' => 'EC1A 1BB'],
            'Manchester' => ['state' => 'England', 'country' => 'United Kingdom (GB)', 'postal' => 'M1 1AE'],
            'Dubai' => ['state' => 'Dubai', 'country' => 'United Arab Emirates (AE)', 'postal' => '00000'],
            'Tokyo' => ['state' => 'Tokyo', 'country' => 'Japan (JP)', 'postal' => '100-0001'],
            'Lagos' => ['state' => 'Lagos', 'country' => 'Nigeria (NG)', 'postal' => '100001'],
            'São Paulo' => ['state' => 'São Paulo', 'country' => 'Brazil (BR)', 'postal' => '01000-000'],
            'Toronto' => ['state' => 'Ontario', 'country' => 'Canada (CA)', 'postal' => 'M5H 2N2'],
            'Mumbai' => ['state' => 'Maharashtra', 'country' => 'India (IN)', 'postal' => '400001'],
            'Sydney' => ['state' => 'New South Wales', 'country' => 'Australia (AU)', 'postal' => '2000'],
            'Nairobi' => ['state' => 'Nairobi', 'country' => 'Kenya (KE)', 'postal' => '00100'],
        ];
        $serviceKeys = array_keys(config('shipment.service_levels', []));
        $packageTypes = ['Parcel', 'Box', 'Pallet', 'Envelope', 'Tube'];
        $shipmentTypes = ['domestic', 'international', 'express', 'freight'];
        $shippingMethods = ['air', 'sea', 'ground'];
        $paymentMethods = ['credit_card', 'bank_transfer', 'cash_on_delivery', 'invoice', 'prepaid'];

        $randCity = function () use ($cities) {
            $keys = array_keys($cities);
            $key = $keys[array_rand($keys)];

            return array_merge(['city' => $key], $cities[$key]);
        };

        $senderCity = $randCity();
        $receiverCity = $randCity();

        $senderNames = $firstNames[array_rand($firstNames)].' '.$lastNames[array_rand($lastNames)];
        $receiverNames = $firstNames[array_rand($firstNames)].' '.$lastNames[array_rand($lastNames)];

        $senderEmail = strtolower(str_replace(' ', '.', $senderNames)).'@'.['gmail.com', 'outlook.com', 'yahoo.com', 'company.com'][array_rand([0, 1, 2, 3])];
        $receiverEmail = strtolower(str_replace(' ', '.', $receiverNames)).'@'.['gmail.com', 'outlook.com', 'yahoo.com', 'company.com'][array_rand([0, 1, 2, 3])];

        $weight = round(mt_rand(5, 850) / 10, 1);
        $length = mt_rand(15, 120);
        $width = mt_rand(10, 80);
        $height = mt_rand(8, 60);
        $quantity = mt_rand(1, 8);
        $baseCost = round(mt_rand(2500, 45000) / 100, 2);

        $estDate = new \DateTime;
        $estDate->modify('+'.mt_rand(3, 21).' days');

        return [
            'sender_full_name' => $senderNames,
            'sender_company' => $companies[array_rand($companies)],
            'sender_email' => $senderEmail,
            'sender_phone' => '+1-'.mt_rand(200, 999).'-'.mt_rand(100, 999).'-'.mt_rand(1000, 9999),
            'sender_country' => $senderCity['country'],
            'sender_state' => $senderCity['state'],
            'sender_city' => $senderCity['city'],
            'sender_address' => $streets[array_rand($streets)],
            'sender_postal_code' => $senderCity['postal'],

            'receiver_full_name' => $receiverNames,
            'receiver_company' => $companies[array_rand($companies)],
            'receiver_email' => $receiverEmail,
            'receiver_phone' => '+1-'.mt_rand(200, 999).'-'.mt_rand(100, 999).'-'.mt_rand(1000, 9999),
            'receiver_country' => $receiverCity['country'],
            'receiver_state' => $receiverCity['state'],
            'receiver_city' => $receiverCity['city'],
            'receiver_address' => $streets[array_rand($streets)],
            'receiver_postal_code' => $receiverCity['postal'],

            'service_level' => $serviceKeys[array_rand($serviceKeys)],
            'shipment_type' => $shipmentTypes[array_rand($shipmentTypes)],
            'shipping_method' => $shippingMethods[array_rand($shippingMethods)],
            'priority' => ['standard', 'standard', 'standard', 'high', 'urgent'][array_rand([0, 1, 2, 3, 4])],
            'estimated_delivery_date' => $estDate->format('Y-m-d'),
            'description' => 'Auto-generated test shipment - '.ucwords($packageTypes[array_rand($packageTypes)]).' shipment',

            'package_type' => $packageTypes[array_rand($packageTypes)],
            'package_weight' => $weight,
            'package_length' => $length,
            'package_width' => $width,
            'package_height' => $height,
            'package_quantity' => $quantity,
            'package_description' => 'Test parcel contents - auto-generated for testing',

            'shipping_cost' => $baseCost,
            'handling_fee' => round(mt_rand(500, 3000) / 100, 2),
            'insurance_fee' => round(mt_rand(200, 1500) / 100, 2),
            'customs_fee' => round(mt_rand(0, 2000) / 100, 2),
            'tax' => round(mt_rand(0, 1000) / 100, 2),
            'discount' => 0,
            'additional_charges' => 0,

            'payment_method' => $paymentMethods[array_rand($paymentMethods)],
            'payment_status' => 'pending',
        ];
    }

    public function adminStoreShipment(Request $request)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::SHIPMENTS_CREATE);

        $data = $request->validate([
            'sender_full_name' => ['required', 'string', 'max:140'],
            'sender_company' => ['nullable', 'string', 'max:140'],
            'sender_email' => ['required', 'email', 'max:120'],
            'sender_phone' => ['required', 'string', 'max:40'],
            'sender_country' => ['required', 'string', 'max:100'],
            'sender_state' => ['nullable', 'string', 'max:100'],
            'sender_city' => ['required', 'string', 'max:100'],
            'sender_address' => ['required', 'string', 'max:200'],
            'sender_postal_code' => ['nullable', 'string', 'max:20'],

            'receiver_full_name' => ['required', 'string', 'max:140'],
            'receiver_company' => ['nullable', 'string', 'max:140'],
            'receiver_email' => ['required', 'email', 'max:120'],
            'receiver_phone' => ['required', 'string', 'max:40'],
            'receiver_country' => ['required', 'string', 'max:100'],
            'receiver_state' => ['nullable', 'string', 'max:100'],
            'receiver_city' => ['required', 'string', 'max:100'],
            'receiver_address' => ['required', 'string', 'max:200'],
            'receiver_postal_code' => ['nullable', 'string', 'max:20'],

            'service_level' => ['required', 'string', 'max:60'],
            'shipment_type' => ['nullable', 'string', 'max:60'],
            'shipping_method' => ['nullable', 'string', 'max:60'],
            'priority' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
            'estimated_delivery_date' => ['nullable', 'date'],

            'package_type' => ['required', 'string', 'max:60'],
            'package_weight' => ['required', 'numeric', 'min:0.1'],
            'package_length' => ['required', 'numeric', 'min:0'],
            'package_width' => ['required', 'numeric', 'min:0'],
            'package_height' => ['required', 'numeric', 'min:0'],
            'package_quantity' => ['required', 'integer', 'min:1'],
            'package_description' => ['nullable', 'string', 'max:500'],

            'shipping_cost' => ['required', 'numeric', 'min:0'],
            'handling_fee' => ['nullable', 'numeric', 'min:0'],
            'insurance_fee' => ['nullable', 'numeric', 'min:0'],
            'customs_fee' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'additional_charges' => ['nullable', 'numeric', 'min:0'],

            'payment_method' => ['nullable', 'string', 'max:60'],
            'payment_status' => ['nullable', 'string', 'max:30'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'driver_id' => ['nullable', 'integer', 'exists:users,id'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $shipment = DB::transaction(function () use ($data, $request, $user) {
            $trackingService = app(TrackingNumberService::class);
            $trackingNumber = $trackingService->generate();

            $handlingFee = (float) ($data['handling_fee'] ?? 0);
            $insuranceFee = (float) ($data['insurance_fee'] ?? 0);
            $customsFee = (float) ($data['customs_fee'] ?? 0);
            $tax = (float) ($data['tax'] ?? 0);
            $discount = (float) ($data['discount'] ?? 0);
            $additionalCharges = (float) ($data['additional_charges'] ?? 0);
            $shippingCost = (float) $data['shipping_cost'];
            $totalCost = $shippingCost + $handlingFee + $insuranceFee + $customsFee + $tax + $additionalCharges - $discount;

            $originAddress = [
                'street' => $data['sender_address'],
                'city' => $data['sender_city'],
                'state' => $data['sender_state'] ?? '',
                'postal_code' => $data['sender_postal_code'] ?? '',
                'country' => $data['sender_country'],
                'email' => $data['sender_email'],
                'phone' => $data['sender_phone'],
                'company' => $data['sender_company'] ?? '',
            ];
            $destinationAddress = [
                'street' => $data['receiver_address'],
                'city' => $data['receiver_city'],
                'state' => $data['receiver_state'] ?? '',
                'postal_code' => $data['receiver_postal_code'] ?? '',
                'country' => $data['receiver_country'],
                'email' => $data['receiver_email'],
                'phone' => $data['receiver_phone'],
                'company' => $data['receiver_company'] ?? '',
            ];

            $resolver = app(LocationCoordinateResolver::class);
            $originCoords = $resolver->resolveAddress($originAddress);
            $destinationCoords = $resolver->resolveAddress($destinationAddress);
            if ($originCoords) {
                $originAddress = array_merge($originAddress, $originCoords);
            }
            if ($destinationCoords) {
                $destinationAddress = array_merge($destinationAddress, $destinationCoords);
            }

            $totalWeight = (float) $data['package_weight'] * (int) $data['package_quantity'];
            $declaredValue = $totalCost;

            $warehouseId = $data['warehouse_id'] ?? null;
            $driverId = $data['driver_id'] ?? null;

            $metadata = [
                'workflow' => [
                    'service_level' => $data['service_level'],
                    'shipment_type' => $data['shipment_type'] ?? '',
                    'shipping_method' => $data['shipping_method'] ?? '',
                    'priority' => $data['priority'] ?? 'standard',
                ],
                'costs' => [
                    'shipping_cost' => $shippingCost,
                    'handling_fee' => $handlingFee,
                    'insurance_fee' => $insuranceFee,
                    'customs_fee' => $customsFee,
                    'tax' => $tax,
                    'discount' => $discount,
                    'additional_charges' => $additionalCharges,
                    'total_cost' => $totalCost,
                    'currency' => \App\Support\AppSettings::currency(),
                ],
                'package' => [
                    'type' => $data['package_type'],
                    'weight_kg' => (float) $data['package_weight'],
                    'length_cm' => (float) $data['package_length'],
                    'width_cm' => (float) $data['package_width'],
                    'height_cm' => (float) $data['package_height'],
                    'quantity' => (int) $data['package_quantity'],
                    'description' => $data['package_description'] ?? '',
                ],
                'payment' => [
                    'method' => $data['payment_method'] ?? '',
                    'status' => $data['payment_status'] ?? 'pending',
                ],
            ];

            $shipment = Shipment::create([
                'user_id' => $user->id,
                'driver_id' => $driverId,
                'warehouse_id' => $warehouseId,
                'tracking_number' => $trackingNumber,
                'status' => 'booked',
                'service_level' => $data['service_level'],
                'sender_name' => $data['sender_full_name'],
                'recipient_name' => $data['receiver_full_name'],
                'origin_address' => $originAddress,
                'destination_address' => $destinationAddress,
                'weight_kg' => $totalWeight,
                'declared_value' => $declaredValue,
                'quoted_amount' => $totalCost,
                'estimated_delivery_at' => ! empty($data['estimated_delivery_date']) ? $data['estimated_delivery_date'] : null,
                'metadata' => $metadata,
            ]);

            $shipment->trackingEvents()->create([
                'status' => 'booked',
                'location' => $data['sender_city'],
                'description' => $data['description'] ?: 'Shipment created by administrator.',
                'created_by' => $user->id,
                'occurred_at' => now(),
                'latitude' => $originCoords['latitude'] ?? null,
                'longitude' => $originCoords['longitude'] ?? null,
                'country_code' => substr($data['sender_country'], 0, 2),
                'checkpoint_label' => 'Current Location',
            ]);

            $shipment->routePoints()->createMany([
                [
                    'sort_order' => 1,
                    'type' => 'origin',
                    'label' => 'Origin',
                    'location' => trim($data['sender_city'].', '.$data['sender_country'], ', '),
                    'latitude' => $originCoords['latitude'] ?? null,
                    'longitude' => $originCoords['longitude'] ?? null,
                    'country_code' => substr($data['sender_country'], 0, 2),
                    'description' => 'Shipment origin.',
                    'created_by' => $user->id,
                ],
                [
                    'sort_order' => 100,
                    'type' => 'destination',
                    'label' => 'Destination',
                    'location' => trim($data['receiver_city'].', '.$data['receiver_country'], ', '),
                    'latitude' => $destinationCoords['latitude'] ?? null,
                    'longitude' => $destinationCoords['longitude'] ?? null,
                    'country_code' => substr($data['receiver_country'], 0, 2),
                    'description' => 'Shipment destination.',
                    'created_by' => $user->id,
                ],
            ]);

            $paymentStatus = $data['payment_status'] ?? 'pending';
            $paymentLabels = [
                1 => 'Booking Deposit',
                2 => 'Pickup Confirmation',
                3 => 'International Processing',
                4 => 'Destination Hub Processing',
                5 => 'Final Delivery Release',
            ];
            foreach ($paymentLabels as $stage => $label) {
                Payment::create([
                    'shipment_id' => $shipment->id,
                    'stage' => $stage,
                    'label' => $label,
                    'percentage' => 20,
                    'amount' => round($totalCost * 0.2, 2),
                    'status' => $stage === 1 ? $paymentStatus : 'locked',
                    'unlocked_at' => $stage === 1 ? now() : null,
                ]);
            }

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    if ($photo->isValid()) {
                        $path = $photo->store('shipment-attachments/'.$shipment->id, 'public');
                        $shipment->attachments()->create([
                            'uploaded_by' => $user->id,
                            'category' => 'package_image',
                            'original_name' => $photo->getClientOriginalName(),
                            'stored_name' => $photo->hashName(),
                            'disk' => 'public',
                            'path' => $path,
                            'mime_type' => $photo->getMimeType(),
                            'size_bytes' => $photo->getSize(),
                            'metadata' => ['visible_to_customer' => true],
                        ]);
                    }
                }
            }

            return $shipment->fresh(['trackingEvents', 'routePoints', 'payments', 'attachments']);
        });

        return redirect()->route('admin.shipments.show', $shipment)
            ->with('success', "Shipment created successfully. Tracking: {$shipment->tracking_number}");
    }

    public function adminTracking(Request $request)
    {
        $user = Auth::user();
        $query = Shipment::with(['user', 'trackingEvents', 'payments'])
            ->whereNotNull('tracking_number');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $trackedShipments = $query->latest()->paginate($request->get('per_page', 25));

        $statuses = ShipmentStatus::databaseStatuses();

        return view('pages.admin-tracking', compact('user', 'trackedShipments', 'statuses', 'search', 'status'));
    }

    public function adminPayments(Request $request, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        if ($request->isMethod('post')) {
            if ($request->input('action') === 'create_payment_request') {
                $data = $request->validate([
                    'shipment_id' => ['required', 'exists:shipments,id'],
                    'title' => ['required', 'string', 'max:180'],
                    'reason' => ['required', 'string', 'max:3000'],
                    'amount' => ['required', 'numeric', 'min:0.01'],
                    'currency' => ['required', 'string', 'max:8'],
                    'allowed_methods' => ['nullable', 'array'],
                    'allowed_methods.*' => ['string', 'max:80'],
                    'due_at' => ['nullable', 'date'],
                ]);

                $allowedMethods = $data['allowed_methods'] ?? [];
                $allowedMethods = array_values(array_filter($allowedMethods));
                if (! empty($allowedMethods)) {
                    foreach ($allowedMethods as $method) {
                        abort_unless(
                            PaymentSetting::where('gateway_name', $method)->where('is_active', true)->exists(),
                            422,
                            "The payment method '{$method}' is not enabled."
                        );
                    }
                }

                $metadata = [];
                if (! empty($allowedMethods)) {
                    $metadata['allowed_methods'] = $allowedMethods;
                }

                $paymentRequest = PaymentRequest::create([
                    'shipment_id' => $data['shipment_id'],
                    'title' => $data['title'],
                    'reason' => $data['reason'],
                    'amount' => $data['amount'],
                    'currency' => strtoupper($data['currency']),
                    'requested_method' => count($allowedMethods) === 1 ? $allowedMethods[0] : null,
                    'metadata' => $metadata,
                    'secure_token' => Str::random(64),
                    'due_at' => $data['due_at'] ?? null,
                    'created_by' => $user->id,
                ]);

                $audit->log($request, $user, 'payment_request.created', [
                    'target_type' => 'payment_request',
                    'target_id' => $paymentRequest->id,
                    'new_values' => $paymentRequest->only(['shipment_id', 'title', 'amount', 'currency', 'requested_method']),
                ]);

                return back()->with('success', 'Payment request created: '.route('payment-request.show', $paymentRequest->secure_token));
            }

            $data = $request->validate([
                'payment_id' => ['required', 'exists:payments,id'],
                'status' => ['required', 'in:payment_required,payment_initiated,awaiting_verification,paid,verified,rejected,failed,refunded,cancelled'],
            ]);

            $payment = Payment::with('shipment')->findOrFail($data['payment_id']);
            $previous = $payment->only(['status', 'verified_at', 'verified_by']);
            $payment->update([
                'status' => $data['status'],
                'verified_at' => $data['status'] === 'verified' ? now() : $payment->verified_at,
                'verified_by' => $data['status'] === 'verified' ? $user->id : $payment->verified_by,
            ]);

            $audit->log($request, $user, 'payment.status.updated', [
                'target_type' => 'payment',
                'target_id' => $payment->id,
                'previous_values' => $previous,
                'new_values' => $payment->only(['status', 'verified_at', 'verified_by']),
            ]);

            return back()->with('success', 'Payment status updated.');
        }

        $query = Payment::with(['shipment.user']);
        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('provider_reference', 'like', "%{$search}%")
                    ->orWhereHas('shipment', fn ($shipment) => $shipment->where('tracking_number', 'like', "%{$search}%"));
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($method = $request->get('method')) {
            $query->where('provider', $method);
        }

        $payments = $query->latest()->paginate(25)->withQueryString();
        $paymentRequests = PaymentRequest::with('shipment')->latest()->limit(20)->get();
        $requestShipments = Shipment::whereNotNull('tracking_number')->latest()->limit(100)->get(['id', 'tracking_number', 'recipient_name']);
        $activeGateways = PaymentSetting::where('is_active', true)->orderBy('gateway_name')->get();
        $allGateways = PaymentSetting::orderBy('gateway_name')->get();
        $paymentStatuses = ['locked', 'pending', 'checkout_created', 'paid', 'verified', 'failed', 'rejected', 'refunded'];
        $methods = Payment::whereNotNull('provider')->distinct()->pluck('provider')->filter()->values();

        return view('pages.admin-payments', compact('user', 'payments', 'paymentRequests', 'requestShipments', 'activeGateways', 'allGateways', 'paymentStatuses', 'methods'));
    }

    public function adminDeletePaymentRequest(Request $request, PaymentRequest $paymentRequest, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        $snapshot = $paymentRequest->only(['id', 'shipment_id', 'title', 'amount', 'currency', 'status']);

        $paymentRequest->transactions()->delete();
        $paymentRequest->delete();

        $audit->log($request, $user, 'payment_request.deleted', [
            'target_type' => 'payment_request',
            'target_id' => $snapshot['id'],
            'previous_values' => $snapshot,
        ]);

        return back()->with('success', 'Payment request deleted.');
    }

    public function adminDrivers()
    {
        $user = Auth::user();

        return view('pages.admin-drivers', compact('user'));
    }

    public function adminWarehouse()
    {
        $user = Auth::user();

        return view('pages.admin-warehouse', compact('user'));
    }

    public function adminStaff()
    {
        $user = Auth::user();

        return view('pages.admin-staff', compact('user'));
    }

    public function adminAdministrators()
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::USERS_VIEW);

        return view('pages.admin-administrators', compact('user'));
    }

    public function adminSupport()
    {
        $user = Auth::user();
        $tickets = SupportTicket::latest()->paginate(20);
        $openCount = SupportTicket::where('status', 'open')->count();
        $pendingCount = SupportTicket::where('status', 'pending')->count();
        $resolvedCount = SupportTicket::where('status', 'resolved')->count();

        return view('pages.admin-support', compact('user', 'tickets', 'openCount', 'pendingCount', 'resolvedCount'));
    }

    public function adminLiveChat(Request $request, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::DASHBOARD_VIEW);

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'conversation_id' => ['required', 'exists:guest_chat_conversations,id'],
                'action' => ['required', 'in:reply,close,reopen'],
                'message' => ['nullable', 'required_if:action,reply', 'string', 'max:3000'],
            ]);

            $conversation = GuestChatConversation::findOrFail($data['conversation_id']);

            if ($data['action'] === 'reply') {
                abort_if($conversation->status === 'closed', 422, 'This conversation is closed.');
                $message = $conversation->messages()->create([
                    'sender_type' => 'admin',
                    'admin_user_id' => $user->id,
                    'body' => $data['message'],
                ]);
                $conversation->update([
                    'status' => 'pending',
                    'last_admin_message_at' => now(),
                ]);
                $audit->log($request, $user, 'live_chat.replied', [
                    'target_type' => 'guest_chat_message',
                    'target_id' => $message->id,
                ]);

                return back()->with('success', 'Reply sent to guest conversation.');
            }

            if ($data['action'] === 'close') {
                $conversation->update([
                    'status' => 'closed',
                    'closed_by' => $user->id,
                    'closed_at' => now(),
                ]);
                $audit->log($request, $user, 'live_chat.closed', [
                    'target_type' => 'guest_chat_conversation',
                    'target_id' => $conversation->id,
                ]);

                return back()->with('success', 'Conversation closed.');
            }

            $conversation->update([
                'status' => 'open',
                'closed_by' => null,
                'closed_at' => null,
            ]);

            return back()->with('success', 'Conversation reopened.');
        }

        $conversations = GuestChatConversation::with(['shipment', 'messages.admin'])
            ->latest('updated_at')
            ->paginate(20);

        return view('pages.admin-live-chat', compact('user', 'conversations'));
    }

    public function adminNotifications()
    {
        $user = Auth::user();

        return view('pages.admin-notifications', compact('user'));
    }

    public function adminEmailServices(Request $request, NotificationDispatchService $notifications, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::USERS_VIEW);

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'recipient_mode' => ['required', 'in:user,email'],
                'recipient_user_id' => ['nullable', 'required_if:recipient_mode,user', 'exists:users,id'],
                'recipient_email' => ['nullable', 'required_if:recipient_mode,email', 'email'],
                'subject' => ['required', 'string', 'max:180'],
                'message' => ['required', 'string', 'max:10000'],
                'attachment' => ['nullable', 'file', 'max:5120'],
            ]);

            $recipientUser = ($data['recipient_mode'] === 'user')
                ? User::findOrFail($data['recipient_user_id'])
                : null;
            $recipientEmail = $recipientUser?->email ?? $data['recipient_email'];
            $attachmentPath = $request->file('attachment')?->store('email-attachments', 'public');
            $status = 'sent';
            $error = null;

            try {
                Mail::raw($data['message'], static function ($message) use ($recipientEmail, $data, $attachmentPath): void {
                    $message->to($recipientEmail)
                        ->subject($data['subject'])
                        ->from(
                            config('mail.from.address'),
                            config('mail.from.name', config('app.name'))
                        );
                    if ($attachmentPath) {
                        $message->attach(storage_path('app/public/'.$attachmentPath));
                    }
                });
            } catch (Throwable $exception) {
                $status = 'logged_fallback';
                $error = $exception->getMessage();
            }

            $log = EmailLog::create([
                'sender_id' => $user->id,
                'recipient_user_id' => $recipientUser?->id,
                'recipient_email' => $recipientEmail,
                'recipient_type' => $data['recipient_mode'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'attachment_path' => $attachmentPath,
                'status' => $status,
                'error_message' => $error,
                'sent_at' => now(),
            ]);

            if ($recipientUser) {
                $notifications->send($recipientUser, $data['subject'], $data['message'], ['type' => 'admin_email'], ['in_app']);
            }

            $audit->log($request, $user, 'email.sent', [
                'target_type' => 'email_log',
                'target_id' => $log->id,
                'new_values' => ['recipient_email' => $recipientEmail, 'status' => $status],
            ]);

            return back()->with('success', $status === 'sent' ? 'Email sent.' : 'Email saved and logged for fallback dispatch.');
        }

        $users = User::orderBy('name')->get(['id', 'name', 'email', 'role']);
        $emailLogs = EmailLog::with(['sender', 'recipientUser'])->latest()->paginate(20);

        return view('pages.admin-email-services', compact('user', 'users', 'emailLogs'));
    }

    public function adminReports()
    {
        $user = Auth::user();

        $paymentStats = [
            'total_payment_requests' => \App\Models\PaymentRequest::count(),
            'verified_payments' => \App\Models\PaymentRequest::whereIn('status', ['paid', 'verified'])->count(),
            'pending_payments' => \App\Models\PaymentRequest::whereIn('status', ['payment_required', 'payment_initiated', 'awaiting_verification'])->count(),
            'total_amount_verified' => \App\Models\PaymentRequest::whereIn('status', ['paid', 'verified'])->sum('amount'),
            'total_amount_pending' => \App\Models\PaymentRequest::whereIn('status', ['payment_required', 'payment_initiated', 'awaiting_verification'])->sum('amount'),
        ];

        $bankTransferStats = [
            'total_proofs' => \App\Models\PaymentProof::count(),
            'verified_proofs' => \App\Models\PaymentProof::where('status', 'verified')->count(),
            'rejected_proofs' => \App\Models\PaymentProof::where('status', 'rejected')->count(),
            'pending_proofs' => \App\Models\PaymentProof::where('status', 'pending')->count(),
            'total_verified_amount' => \App\Models\PaymentTransaction::where('provider', 'bank_transfer')->where('status', 'verified')->sum('amount'),
            'total_pending_amount' => \App\Models\PaymentTransaction::where('provider', 'bank_transfer')->where('status', 'pending')->sum('amount'),
        ];

        $gatewayBreakdown = \App\Models\PaymentSetting::all()->map(fn ($s) => [
            'name' => $s->gateway_name,
            'is_active' => $s->is_active,
            'label' => ucfirst(str_replace('_', ' ', $s->gateway_name)),
            'total_transactions' => \App\Models\PaymentTransaction::where('provider', $s->gateway_name)->count(),
            'verified_transactions' => \App\Models\PaymentTransaction::where('provider', $s->gateway_name)->where('status', 'verified')->count(),
            'total_amount' => \App\Models\PaymentTransaction::where('provider', $s->gateway_name)->sum('amount'),
        ]);

        return view('pages.admin-reports', compact('user', 'paymentStats', 'bankTransferStats', 'gatewayBreakdown'));
    }

    public function adminReportsExport(Request $request)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::REPORTS_VIEW);

        $format = $request->get('format', 'csv');

        $paymentRequests = \App\Models\PaymentRequest::with('shipment', 'transactions')->latest()->get();

        $rows = [['ID', 'Title', 'Tracking Number', 'Amount', 'Currency', 'Status', 'Method', 'Reference', 'Created', 'Verified At']];
        foreach ($paymentRequests as $pr) {
            $rows[] = [
                $pr->id,
                $pr->title,
                $pr->shipment?->tracking_number ?? '',
                $pr->amount,
                $pr->currency,
                $pr->status,
                $pr->requested_method ?? 'multiple',
                $pr->payment_reference ?? '',
                $pr->created_at->format('Y-m-d H:i:s'),
                $pr->verified_at?->format('Y-m-d H:i:s') ?? '',
            ];
        }

        $csv = collect($rows)->map(fn ($row) => collect($row)->map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"')->implode(','))->implode("\n");

        return \Illuminate\Support\Facades\Response::make($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="payment-reports-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    public function adminCms()
    {
        $user = Auth::user();

        return view('pages.admin-cms', compact('user'));
    }

    public function adminSettings()
    {
        return redirect()->route('admin.app-settings');
    }

    public function adminAppSettings(Request $request, AdminAuditLogger $audit)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::DASHBOARD_VIEW);

        $definitions = [
            'company_name' => ['label' => 'Company Name', 'type' => 'string', 'group' => 'app', 'default' => config('app.name')],
            'company_email' => ['label' => 'Company Email', 'type' => 'email', 'group' => 'app', 'default' => 'admin@example.com'],
            'company_phone' => ['label' => 'Phone Number', 'type' => 'string', 'group' => 'app', 'default' => ''],
            'business_address' => ['label' => 'Business Address', 'type' => 'text', 'group' => 'app', 'default' => ''],
            'logo' => ['label' => 'Website Logo', 'type' => 'image', 'group' => 'app', 'default' => ''],
            'favicon' => ['label' => 'Favicon', 'type' => 'image', 'group' => 'app', 'default' => asset('favicon.ico')],
            'website_url' => ['label' => 'Website URL', 'type' => 'url', 'group' => 'app', 'default' => config('app.url')],
            'currency' => ['label' => 'Currency', 'type' => 'string', 'group' => 'app', 'default' => 'USD'],
            'timezone' => ['label' => 'Timezone', 'type' => 'string', 'group' => 'app', 'default' => config('app.timezone')],
            'date_format' => ['label' => 'Date Format', 'type' => 'string', 'group' => 'app', 'default' => 'M d, Y'],
            'default_shipment_status' => ['label' => 'Default Shipment Status', 'type' => 'string', 'group' => 'shipment', 'default' => 'shipment_requested'],
            'email_notifications_enabled' => ['label' => 'Email Notifications Enabled', 'type' => 'boolean', 'group' => 'email', 'default' => '1'],
        ];

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'settings' => ['required', 'array'],
                'settings.*' => ['nullable', 'string', 'max:2000'],
                'logo_file' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
                'favicon_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,ico,webp,svg', 'max:1024'],
            ]);

            foreach ($definitions as $key => $definition) {
                if ($definition['type'] === 'image') {
                    continue;
                }

                AdminSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => (string) ($data['settings'][$key] ?? ''),
                        'type' => $definition['type'],
                        'group' => $definition['group'],
                        'label' => $definition['label'],
                    ]
                );
            }

            foreach (['logo' => 'logo_file', 'favicon' => 'favicon_file'] as $settingKey => $fileKey) {
                if (! $request->hasFile($fileKey)) {
                    continue;
                }

                $path = $request->file($fileKey)->store('branding', 'public');
                AdminSetting::updateOrCreate(
                    ['key' => $settingKey],
                    [
                        'value' => asset('storage/'.$path),
                        'type' => 'url',
                        'group' => 'app',
                        'label' => $definitions[$settingKey]['label'],
                    ]
                );
            }

            $audit->log($request, $user, 'settings.app.updated', [
                'target_type' => 'admin_settings',
                'new_values' => $data['settings'],
            ]);

            return back()->with('success', 'Application settings saved.');
        }

        foreach ($definitions as $key => $definition) {
            AdminSetting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $definition['default'],
                    'type' => $definition['type'],
                    'group' => $definition['group'],
                    'label' => $definition['label'],
                ]
            );
        }

        $settings = AdminSetting::whereIn('key', array_keys($definitions))->orderBy('id')->get()->keyBy('key');

        return view('pages.admin-app-settings', compact('user', 'settings', 'definitions'));
    }

    public function adminPaymentSettings(Request $request, AdminAuditLogger $audit, PaymentGatewayManager $gatewayManager)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'gateway_name' => ['required', 'string', 'max:80'],
                'api_key' => ['nullable', 'string', 'max:2000'],
                'secret_key' => ['nullable', 'string', 'max:2000'],
                'public_key' => ['nullable', 'string', 'max:2000'],
                'encryption_key' => ['nullable', 'string', 'max:2000'],
                'webhook_secret' => ['nullable', 'string', 'max:2000'],
                'webhook_url' => ['nullable', 'string', 'max:500'],
                'merchant_name' => ['nullable', 'string', 'max:200'],
                'mode' => ['required', 'in:test,live'],
                'currency' => ['required', 'string', 'max:8'],
                'is_active' => ['nullable', 'boolean'],
                'processing_fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'fixed_fee' => ['nullable', 'numeric', 'min:0'],
                'min_amount' => ['nullable', 'numeric', 'min:0'],
                'max_amount' => ['nullable', 'numeric', 'min:0'],
                'config' => ['nullable', 'array'],
            ]);

            abort_unless($gatewayManager->isRegistered($data['gateway_name']), 422, 'Unsupported payment gateway.');

            $existingSetting = PaymentSetting::where('gateway_name', $data['gateway_name'])->first();
            $configData = $data['config'] ?? [];
            if ($data['gateway_name'] === 'crypto') {
                $configData = $request->validate([
                    'config.provider' => ['nullable', 'string', 'max:50'],
                    'config.preferred_coin' => ['nullable', 'string', 'max:20'],
                    'config.api_url' => ['nullable', 'string', 'max:500'],
                    'config.ipn_secret' => ['nullable', 'string', 'max:200'],
                ])['config'] ?? [];
            }

            $webhookUrl = $data['webhook_url'] ?: ($data['gateway_name'] === 'flutterwave'
                ? url('/api/payments/webhook')
                : url('/api/payments/webhook/'.$data['gateway_name']));

            $values = [
                'api_key' => $this->retainCredential($data, $existingSetting, 'api_key'),
                'secret_key' => $this->retainCredential($data, $existingSetting, 'secret_key'),
                'public_key' => $this->retainCredential($data, $existingSetting, 'public_key'),
                'encryption_key' => $this->retainCredential($data, $existingSetting, 'encryption_key'),
                'webhook_secret' => $this->retainCredential($data, $existingSetting, 'webhook_secret'),
                'webhook_url' => $webhookUrl,
                'merchant_name' => $data['merchant_name'] ?? null,
                'mode' => $data['mode'],
                'currency' => strtoupper($data['currency']),
                'is_active' => (bool) ($data['is_active'] ?? false),
                'processing_fee_percent' => $data['processing_fee_percent'] ?? 0,
                'fixed_fee' => $data['fixed_fee'] ?? 0,
                'min_amount' => $data['min_amount'] ?? null,
                'max_amount' => $data['max_amount'] ?? null,
                'config' => $configData ?: null,
                'environment_credentials' => $existingSetting?->environment_credentials,
            ];

            if ($data['gateway_name'] === PaymentSetting::FLUTTERWAVE_BASE_GATEWAY) {
                $configData = $existingSetting?->config ?? [];
                $environments = $existingSetting?->environment_credentials ?? ($configData['environments'] ?? []);
                $selectedMode = $data['mode'];
                $selectedEnvironment = $environments[$selectedMode] ?? [];

                foreach (['public_key', 'secret_key', 'encryption_key', 'webhook_secret'] as $credentialField) {
                    $selectedEnvironment[$credentialField] = ! empty($data[$credentialField])
                        ? $data[$credentialField]
                        : ($selectedEnvironment[$credentialField] ?? null);
                }

                $selectedEnvironment['merchant_name'] = $data['merchant_name'] ?? ($selectedEnvironment['merchant_name'] ?? null);
                $selectedEnvironment['webhook_url'] = $webhookUrl;
                $selectedEnvironment['updated_at'] = now()->toIso8601String();
                $environments[$selectedMode] = $selectedEnvironment;

                $configData['active_environment'] = $selectedMode;
                unset($configData['environments']);

                $values['api_key'] = null;
                $values['public_key'] = $selectedEnvironment['public_key'] ?? null;
                $values['secret_key'] = $selectedEnvironment['secret_key'] ?? null;
                $values['encryption_key'] = $selectedEnvironment['encryption_key'] ?? null;
                $values['webhook_secret'] = $selectedEnvironment['webhook_secret'] ?? null;
                $values['merchant_name'] = $selectedEnvironment['merchant_name'] ?? null;
                $values['config'] = $configData;
                $values['environment_credentials'] = $environments;
            }

            $setting = PaymentSetting::updateOrCreate(
                ['gateway_name' => $data['gateway_name']],
                $values
            );

            $audit->log($request, $user, 'settings.payment.updated', [
                'target_type' => 'payment_setting',
                'target_id' => $setting->id,
                'new_values' => $setting->only(['gateway_name', 'mode', 'currency', 'is_active']),
            ]);

            $gatewayManager->invalidateCache($data['gateway_name']);

            return redirect()->route('admin.payment-settings')->with('success', 'Payment gateway settings saved.')->with('editGateway', $data['gateway_name']);
        }

        $paymentSettings = PaymentSetting::latest()->get();
        $registeredGateways = $gatewayManager->getAllRegistered();

        $gatewayData = [];
        foreach ($paymentSettings as $s) {
            $gatewayData[$s->gateway_name] = [
                'merchant_name' => $s->merchant_name,
                'mode' => $s->mode,
                'currency' => $s->currency,
                'is_active' => $s->is_active,
                'processing_fee_percent' => $s->processing_fee_percent,
                'fixed_fee' => $s->fixed_fee,
                'min_amount' => $s->min_amount,
                'max_amount' => $s->max_amount,
                'has_api_key' => ! empty($s->api_key),
                'has_secret_key' => ! empty($s->secret_key),
                'has_public_key' => ! empty($s->public_key),
                'has_webhook_secret' => ! empty($s->webhook_secret),
                'has_encryption_key' => ! empty($s->encryption_key),
                'has_environment_credentials' => ! empty($s->environment_credentials),
                'webhook_url' => $s->webhook_url,
                'config' => $s->config,
                'has_flutterwave_test_credentials' => $s->isFlutterwave() && $s->hasFlutterwaveEnvironmentCredentials('test'),
                'has_flutterwave_live_credentials' => $s->isFlutterwave() && $s->hasFlutterwaveEnvironmentCredentials('live'),
            ];
        }

        $editGateway = session('editGateway');

        return view('pages.admin-payment-settings', compact('user', 'paymentSettings', 'registeredGateways', 'gatewayData', 'editGateway'));
    }

    public function adminTestGateway(Request $request, string $gateway, PaymentGatewayManager $gatewayManager)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::PAYMENTS_MANAGE);

        $setting = PaymentSetting::where('gateway_name', $gateway)->first();
        if (! $setting) {
            return back()->with('error', 'Gateway not configured.');
        }

        $adapter = $gatewayManager->makeForSetting($setting);
        if (! $adapter) {
            return back()->with('error', 'Gateway adapter not found.');
        }

        try {
            $adapter->initialize($setting);
            if (method_exists($adapter, 'testConnection')) {
                $adapter->testConnection();
            }

            return back()->with('success', "{$adapter->getDisplayName()} connection test passed. Mode: {$setting->mode}");
        } catch (\Exception $e) {
            return back()->with('error', "{$adapter->getDisplayName()} test failed: ".$e->getMessage());
        }
    }

    private function retainCredential(array $data, ?PaymentSetting $existingSetting, string $field): ?string
    {
        return ! empty($data[$field]) ? $data[$field] : ($existingSetting?->{$field});
    }

    public function adminEmailSettings(Request $request, AdminAuditLogger $audit)
    {
        $definitions = [
            'smtp_host' => ['label' => 'SMTP Host', 'type' => 'string', 'group' => 'email', 'default' => ''],
            'smtp_port' => ['label' => 'SMTP Port', 'type' => 'integer', 'group' => 'email', 'default' => '587'],
            'smtp_username' => ['label' => 'SMTP Username', 'type' => 'string', 'group' => 'email', 'default' => ''],
            'smtp_password' => ['label' => 'SMTP Password', 'type' => 'password', 'group' => 'email', 'default' => ''],
            'smtp_encryption' => ['label' => 'SMTP Encryption', 'type' => 'string', 'group' => 'email', 'default' => 'tls'],
            'mail_from_name' => ['label' => 'Sender Name', 'type' => 'string', 'group' => 'email', 'default' => config('app.name')],
            'mail_from_address' => ['label' => 'Sender Email', 'type' => 'email', 'group' => 'email', 'default' => ''],
            'notify_shipment_created' => ['label' => 'Shipment Created Notification', 'type' => 'boolean', 'group' => 'email', 'default' => '1'],
            'notify_status_updated' => ['label' => 'Status Updated Notification', 'type' => 'boolean', 'group' => 'email', 'default' => '1'],
            'notify_payment_request_created' => ['label' => 'Payment Request Notification', 'type' => 'boolean', 'group' => 'email', 'default' => '1'],
            'notify_payment_received' => ['label' => 'Payment Received Notification', 'type' => 'boolean', 'group' => 'email', 'default' => '1'],
            'notify_shipment_delivered' => ['label' => 'Shipment Delivered Notification', 'type' => 'boolean', 'group' => 'email', 'default' => '1'],
        ];

        return $this->settingsPage($request, $audit, 'Email Settings', 'admin-email-settings', $definitions, 'settings.email.updated');
    }

    public function adminLiveChatSettings(Request $request, AdminAuditLogger $audit)
    {
        $definitions = [
            'live_chat_enabled' => ['label' => 'Live Chat Enabled', 'type' => 'boolean', 'group' => 'live_chat', 'default' => '1'],
            'live_chat_welcome_message' => ['label' => 'Welcome Message', 'type' => 'text', 'group' => 'live_chat', 'default' => 'Welcome. Share your tracking number and our support team will help.'],
            'live_chat_guest_email_required' => ['label' => 'Require Guest Email', 'type' => 'boolean', 'group' => 'live_chat', 'default' => '0'],
            'live_chat_poll_seconds' => ['label' => 'Polling Interval Seconds', 'type' => 'integer', 'group' => 'live_chat', 'default' => '8'],
        ];

        return $this->settingsPage($request, $audit, 'Live Chat Settings', 'admin-live-chat-settings', $definitions, 'settings.live_chat.updated');
    }

    public function adminMapSettings(Request $request, AdminAuditLogger $audit)
    {
        $definitions = [
            'map_provider' => ['label' => 'Map Provider', 'type' => 'string', 'group' => 'map', 'default' => 'openstreetmap'],
            'map_public_key' => ['label' => 'Public Map Key', 'type' => 'string', 'group' => 'map', 'default' => ''],
            'map_default_latitude' => ['label' => 'Default Latitude', 'type' => 'string', 'group' => 'map', 'default' => '0'],
            'map_default_longitude' => ['label' => 'Default Longitude', 'type' => 'string', 'group' => 'map', 'default' => '0'],
            'map_zoom' => ['label' => 'Default Zoom', 'type' => 'integer', 'group' => 'map', 'default' => '4'],
        ];

        return $this->settingsPage($request, $audit, 'Map Settings', 'admin-map-settings', $definitions, 'settings.map.updated');
    }

    public function adminNotificationSettings(Request $request, AdminAuditLogger $audit)
    {
        $definitions = [
            'notifications_enabled' => ['label' => 'Notifications Enabled', 'type' => 'boolean', 'group' => 'notifications', 'default' => '1'],
            'notify_admin_new_chat' => ['label' => 'Admin New Chat Alerts', 'type' => 'boolean', 'group' => 'notifications', 'default' => '1'],
            'notify_admin_new_payment' => ['label' => 'Admin Payment Alerts', 'type' => 'boolean', 'group' => 'notifications', 'default' => '1'],
            'notify_guest_tracking_updates' => ['label' => 'Guest Tracking Update Alerts', 'type' => 'boolean', 'group' => 'notifications', 'default' => '1'],
            'notification_footer' => ['label' => 'Notification Footer', 'type' => 'text', 'group' => 'notifications', 'default' => 'Thank you for using our logistics service.'],
        ];

        return $this->settingsPage($request, $audit, 'Notification Settings', 'admin-notification-settings', $definitions, 'settings.notifications.updated');
    }

    private function settingsPage(Request $request, AdminAuditLogger $audit, string $title, string $view, array $definitions, string $auditAction)
    {
        $user = Auth::user();
        AdminPermissions::authorize($user, AdminPermissions::DASHBOARD_VIEW);

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'settings' => ['required', 'array'],
                'settings.*' => ['nullable', 'string', 'max:5000'],
            ]);

            foreach ($definitions as $key => $definition) {
                AdminSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => (string) ($data['settings'][$key] ?? ''),
                        'type' => $definition['type'],
                        'group' => $definition['group'],
                        'label' => $definition['label'],
                    ]
                );
            }

            $audit->log($request, $user, $auditAction, [
                'target_type' => 'admin_settings',
                'new_values' => array_diff_key($data['settings'], array_flip(['smtp_password'])),
            ]);

            return back()->with('success', $title.' saved.');
        }

        foreach ($definitions as $key => $definition) {
            AdminSetting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $definition['default'],
                    'type' => $definition['type'],
                    'group' => $definition['group'],
                    'label' => $definition['label'],
                ]
            );
        }

        $settings = AdminSetting::whereIn('key', array_keys($definitions))->orderBy('id')->get()->keyBy('key');

        return view('pages.'.$view, compact('user', 'settings', 'definitions', 'title'));
    }

    public function adminAuditLogs()
    {
        $user = Auth::user();

        return view('pages.admin-audit-logs', compact('user'));
    }

    public function adminSecurity()
    {
        $user = Auth::user();

        return view('pages.admin-security', compact('user'));
    }

    public function adminAccountSettings(Request $request, AdminAuditLogger $audit)
    {
        $user = Auth::user();

        if ($request->isMethod('post')) {
            $action = $request->input('action');

            if ($action === 'update_profile') {
                $data = $request->validate([
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
                ]);

                $user->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                ]);

                $audit->log($request, $user, 'account.profile_updated', [
                    'target_type' => 'user',
                    'target_id' => $user->id,
                    'new_values' => ['name' => $data['name'], 'email' => $data['email']],
                ]);

                return back()->with('success', 'Account information updated successfully.');
            }

            if ($action === 'update_password') {
                $data = $request->validate([
                    'current_password' => ['required', 'string'],
                    'password' => ['required', 'string', 'min:8', 'confirmed'],
                    'password_confirmation' => ['required', 'string'],
                ]);

                if (! Hash::check($data['current_password'], $user->password)) {
                    return back()->withErrors(['current_password' => 'The current password is incorrect.'])->withInput();
                }

                $user->update([
                    'password' => Hash::make($data['password']),
                ]);

                $audit->log($request, $user, 'account.password_changed', [
                    'target_type' => 'user',
                    'target_id' => $user->id,
                ]);

                return back()->with('success', 'Password changed successfully. Your new password is effective immediately.');
            }

            return back()->withErrors(['error' => 'Unknown action.']);
        }

        return view('pages.admin-account-settings', compact('user'));
    }

    private function storeRoutePointFromTrackingData(Shipment $shipment, array $data, int $adminUserId): void
    {
        $coords = isset($data['latitude'], $data['longitude'])
            ? ['latitude' => $data['latitude'], 'longitude' => $data['longitude']]
            : app(LocationCoordinateResolver::class)->resolveLocation((string) ($data['location'] ?? ''));

        if (! $coords) {
            return;
        }

        $nextSortOrder = ((int) $shipment->routePoints()->max('sort_order')) + 10;

        $shipment->routePoints()->create([
            'sort_order' => max(10, min($nextSortOrder, 90)),
            'type' => ($data['status'] ?? null) === 'customs_clearance' ? 'customs' : 'checkpoint',
            'label' => ShipmentStatus::toDisplay($data['status'] ?? $shipment->status),
            'location' => $data['location'],
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'description' => $data['description'],
            'arrived_at' => Carbon::parse($data['occurred_at']),
            'created_by' => $adminUserId,
        ]);
    }

    // ---- TRACKING ----

    public function tracking(Request $request)
    {
        $trackingNumber = (string) $request->query('number', '');
        $shipment = null;
        $error = null;

        if ($request->isMethod('post')) {
            $trackingNumber = Str::upper(trim((string) $request->input('tracking_number', '')));
            if ($trackingNumber !== '') {
                $shipment = Shipment::with(['payments', 'paymentRequests', 'routePoints', 'trackingEvents', 'driver', 'warehouse', 'attachments'])
                    ->where('tracking_number', $trackingNumber)
                    ->first();

                if (! $shipment) {
                    $error = 'We could not find that tracking number yet.';
                }
            }
        }

        $mapService = app(MapPointService::class);
        $mapPoints = $shipment ? $mapService->buildForShipment($shipment) : collect();
        $mapSettings = $mapService->settings();

        return view('pages.tracking', compact('trackingNumber', 'shipment', 'error', 'mapPoints', 'mapSettings'));
    }

    public function trackingWithNumber(string $trackingNumber)
    {
        $trackingNumber = Str::upper(trim($trackingNumber));
        $shipment = Shipment::with(['payments', 'paymentRequests', 'routePoints', 'trackingEvents', 'driver', 'warehouse', 'attachments'])
            ->where('tracking_number', $trackingNumber)
            ->first();

        $error = $shipment ? null : 'We could not find that tracking number yet.';
        $mapService = app(MapPointService::class);
        $mapPoints = $shipment ? $mapService->buildForShipment($shipment) : collect();
        $mapSettings = $mapService->settings();

        return view('pages.tracking', compact('trackingNumber', 'shipment', 'error', 'mapPoints', 'mapSettings'));
    }

    public function trackRedirect(string $trackingNumber)
    {
        return redirect()->route('tracking.number', ['trackingNumber' => $trackingNumber], 301);
    }

    public function receipt(string $trackingNumber)
    {
        $receipt = app(ShipmentReceiptService::class);
        $shipment = $receipt->loadForReceipt($trackingNumber);
        $parsed = $receipt->parseMeta($shipment);
        $totalCost = $receipt->calculateTotalCost($shipment, $parsed['costs']);

        $paymentRequests = $shipment->paymentRequests;
        $completedPaymentRequest = $paymentRequests->filter(fn ($pr) => $pr->status === 'paid')->first();
        $pendingPaymentRequest = $paymentRequests->filter(fn ($pr) => !in_array($pr->status, ['paid', 'cancelled']))->first();

        return view('pages.receipt', array_merge(
            compact('shipment', 'totalCost', 'paymentRequests', 'completedPaymentRequest', 'pendingPaymentRequest'), $parsed
        ));
    }

    public function receiptPdf(string $trackingNumber)
    {
        $receipt = app(ShipmentReceiptService::class);
        $shipment = $receipt->loadForReceipt($trackingNumber);
        $parsed = $receipt->parseMeta($shipment);
        $totalCost = $receipt->calculateTotalCost($shipment, $parsed['costs']);

        $paymentRequests = $shipment->paymentRequests;
        $completedPaymentRequest = $paymentRequests->filter(fn ($pr) => $pr->status === 'paid')->first();
        $pendingPaymentRequest = $paymentRequests->filter(fn ($pr) => !in_array($pr->status, ['paid', 'cancelled']))->first();

        $trackingUrl = route('tracking.number', $shipment->tracking_number);
        $barcodeHtml = $this->generateBarcodeTable($shipment->tracking_number);

        $pdf = Pdf::loadView('pages.receipt-pdf', array_merge(
            compact('shipment', 'totalCost', 'trackingUrl', 'barcodeHtml',
                'paymentRequests', 'completedPaymentRequest', 'pendingPaymentRequest'), $parsed
        ));
        $pdf->setPaper('a4');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf->download("receipt-{$shipment->tracking_number}.pdf");
    }

    public function trackingLiveData(string $trackingNumber): JsonResponse
    {
        $trackingNumber = Str::upper(trim($trackingNumber));
        $shipment = Shipment::with(['routePoints', 'trackingEvents'])
            ->where('tracking_number', $trackingNumber)
            ->first();

        if (! $shipment) {
            return response()->json(['error' => 'Shipment not found'], 404);
        }

        $mapService = app(MapPointService::class);
        $points = $mapService->buildForShipment($shipment);

        $lastEvent = $shipment->trackingEvents->first();
        $lastUpdated = $lastEvent?->occurred_at?->diffForHumans() ?? 'N/A';

        return response()->json([
            'status' => ShipmentStatus::toDisplay($shipment->status),
            'status_code' => $shipment->status,
            'tracking_number' => $shipment->tracking_number,
            'last_updated' => $lastUpdated,
            'last_updated_raw' => $lastEvent?->occurred_at?->toIso8601String(),
            'points' => $points,
            'has_points' => $points->isNotEmpty(),
        ]);
    }

    // ---- STAFF / DRIVER / WAREHOUSE ----

    public function staff()
    {
        return view('pages.staff');
    }

    public function driver()
    {
        return view('pages.driver');
    }

    public function warehouse()
    {
        return view('pages.warehouse');
    }
}
