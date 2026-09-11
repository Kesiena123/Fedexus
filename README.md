# FreightFlow Logistics Platform

Enterprise-grade logistics and courier management platform: tracking workflows, rates and transit estimates, operations dashboards, real-time support, and staged payments.

## Stack

- Backend: PHP 8.3, Laravel 12, Laravel Sanctum, Laravel Reverb, Redis queues
- Frontend: Laravel Blade, Alpine.js, Tailwind CSS, Livewire
- Data: SQLite (dev) / MySQL 8 (prod)
- Payments: Stripe, PayPal, Flutterwave, Paystack integration adapters
- Realtime: WebSockets for shipment updates, notifications, and chat

## Quick Start

1. Copy environment file:

```bash
cp backend/.env.example backend/.env
```

2. Start services:

```bash
docker compose up --build
```

3. Install dependencies and run setup inside the API container:

```bash
docker compose exec backend composer install
docker compose exec backend php artisan key:generate
docker compose exec backend php artisan migrate --seed
docker compose exec backend php artisan reverb:start
docker compose exec backend php artisan queue:work redis
```

4. Open: http://localhost:8000

## Key Capabilities

- Home, tracking, shipping, rates, support, contact, service, auth, dashboard, admin, staff, driver, and warehouse pages
- Tracking number formats: `FDX-2026-XXXXXXXX`, `TRK-XXXXXXXXXX`, `LOG-XXXXXXXXXX`
- Quote calculator and shipment creation workflow
- Pickup and delivery scheduling
- Real-time tracking event stream
- Five-stage payment release workflow, 20% per stage
- Support tickets and real-time chat with file metadata support, typing events, and notifications
- Role-based dashboards for customer, admin, staff, driver, and warehouse teams
