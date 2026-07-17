# Folder Structure
## ARS JUNCTION

Version: 1.0

Status: Production Ready

Purpose:
This document defines the official folder structure of the ARS JUNCTION platform. Every developer and AI agent must follow this structure. No module may violate these boundaries.

---

# Root Structure

ars-junction/

apps/

packages/

backend/

database/

docs/

scripts/

docker/

.github/

public/

assets/

tests/

.env.example

docker-compose.yml

package.json

README.md

---

# Applications

apps/

customer-app/

restaurant-dashboard/

delivery-dashboard/

admin-dashboard/

landing-page/

shared/

---

# Customer App

customer-app/

src/

app/

pages/

components/

layouts/

hooks/

services/

store/

contexts/

api/

assets/

styles/

utils/

constants/

types/

validators/

routes/

providers/

---

# Restaurant Dashboard

restaurant-dashboard/

src/

dashboard/

components/

pages/

orders/

menu/

inventory/

analytics/

wallet/

settings/

delivery/

reports/

hooks/

store/

services/

api/

utils/

types/

validators/

---

# Delivery Dashboard

delivery-dashboard/

src/

dashboard/

orders/

tracking/

earnings/

wallet/

profile/

notifications/

components/

services/

store/

hooks/

utils/

---

# Admin Dashboard

admin-dashboard/

src/

dashboard/

users/

restaurants/

orders/

payments/

delivery/

analytics/

cms/

reports/

roles/

permissions/

audit/

settings/

components/

hooks/

services/

store/

---

# Backend Structure

backend/

src/

config/

controllers/

services/

repositories/

middleware/

routes/

validators/

dto/

entities/

events/

queues/

cron/

socket/

notifications/

emails/

payments/

storage/

uploads/

logs/

utils/

constants/

types/

exceptions/

interceptors/

database/

tests/

---

# Database

database/

schema/

migrations/

seed/

backup/

scripts/

indexes/

functions/

views/

---

# Shared Packages

packages/

ui/

hooks/

types/

validators/

api-client/

theme/

constants/

utils/

icons/

---

# Documentation

docs/

project-overview.md

architecture.md

ai-workflow-rules.md

code-standards.md

ui-context.md

folder-structure.md

database-schema.md

api-spec.md

authentication.md

authorization.md

customer-module.md

restaurant-module.md

delivery-module.md

admin-module.md

inventory-module.md

billing-module.md

order-management.md

payment-system.md

notification-system.md

analytics.md

security-rules.md

deployment.md

testing.md

roadmap.md

progress-tracker.md

---

# Public Folder

public/

images/

icons/

logos/

fonts/

videos/

manifest.json

robots.txt

favicon.ico

---

# Assets

assets/

illustrations/

backgrounds/

banners/

placeholders/

animations/

audio/

videos/

---

# Configuration

config/

database.ts

redis.ts

socket.ts

jwt.ts

mail.ts

cloudinary.ts

payment.ts

storage.ts

env.ts

---

# API Structure

api/

auth/

customers/

restaurants/

menus/

inventory/

orders/

payments/

wallet/

coupons/

delivery/

notifications/

reports/

analytics/

admin/

cms/

settings/

---

# Services

Each module owns its own service.

Example

RestaurantService

OrderService

PaymentService

WalletService

InventoryService

NotificationService

DeliveryService

AnalyticsService

---

# Repository Layer

Each module has one repository.

RestaurantRepository

OrderRepository

CustomerRepository

InventoryRepository

WalletRepository

PaymentRepository

---

# Validation

validators/

auth/

customer/

restaurant/

menu/

inventory/

orders/

payments/

delivery/

admin/

---

# Types

types/

api/

database/

models/

responses/

requests/

enums/

events/

---

# Constants

constants/

roles.ts

permissions.ts

routes.ts

messages.ts

status.ts

order.ts

payment.ts

inventory.ts

wallet.ts

---

# Middleware

Authentication

Authorization

Rate Limiter

Logger

Error Handler

Validation

Security

Upload

---

# Queue System

Email Queue

SMS Queue

Notification Queue

Invoice Queue

Analytics Queue

Report Queue

Image Queue

---

# Socket Events

Order Created

Order Accepted

Order Ready

Delivery Assigned

Delivery Started

Location Updated

Order Delivered

Notification Received

---

# Upload Structure

uploads/

restaurants/

products/

users/

documents/

invoices/

reports/

exports/

---

# Logs

logs/

application/

api/

payment/

orders/

security/

audit/

system/

---

# Testing Structure

tests/

unit/

integration/

api/

performance/

security/

ui/

e2e/

---

# Development Rules

Every module must have

Controller

Service

Repository

Validator

Routes

Types

Tests

Documentation

No module may directly access another module's files.

Communication must happen through services or APIs only.

---

# Final Rule

Folder structure is part of the architecture.

Changing it requires updating:

Architecture.md

Code Standards.md

Progress Tracker.md

No shortcuts.

No random folders.

No mixed responsibilities.

End of Document