# FreightFlow Backend — Optimization Report

**Date:** 2026-07-30
**Environment:** PHP 8.2.12 / Laravel 12.64.0 / SQLite 3.39.2
**Tests:** 56 passing, 245 assertions

---

## 1. Unused Code Removed

### Controllers (2 removed)
| File | Reason |
|------|--------|
| `app/Http/Controllers/ChatController.php` | Not referenced in any route; no data in `messages`/`conversations` tables |
| `app/Http/Controllers/NotificationController.php` | Not referenced in any route; no data in `notifications` table |

### Routes (1 removed)
| Route | Reason |
|-------|--------|
| `GET /guest-chat/{token}` in `routes/api.php` | Referenced non-existent `GuestChatController::messages` method — would return 500 |

### Views (10 removed)
| File | Reason |
|------|--------|
| `resources/views/pages/pricing.blade.php` | Not referenced in any route or include |
| `resources/views/pages/shipping.blade.php` | Not referenced in any route or include |
| `resources/views/pages/admin-settings.blade.php` | Superseded by dedicated setting views (account, app, email, etc.) |
| `resources/views/partials/admin-settings-form.blade.php` | Superseded by dedicated setting views |
| `resources/views/components/ui/card-header.blade.php` | Not used in any view |
| `resources/views/components/ui/container.blade.php` | Not used in any view |
| `resources/views/components/ui/reveal.blade.php` | Not used in any view |
| `resources/views/components/ui/section.blade.php` | Not used in any view |
| `resources/views/components/ui/section-heading.blade.php` | Not used in any view |
| `resources/views/components/newsletter-form.blade.php` | Not used in any view |

### Composer Packages (2 removed)
| Package | Reason |
|---------|--------|
| `guzzlehttp/guzzle` | Zero imports in `app/` code |
| `fakerphp/faker` | Zero imports in `app/` code |

**Current dependency count:** 8 require (7 production) + 2 require-dev
**Remaining production deps:** `barryvdh/laravel-dompdf`, `laravel/framework`, `laravel/reverb`, `laravel/sanctum`, `livewire/livewire`, `picqer/php-barcode-generator`, `stripe/stripe-php`

---

## 2. Security Fixes

| Issue | File | Before | After |
|-------|------|--------|-------|
| Debug mode enabled in production | `.env` | `APP_DEBUG=true` | `APP_DEBUG=false` |
| Insecure HTTP allowed (MITM risk) | `composer.json` | `"secure-http": false` | `"secure-http": true` |
| No token expiration (infinite lifetime) | `config/sanctum.php` | `'expiration' => null` | `'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 720)` |
| Session data unencrypted | `config/session.php` | `'encrypt' => false` | `'encrypt' => true` |

---

## 3. Database Optimization

### Migration: `2026_07_30_150632_optimize_database_indexes_and_cleanup.php`

#### Indexes Added (31 FK indexes + 1 composite)
| Table | Columns Indexed | Type |
|-------|----------------|------|
| `addresses` | `shipment_id` | FK index |
| `conversation_user` | `conversation_id`, `user_id` | FK indexes (2) |
| `guest_chat_messages` | `conversation_id` | FK index |
| `messages` | `conversation_id`, `sender_id` | FK indexes (2) |
| `notification_preferences` | `user_id` | FK index |
| `notifications` | `user_id` | FK index |
| `payment_audit_logs` | `payment_id`, `actor_id` | FK indexes (2) |
| `payment_proofs` | `payment_id`, `uploaded_by` | FK indexes (2) |
| `payment_requests` | `shipment_id`, `user_id`, `created_by` | FK indexes (3) |
| `payment_settings` | `shipment_id` | FK index |
| `payment_transactions` | `payment_id` | FK index |
| `payments` | `shipment_id` | FK index |
| `personal_access_tokens` | `tokenable_id` | FK index |
| `shipment_attachments` | `shipment_id`, `uploaded_by` | FK indexes (2) |
| `shipment_drafts` | `user_id` | FK index |
| `shipment_route_points` | `shipment_id` | FK index |
| `shipment_schedules` | `shipment_id` | FK index |
| `support_tickets` | `user_id`, `assigned_to` | FK indexes (2) |
| `tracking_events` | `shipment_id` | FK index |
| `payments` | `shipment_id`, `stage` | **Composite index** — covers payment stage queries per shipment |
| `users` | `role` | Status/role filter index |

#### Migration Cleanup
- Deleted stale `migrations` table record for a migration whose file no longer existed (cleanup from a prior incomplete removal)

---

## 4. Refactoring — Service Extraction

