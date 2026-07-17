# Database Schema
## ARS JUNCTION

Version: 1.0

Status: Production Ready

Database
PostgreSQL 16

ORM
Prisma ORM

Purpose
This document defines the official database architecture of ARS JUNCTION.

Every table must follow these standards.

---

# Global Rules

Every table contains

id

createdAt

updatedAt

deletedAt (Soft Delete)

createdBy

updatedBy

status

uuid

---

# Authentication Module

## users

id

uuid

fullName

email

phone

passwordHash

roleId

avatar

emailVerified

phoneVerified

isActive

lastLogin

createdAt

updatedAt

---

## roles

id

name

description

priority

---

## permissions

id

module

action

description

---

## role_permissions

roleId

permissionId

---

## refresh_tokens

id

userId

token

expiresAt

device

ipAddress

---

## sessions

id

userId

device

browser

platform

ip

lastActivity

expiresAt

---

# Customer Module

## customer_profiles

userId

gender

dob

walletBalance

loyaltyPoints

preferredLanguage

---

## customer_addresses

id

userId

type

receiverName

phone

house

landmark

city

state

country

postalCode

latitude

longitude

isDefault

---

## customer_notifications

id

userId

title

message

type

isRead

---

## wishlist

id

userId

restaurantId

menuItemId

---

# Restaurant Module

## restaurants

id

ownerId

restaurantName

slug

description

logo

banner

email

phone

gstNumber

fssaiNumber

openingTime

closingTime

latitude

longitude

address

city

state

country

postalCode

rating

deliveryRadius

isVerified

isOpen

---

## restaurant_documents

id

restaurantId

documentType

documentUrl

verificationStatus

---

## restaurant_bank_accounts

id

restaurantId

accountHolder

accountNumber

ifsc

bankName

upiId

---

## restaurant_settings

restaurantId

minimumOrder

deliveryCharge

packingCharge

gstEnabled

autoAcceptOrders

---

# Menu Module

## menu_categories

id

restaurantId

name

image

priority

status

---

## menu_items

id

restaurantId

categoryId

name

slug

description

price

discountPrice

preparationTime

vegType

stock

sku

barcode

image

thumbnail

rating

isAvailable

---

## menu_images

id

menuItemId

imageUrl

priority

---

## addons

id

restaurantId

name

price

---

## menu_item_addons

menuItemId

addonId

---

# Inventory Module

## inventory_items

id

restaurantId

name

sku

barcode

purchasePrice

sellingPrice

unit

openingStock

currentStock

minimumStock

supplierId

---

## inventory_transactions

id

inventoryId

type

quantity

reference

remarks

---

## suppliers

id

restaurantId

companyName

ownerName

phone

email

address

gstNumber

---

# Cart Module

## carts

id

customerId

restaurantId

total

tax

discount

deliveryCharge

grandTotal

---

## cart_items

cartId

menuItemId

quantity

price

addonPrice

subtotal

---

# Order Module

## orders

id

orderNumber

customerId

restaurantId

deliveryPartnerId

paymentId

couponId

status

paymentStatus

subtotal

discount

tax

deliveryCharge

grandTotal

estimatedDelivery

otp

---

## order_items

id

orderId

menuItemId

quantity

price

addonPrice

subtotal

---

## order_status_history

id

orderId

status

remarks

updatedBy

timestamp

---

# Payment Module

## payments

id

orderId

gateway

transactionId

amount

currency

status

paymentMethod

paidAt

---

## refunds

id

paymentId

amount

reason

status

processedAt

---

# Wallet Module

## wallets

id

userId

balance

currency

---

## wallet_transactions

walletId

type

amount

reference

remarks

---

# Coupon Module

## coupons

id

code

title

description

discountType

discountValue

minimumOrder

maximumDiscount

startDate

endDate

usageLimit

status

---

## coupon_usage

couponId

customerId

orderId

usedAt

---

# Delivery Module

## delivery_partners

id

userId

vehicleType

vehicleNumber

licenseNumber

aadhaarNumber

isOnline

rating

---

## delivery_assignments

orderId

deliveryPartnerId

assignedAt

acceptedAt

pickedAt

deliveredAt

---

## live_locations

deliveryPartnerId

latitude

longitude

speed

heading

updatedAt

---

# Review Module

## restaurant_reviews

id

restaurantId

customerId

orderId

rating

review

---

## menu_reviews

id

menuItemId

customerId

rating

review

---

# Notification Module

## notifications

id

userId

title

message

channel

isRead

sentAt

---

# CMS Module

## banners

id

title

image

link

priority

status

---

## pages

id

title

slug

content

---

# Analytics Module

## analytics_daily

date

orders

sales

customers

restaurants

deliveries

---

## analytics_monthly

month

year

revenue

orders

profit

---

# Audit Module

## audit_logs

id

userId

module

action

oldValue

newValue

ip

device

createdAt

---

# Relationships

Users
↓

Customer Profile

Restaurant

Delivery Partner

Wallet

Notifications

↓

Orders

↓

Payments

↓

Reports

---

Restaurant

↓

Menu

Inventory

Orders

Analytics

Delivery

Reports

---

Orders

↓

Order Items

Payments

Coupons

Delivery

Reviews

Invoices

---

# Database Indexes

email

phone

restaurantName

menuName

orderNumber

transactionId

couponCode

createdAt

status

latitude

longitude

---

# Constraints

Unique Email

Unique Phone

Unique Order Number

Unique Transaction ID

Unique Restaurant Slug

Unique Coupon Code

---

# Future Tables

Subscriptions

Membership

Referral

Gift Cards

Loyalty

Tax Reports

POS

Kitchen Display

Table Booking

QR Ordering

AI Recommendations

Chat Support

Multi Vendor Settlement

---

End of Database Schema