<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuestChatController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ShipmentAttachmentController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\ShipmentDraftController;
use Illuminate\Support\Facades\Route;

// ---- PUBLIC API ----
Route::get('/track/{trackingNumber}', [ShipmentController::class, 'track']);
Route::post('/quotes', QuoteController::class);
Route::post('/payments/webhook', [PaymentController::class, 'flutterwaveWebhook'])->name('api.payments.webhook.flutterwave');
Route::post('/payments/webhook/{provider}', [PaymentController::class, 'webhook'])->name('api.payments.webhook');
Route::post('/guest-chat', [GuestChatController::class, 'start']);
Route::post('/guest-chat/{token}/messages', [GuestChatController::class, 'reply']);

// ---- ADMIN AUTH (rate-limited, IP-checked) ----
Route::prefix('/admin/auth')->middleware(['admin.ip'])->group(function () {
    Route::get('/captcha', [AuthController::class, 'adminCaptcha']);
    Route::post('/login', [AuthController::class, 'adminLogin']);
});

// ---- AUTHENTICATED ADMIN API ----
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::prefix('/admin')->middleware(['admin.ip', 'admin.access', 'admin.session'])->group(function () {
        Route::get('/session', [AdminController::class, 'session']);
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::patch('/users/{user}', [AdminUserController::class, 'update']);
        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend']);
        Route::post('/users/{user}/reactivate', [AdminUserController::class, 'reactivate']);
        Route::post('/users/{user}/verify-email', [AdminUserController::class, 'verifyEmail']);
        Route::post('/users/{user}/password-reset', [AdminUserController::class, 'issuePasswordReset']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
        Route::get('/support-tickets', [AdminController::class, 'tickets']);
        Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
        Route::get('/audit-logs/export', [AdminController::class, 'exportAuditLogs']);
        Route::post('/shipments', [ShipmentController::class, 'store']);
        Route::patch('/shipments/{shipment}', [ShipmentController::class, 'update']);
        Route::delete('/shipments/{shipment}', [ShipmentController::class, 'destroy']);
        Route::post('/shipments/{shipment}/notifications/resend', [ShipmentController::class, 'resendNotifications']);
        Route::post('/shipments/{shipment}/approve', [ShipmentController::class, 'approve']);
        Route::post('/shipments/{shipment}/regenerate-tracking', [ShipmentController::class, 'regenerateTracking']);
        Route::post('/shipments/{shipment}/reject', [ShipmentController::class, 'reject']);
        Route::patch('/shipments/{shipment}/status', [ShipmentController::class, 'status']);
        Route::post('/shipments/{shipment}/pickup', [ShipmentController::class, 'pickup']);
        Route::post('/shipments/{shipment}/delivery', [ShipmentController::class, 'delivery']);
        Route::get('/shipment-draft', [ShipmentDraftController::class, 'show']);
        Route::post('/shipment-draft', [ShipmentDraftController::class, 'upsert']);
        Route::delete('/shipment-draft', [ShipmentDraftController::class, 'destroy']);
        Route::get('/shipments/{shipment}/attachments', [ShipmentAttachmentController::class, 'index']);
        Route::post('/shipments/{shipment}/attachments', [ShipmentAttachmentController::class, 'store']);
        Route::delete('/shipments/{shipment}/attachments/{attachment}', [ShipmentAttachmentController::class, 'destroy']);
        Route::get('/shipments/{shipment}', [ShipmentController::class, 'show']);
        Route::post('/shipments/{shipment}/tracking-events', [ShipmentController::class, 'addTrackingEvent']);
        Route::post('/shipments/{shipment}/payments/unlock-next', [PaymentController::class, 'unlock']);
        Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify']);
        Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject']);
    });
});