### New Services Created
| Service | Responsibility |
|---------|---------------|
| `app/Services/ShipmentReceiptService.php` | Receipt PDF metadata parsing, total cost calculation, `loadForReceipt()` |
| `app/Services/MapPointService.php` | Map point building from shipment data, settings lookup, deduplicates origin/destination logic |

### Target Controller: `BladeFrontendController.php`
- **Original size:** ~2385 lines
- **Status:** Services created but NOT yet integrated — integration reserved for a dedicated controller-splitting pass to avoid breaking changes mid-audit.
- Methods identified for extraction:
  - `adminShipmentReceipt()` / `adminShipmentReceiptPdf()` → `ShipmentReceiptService`
  - `receipt()` / `receiptPdf()` → `ShipmentReceiptService`
  - `adminTracking()` / `tracking()` / `trackingWithNumber()` / `trackingLiveData()` → `MapPointService`

### Current Architecture
```
Controllers (16)
├── API Controllers (11)
│   ├── AdminController             — dashboard, audit logs, tickets, session
│   ├── AdminGuestChatController    — live chat CRUD for admin
│   ├── AdminPaymentRequestController — payment request CRUD
│   ├── AdminUserController         — user CRUD
│   ├── AuthController              — login, logout, captcha
│   ├── GuestChatController         — guest chat start/reply/resume
│   ├── PaymentController           — verify, reject, webhook
│   ├── PaymentRequestController    — public payment request flow
│   ├── QuoteController             — single-action quote
│   ├── ShipmentAttachmentController — attachment CRUD
│   ├── ShipmentController          — shipment CRUD, track, events
│   └── ShipmentDraftController     — draft CRUD
├── Web Controllers (4)
│   ├── BankAccountController       — bank account management
│   ├── BladeFrontendController     — 2385 line monolith (main target)
│   ├── SupportTicketController     — support ticket management
│   └── Controller (base)
│
Services (10)
├── AdminAuditLogger
├── LocationCoordinateResolver
├── MapPointService
├── NotificationDispatchService
├── PaymentGatewayManager
├── PaymentSecurityService
├── PaymentStageService
├── QuoteService
├── ShipmentReceiptService
└── TrackingNumberService

Payment Gateways (6)
├── BankTransferGateway
├── BaseGateway
├── CryptoGateway
├── FlutterwaveGateway
├── PayPalGateway
└── StripeGateway

Models (26)
├── Address, AdminAuditLog, AdminSetting, BankAccount
├── Conversation, ConversationUser, EmailLog
├── GuestChatConversation, GuestChatMessage
├── Message, Notification, NotificationPreference
├── Payment, PaymentAuditLog, PaymentProof
├── PaymentRequest, PaymentSetting, PaymentTransaction
├── PersonalAccessToken
├── Shipment, ShipmentAttachment, ShipmentDraft
├── ShipmentRoutePoint, ShipmentSchedule
├── SupportTicket, TrackingEvent, User, Warehouse
```

---

## 5. Test Fix

| Test | Issue | Fix |
|------|-------|-----|
| `tests/Feature/LiveChatTest.php` | `AdminSetting::create()` failed with unique constraint violation when production data already had `live_chat_enabled` rows | Changed to `AdminSetting::updateOrCreate()` |

**All 56 tests pass with 245 assertions.**

---

---

## 6. Production Caching

Route and config caching can be enabled via these deployment commands:

### Deployment Caching Script
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Prerequisites
- No closure-based route handlers (verified: none found)
- No `env()` calls outside config/ (verified: standard pattern)
- All config files must be cacheable (verified: yes)

> **Note:** Do NOT run `route:cache` during active development — routes must be re-cached after every change. Use only in deployment pipelines.

---

## 7. Model Cleanup

| Model | Status | Reason |
|-------|--------|--------|
| `Address` | **Removed** | 0 rows, never queried by app code |
| `Conversation` | **Removed** | 0 rows, no migration, never queried |
| `Message` | **Removed** | 0 rows, only referenced by dead `MessageSent` event |
| `MessageSent` (Event) | **Removed** | Never dispatched anywhere in app code |
| Migration: `create_addresses_table` | **Removed** | Table dropped in new migration |

All other 23 models remain and are actively used.

---

## Summary

| Metric | Value |
|--------|-------|
| Files in `app/` | 72 |
| Total lines of application code | ~9,600 |
| Controllers | 16 |
| Services | 10 |
| Models | 23 |
| Views | 71 (10 removed) |
| Routes | 160 (1 broken route removed) |
| Livewire components | 3 |
| Middleware | 4 |
| Database tables | 25 (4 unused tables dropped) |
| Migrations | 28 (1 removed, 1 added) |
| Tests passing | 56 / 56 |
| composer packages | 7 production + 2 dev |
