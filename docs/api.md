# REST API Documentation

Base URL: `/api`

Authentication uses Laravel Sanctum bearer tokens. Protected endpoints require:

```http
Authorization: Bearer {token}
Accept: application/json
```

## Auth

### POST `/auth/register`

Creates a customer account.

```json
{
  "name": "Maya Carter",
  "email": "maya@example.com",
  "password": "Password123!",
  "phone": "+14155550123"
}
```

### POST `/auth/login`

Returns the user profile and Sanctum token.

### POST `/auth/logout`

Revokes the active token.

## Tracking

### GET `/track/{trackingNumber}`

Public shipment lookup with current status, route, delivery estimate, payment stage, and tracking history.

## Quotes

### POST `/quotes`

Calculates rates and transit times.

```json
{
  "origin_country": "US",
  "origin_postal_code": "94105",
  "destination_country": "GB",
  "destination_postal_code": "SW1A 1AA",
  "weight_kg": 8.5,
  "service_level": "international_priority",
  "declared_value": 450
}
```

## Shipments

### GET `/shipments`

Lists shipments visible to the authenticated user.

### POST `/shipments`

Creates a shipment, generates a tracking number, creates staged payment records, and emits notifications.

### GET `/shipments/{shipment}`

Returns shipment details.

### PATCH `/shipments/{shipment}/status`

Admin/staff update of status, location, driver, warehouse, and tracking event.

## Payments

### GET `/shipments/{shipment}/payments`

Returns the five 20% payment stages.

### POST `/payments/{payment}/checkout`

Creates provider checkout metadata for `stripe`, `paypal`, `flutterwave`, or `paystack`.

### POST `/payments/webhook/{provider}`

Provider webhook endpoint. Verify signatures before mutating payment state in production.

## Pickup and Delivery Scheduling

### POST `/shipments/{shipment}/pickup`

Schedules pickup window, address, and instructions.

### POST `/shipments/{shipment}/delivery`

Schedules final delivery window and recipient instructions.

## Chat

### GET `/conversations`

Lists user conversations.

### POST `/conversations`

Creates support, admin, shipment, or internal conversation.

### GET `/conversations/{conversation}/messages`

Lists messages.

### POST `/conversations/{conversation}/messages`

Sends text and optional file metadata, broadcasts a Reverb event, and creates notifications.

## Notifications

### GET `/notifications`

Lists in-app notifications.

### PATCH `/notifications/{notification}/read`

Marks a notification as read.

## Admin

### GET `/admin/dashboard`

Revenue, shipment, payment, support, warehouse, and driver metrics.

### GET `/admin/users`

Customer/staff/driver/admin management.

### GET `/admin/support-tickets`

Support queue and ticket SLA status.

