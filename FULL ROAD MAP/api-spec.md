# API Specification
## ARS JUNCTION

Version: 1.0

Status: Production Ready

API Version

v1

Base URL

/api/v1

API Style

REST

Content Type

application/json

Authentication

JWT + Refresh Token

Response Format

JSON

---

# Standard Response

## Success

{
  "success": true,
  "message": "Success",
  "data": {}
}

---

## Error

{
  "success": false,
  "message": "Validation Failed",
  "errors": []
}

---

# Authentication APIs

POST

/auth/register

Create Customer Account

---

POST

/auth/login

Customer Login

---

POST

/auth/logout

Logout

---

POST

/auth/refresh-token

Generate New Access Token

---

POST

/auth/forgot-password

Send Reset Link

---

POST

/auth/reset-password

Reset Password

---

POST

/auth/change-password

Change Password

---

POST

/auth/verify-email

Email Verification

---

POST

/auth/verify-phone

Phone Verification

---

GET

/auth/me

Current Logged User

---

# Customer APIs

GET

/customers/profile

---

PUT

/customers/profile

---

GET

/customers/address

---

POST

/customers/address

---

PUT

/customers/address/{id}

---

DELETE

/customers/address/{id}

---

GET

/customers/orders

---

GET

/customers/wallet

---

GET

/customers/notifications

---

# Restaurant APIs

POST

/restaurants/register

---

POST

/restaurants/login

---

GET

/restaurants/profile

---

PUT

/restaurants/profile

---

GET

/restaurants/settings

---

PUT

/restaurants/settings

---

POST

/restaurants/upload-document

---

GET

/restaurants/dashboard

---

GET

/restaurants/earnings

---

POST

/restaurants/withdraw

---

# Menu APIs

GET

/menu/categories

---

POST

/menu/categories

---

PUT

/menu/categories/{id}

---

DELETE

/menu/categories/{id}

---

GET

/menu/items

---

POST

/menu/items

---

GET

/menu/items/{id}

---

PUT

/menu/items/{id}

---

DELETE

/menu/items/{id}

---

PATCH

/menu/items/{id}/availability

---

POST

/menu/items/{id}/images

---

DELETE

/menu/images/{id}

---

# Inventory APIs

GET

/inventory/items

---

POST

/inventory/items

---

PUT

/inventory/items/{id}

---

DELETE

/inventory/items/{id}

---

GET

/inventory/transactions

---

POST

/inventory/stock-in

---

POST

/inventory/stock-out

---

GET

/inventory/low-stock

---

# Cart APIs

GET

/cart

---

POST

/cart/items

---

PUT

/cart/items/{id}

---

DELETE

/cart/items/{id}

---

DELETE

/cart/clear

---

# Order APIs

POST

/orders

Create Order

---

GET

/orders

---

GET

/orders/{id}

---

PATCH

/orders/{id}/cancel

---

PATCH

/orders/{id}/status

---

GET

/orders/history

---

GET

/orders/live-tracking

---

# Payment APIs

POST

/payments/create

---

POST

/payments/verify

---

POST

/payments/refund

---

GET

/payments/history

---

GET

/payments/invoice/{id}

---

# Wallet APIs

GET

/wallet

---

POST

/wallet/add-money

---

POST

/wallet/withdraw

---

GET

/wallet/history

---

# Coupon APIs

GET

/coupons

---

POST

/coupons/apply

---

POST

/coupons/create

---

PUT

/coupons/{id}

---

DELETE

/coupons/{id}

---

# Delivery APIs

POST

/delivery/login

---

GET

/delivery/profile

---

PATCH

/delivery/status

Online / Offline

---

GET

/delivery/orders

---

POST

/delivery/orders/{id}/accept

---

POST

/delivery/orders/{id}/pickup

---

POST

/delivery/orders/{id}/deliver

---

POST

/delivery/location/update

---

GET

/delivery/earnings

---

GET

/delivery/history

---

# Notification APIs

GET

/notifications

---

PATCH

/notifications/read/{id}

---

PATCH

/notifications/read-all

---

DELETE

/notifications/{id}

---

# Review APIs

POST

/reviews/restaurant

---

POST

/reviews/menu

---

GET

/reviews/restaurant/{id}

---

GET

/reviews/menu/{id}

---

# Admin APIs

GET

/admin/dashboard

---

GET

/admin/users

---

GET

/admin/restaurants

---

PATCH

/admin/restaurants/{id}/approve

---

PATCH

/admin/restaurants/{id}/reject

---

GET

/admin/orders

---

GET

/admin/payments

---

GET

/admin/delivery-partners

---

GET

/admin/reports

---

GET

/admin/analytics

---

GET

/admin/settings

---

PUT

/admin/settings

---

# Analytics APIs

GET

/analytics/revenue

---

GET

/analytics/orders

---

GET

/analytics/customers

---

GET

/analytics/restaurants

---

GET

/analytics/delivery

---

GET

/analytics/inventory

---

# Report APIs

GET

/reports/sales

---

GET

/reports/gst

---

GET

/reports/orders

---

GET

/reports/inventory

---

GET

/reports/customers

---

GET

/reports/restaurants

---

GET

/reports/delivery

---

# Upload APIs

POST

/upload/image

---

POST

/upload/document

---

DELETE

/upload/{id}

---

# API Security

JWT Authentication

Refresh Token Rotation

Rate Limiting

Request Validation

Permission Middleware

Input Sanitization

SQL Injection Protection

XSS Protection

CSRF Protection

Audit Logging

---

# HTTP Status Codes

200 OK

201 Created

204 No Content

400 Bad Request

401 Unauthorized

403 Forbidden

404 Not Found

409 Conflict

422 Validation Error

429 Too Many Requests

500 Internal Server Error

503 Service Unavailable

---

# API Versioning

/api/v1/

/api/v2/

/api/v3/

Older versions remain supported until officially deprecated.

---

# Total API Endpoints

Authentication : 10

Customer : 9

Restaurant : 10

Menu : 10

Inventory : 7

Cart : 5

Orders : 6

Payments : 5

Wallet : 4

Coupons : 4

Delivery : 9

Notifications : 4

Reviews : 4

Admin : 10

Analytics : 6

Reports : 7

Uploads : 3

Total : 123+ Production APIs

---

End of API Specification