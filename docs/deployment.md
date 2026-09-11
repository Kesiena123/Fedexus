# Deployment Documentation

## Production Checklist

- Provision MySQL 8, Redis, object storage, mail, SMS, push notification, and queue infrastructure.
- Set `APP_ENV=production`, `APP_DEBUG=false`, strong `APP_KEY`.
- Configure Sanctum stateful domains and CORS.
- Configure Reverb with TLS behind a reverse proxy.
- Set payment provider keys and webhook secrets.
- Run `php artisan migrate --force`.
- Run queue workers under Supervisor or a container orchestrator.
- Serve the Laravel application behind Nginx or Caddy with PHP-FPM.
- Run `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
- Enforce HTTPS, HSTS, secure cookies, rate limits, audit logging, and encrypted backups.

## Required Environment Variables

Backend:

```env
APP_URL=https://app.example.com
DB_HOST=mysql
REDIS_HOST=redis
SANCTUM_STATEFUL_DOMAINS=app.example.com
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
STRIPE_SECRET=
PAYPAL_CLIENT_ID=
PAYPAL_CLIENT_SECRET=
FLUTTERWAVE_SECRET_KEY=
PAYSTACK_SECRET_KEY=
MAIL_MAILER=smtp
SMS_PROVIDER=
PUSH_PROVIDER=
```

## Scaling

- Run stateless application replicas.
- Separate queue workers by workload: `payments`, `notifications`, `tracking`, `chat`.
- Use Redis for cache, queues, sessions, and broadcast presence metadata.
- Store uploaded chat files and shipment documents in S3-compatible storage.
- Index tracking number, status, customer, driver, warehouse, and payment stage fields.
