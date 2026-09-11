<?php

use App\Http\Controllers\AdminGuestChatController;
use App\Http\Controllers\AdminPaymentRequestController;
use App\Http\Controllers\BladeFrontendController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\GuestChatController;
use App\Http\Controllers\PaymentRequestController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;

// ---- PUBLIC PAGES ----
Route::get('/', [BladeFrontendController::class, 'home'])->name('home');
Route::get('/about', [BladeFrontendController::class, 'about'])->name('about');
Route::get('/services', [BladeFrontendController::class, 'services'])->name('services');
Route::get('/services/international', [BladeFrontendController::class, 'servicesInternational'])->name('services.international');
Route::get('/pricing', [BladeFrontendController::class, 'pricing'])->name('pricing');
Route::get('/rates', [BladeFrontendController::class, 'rates'])->name('rates');
Route::get('/contact', [BladeFrontendController::class, 'contact'])->name('contact');
Route::get('/support', [BladeFrontendController::class, 'support'])->name('support');
Route::post('/support', [SupportTicketController::class, 'storeFromForm'])->name('support.store');
Route::get('/careers', [BladeFrontendController::class, 'careers'])->name('careers');
Route::get('/blog', [BladeFrontendController::class, 'blog'])->name('blog');
Route::get('/faq', [BladeFrontendController::class, 'faq'])->name('faq');
Route::get('/shipping', [BladeFrontendController::class, 'shipping'])->name('shipping');

// ---- PUBLIC TRACKING ----
Route::get('/track', [BladeFrontendController::class, 'tracking'])->name('track');
Route::match(['get', 'post'], '/tracking', [BladeFrontendController::class, 'tracking'])->name('tracking');
Route::get('/tracking/{trackingNumber}', [BladeFrontendController::class, 'trackingWithNumber'])->name('tracking.number');
Route::get('/tracking/{trackingNumber}/live', [BladeFrontendController::class, 'trackingLiveData'])->name('tracking.live');
Route::get('/track/{trackingNumber}', [BladeFrontendController::class, 'trackRedirect'])->name('track.redirect');
Route::get('/receipt/{trackingNumber}', [BladeFrontendController::class, 'receipt'])->name('receipt');
Route::get('/receipt/{trackingNumber}/pdf', [BladeFrontendController::class, 'receiptPdf'])->name('receipt.pdf');
Route::get('/pay/{token}', [PaymentRequestController::class, 'show'])->name('payment-request.show');
Route::post('/pay/{token}', [PaymentRequestController::class, 'start'])->name('payment-request.start');
Route::get('/pay/{token}/success', [PaymentRequestController::class, 'success'])->name('payment.success');
Route::get('/pay/{token}/cancel', [PaymentRequestController::class, 'cancel'])->name('payment.cancel');
Route::get('/pay/{token}/callback/{provider}', [PaymentRequestController::class, 'callback'])->name('payment.callback');
Route::post('/pay/{token}/upload-proof', [PaymentRequestController::class, 'uploadProof'])->name('payment.upload-proof');
Route::get('/pay/{token}/instructions', [PaymentRequestController::class, 'instructions'])->name('payment-request.instructions');
Route::get('/pay/{token}/instructions-pdf', [PaymentRequestController::class, 'instructionsPdf'])->name('payment-request.instructions-pdf');

// ---- GUEST CHAT API (public, uses tracking number) ----
Route::get('/api/chat/info', [GuestChatController::class, 'info'])->name('chat.info');
Route::post('/api/chat/start', [GuestChatController::class, 'start'])->name('chat.start');
Route::get('/api/chat/resume', [GuestChatController::class, 'resume'])->name('chat.resume');
Route::post('/api/chat/reply', [GuestChatController::class, 'reply'])->name('chat.reply');
Route::post('/api/chat/upload', [GuestChatController::class, 'upload'])->name('chat.upload');

// ---- REDIRECT OLD CUSTOMER AUTH PAGES TO TRACKING ----
Route::redirect('/login', '/tracking', 301)->name('login');
Route::redirect('/register', '/tracking', 301)->name('register');
Route::redirect('/forgot-password', '/tracking', 301)->name('forgot-password');
Route::redirect('/dashboard', '/tracking', 301)->name('dashboard');
Route::redirect('/dashboard/shipments', '/tracking', 301)->name('dashboard.shipments');
Route::redirect('/profile', '/tracking', 301)->name('profile');

// ---- ADMIN LOGIN (no auth required) ----
Route::match(['get', 'post'], '/admin/login', [BladeFrontendController::class, 'adminLogin'])->name('admin.login');

// ---- PROTECTED PAGES (admin + operations only) ----
Route::middleware('auth')->group(function () {
    Route::post('/logout', [BladeFrontendController::class, 'logout'])->name('logout');

    Route::middleware(['admin.access'])->group(function () {
        Route::match(['get', 'post'], '/admin', [BladeFrontendController::class, 'adminDashboard'])->name('admin.dashboard');
        Route::get('/admin/shipments', [BladeFrontendController::class, 'adminShipments'])->name('admin.shipments');
        Route::get('/admin/shipments/export', [BladeFrontendController::class, 'adminShipmentsExport'])->name('admin.shipments.export');
        Route::get('/admin/shipments/create', [BladeFrontendController::class, 'adminCreateShipment'])->name('admin.shipments.create');
        Route::post('/admin/shipments', [BladeFrontendController::class, 'adminStoreShipment'])->name('admin.shipments.store');
        Route::match(['get', 'post'], '/admin/shipments/{shipment}', [BladeFrontendController::class, 'adminShipmentShow'])->name('admin.shipments.show');
        Route::match(['get', 'post'], '/admin/shipments/{shipment}/edit', [BladeFrontendController::class, 'adminShipmentEdit'])->name('admin.shipments.edit');
        Route::delete('/admin/shipments/{shipment}', [BladeFrontendController::class, 'adminShipmentDelete'])->name('admin.shipments.delete');
        Route::get('/admin/shipments/{shipment}/receipt', [BladeFrontendController::class, 'adminShipmentReceipt'])->name('admin.shipments.receipt');
        Route::get('/admin/shipments/{shipment}/receipt/pdf', [BladeFrontendController::class, 'adminShipmentReceiptPdf'])->name('admin.shipments.receipt.pdf');
        Route::get('/admin/tracking', [BladeFrontendController::class, 'adminTracking'])->name('admin.tracking');
        Route::match(['get', 'post'], '/admin/payments', [BladeFrontendController::class, 'adminPayments'])->name('admin.payments');
        // ---- ADMIN PAYMENT REQUEST MANAGEMENT ----
        Route::prefix('admin/payment-requests')->name('admin.payment-requests.')->controller(AdminPaymentRequestController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{paymentRequest}', 'show')->name('show');
            Route::get('{paymentRequest}/edit', 'edit')->name('edit');
            Route::patch('{paymentRequest}', 'update')->name('update');
            Route::delete('{paymentRequest}', 'destroy')->name('destroy');
            Route::post('{paymentRequest}/duplicate', 'duplicate')->name('duplicate');
            Route::patch('{paymentRequest}/toggle-active', 'toggleActive')->name('toggle-active');
            Route::patch('{paymentRequest}/cancel', 'cancel')->name('cancel');
            Route::patch('{paymentRequest}/expire', 'expire')->name('expire');
            Route::patch('{paymentRequest}/extend-due', 'extendDueDate')->name('extend-due');
            Route::patch('{paymentRequest}/complete', 'markCompleted')->name('complete');
            Route::patch('{paymentRequest}/refund', 'refund')->name('refund');
            Route::patch('{paymentRequest}/reopen', 'reopen')->name('reopen');
            Route::patch('{paymentRequest}/archive', 'archive')->name('archive');
            Route::patch('{paymentRequest}/restore', 'restore')->name('restore');
        });

        Route::get('/admin/drivers', [BladeFrontendController::class, 'adminDrivers'])->name('admin.drivers');
        Route::get('/admin/warehouse', [BladeFrontendController::class, 'adminWarehouse'])->name('admin.warehouse');
        Route::get('/admin/staff', [BladeFrontendController::class, 'adminStaff'])->name('admin.staff');
        Route::get('/admin/administrators', [BladeFrontendController::class, 'adminAdministrators'])->name('admin.administrators');
        Route::get('/admin/support', [BladeFrontendController::class, 'adminSupport'])->name('admin.support');
        Route::post('/admin/support/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->name('admin.support.status');
        Route::match(['get', 'post'], '/admin/live-chat', [BladeFrontendController::class, 'adminLiveChat'])->name('admin.live-chat');
        Route::prefix('admin/api/live-chat')->name('admin.live-chat.')->controller(AdminGuestChatController::class)->group(function () {
            Route::get('conversations', 'conversations')->name('conversations');
            Route::get('conversations/{id}', 'show')->name('show');
            Route::post('conversations/{id}/reply', 'reply')->name('reply');
            Route::post('conversations/{id}/close', 'close')->name('close');
            Route::post('conversations/{id}/reopen', 'reopen')->name('reopen');
            Route::post('conversations/{id}/archive', 'archive')->name('archive');
            Route::delete('conversations/{id}', 'destroy')->name('destroy');
            Route::post('conversations/{id}/mark-read', 'markRead')->name('mark-read');
        });
        Route::get('/admin/notifications', [BladeFrontendController::class, 'adminNotifications'])->name('admin.notifications');
        Route::match(['get', 'post'], '/admin/email-services', [BladeFrontendController::class, 'adminEmailServices'])->name('admin.email-services');
        Route::get('/admin/reports', [BladeFrontendController::class, 'adminReports'])->name('admin.reports');
        Route::get('/admin/reports/export', [BladeFrontendController::class, 'adminReportsExport'])->name('admin.reports.export');
        Route::get('/admin/cms', [BladeFrontendController::class, 'adminCms'])->name('admin.cms');
        Route::get('/admin/settings', [BladeFrontendController::class, 'adminSettings'])->name('admin.settings');
        Route::match(['get', 'post'], '/admin/settings/app', [BladeFrontendController::class, 'adminAppSettings'])->name('admin.app-settings');
        Route::match(['get', 'post'], '/admin/settings/payment', [BladeFrontendController::class, 'adminPaymentSettings'])->name('admin.payment-settings');
        Route::match(['get', 'post'], '/admin/settings/bank-accounts', [BankAccountController::class, 'index'])->name('admin.bank-accounts');
        Route::post('/admin/settings/bank-accounts/store', [BankAccountController::class, 'store'])->name('admin.bank-accounts.store');
        Route::match(['get', 'post'], '/admin/settings/bank-accounts/{bankAccount}/edit', [BankAccountController::class, 'edit'])->name('admin.bank-accounts.edit');
        Route::delete('/admin/settings/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('admin.bank-accounts.destroy');
        Route::post('/admin/settings/bank-accounts/{bankAccount}/toggle', [BankAccountController::class, 'toggle'])->name('admin.bank-accounts.toggle');
        Route::post('/admin/settings/bank-accounts/{bankAccount}/default', [BankAccountController::class, 'setDefault'])->name('admin.bank-accounts.default');
        Route::post('/admin/settings/bank-accounts/reorder', [BankAccountController::class, 'reorder'])->name('admin.bank-accounts.reorder');
        Route::match(['get', 'post'], '/admin/bank-transfers', [BankAccountController::class, 'review'])->name('admin.bank-transfers');
        Route::post('/admin/bank-transfers/{paymentProof}/approve', [BankAccountController::class, 'approve'])->name('admin.bank-transfers.approve');
        Route::post('/admin/bank-transfers/{paymentProof}/reject', [BankAccountController::class, 'reject'])->name('admin.bank-transfers.reject');
        Route::post('/admin/bank-transfers/{paymentProof}/notes', [BankAccountController::class, 'addNotes'])->name('admin.bank-transfers.notes');
        Route::post('/admin/bank-transfers/{paymentProof}/request-resubmission', [BankAccountController::class, 'requestResubmission'])->name('admin.bank-transfers.resubmit');
        Route::post('/admin/settings/payment/{gateway}/test', [BladeFrontendController::class, 'adminTestGateway'])->name('admin.payment-settings.test');
        Route::match(['get', 'post'], '/admin/settings/email', [BladeFrontendController::class, 'adminEmailSettings'])->name('admin.email-settings');
        Route::match(['get', 'post'], '/admin/settings/live-chat', [BladeFrontendController::class, 'adminLiveChatSettings'])->name('admin.live-chat-settings');
        Route::match(['get', 'post'], '/admin/settings/map', [BladeFrontendController::class, 'adminMapSettings'])->name('admin.map-settings');
        Route::match(['get', 'post'], '/admin/settings/notifications', [BladeFrontendController::class, 'adminNotificationSettings'])->name('admin.notification-settings');
        Route::match(['get', 'post'], '/admin/settings/account', [BladeFrontendController::class, 'adminAccountSettings'])->name('admin.account-settings');
        Route::get('/admin/audit-logs', [BladeFrontendController::class, 'adminAuditLogs'])->name('admin.audit-logs');
        Route::get('/admin/security', [BladeFrontendController::class, 'adminSecurity'])->name('admin.security');
        Route::get('/staff', [BladeFrontendController::class, 'staff'])->name('staff');
    });

    Route::get('/warehouse', [BladeFrontendController::class, 'warehouse'])->name('warehouse');
    Route::get('/driver', [BladeFrontendController::class, 'driver'])->name('driver');
});
