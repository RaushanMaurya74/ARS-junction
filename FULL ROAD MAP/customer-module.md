# Customer Module
## ARS JUNCTION

Version: 1.0

Status: Production Ready

Purpose

The Customer Module manages the complete customer experience from account creation to order completion, wallet management, reviews, and profile management.

---

# Module Responsibilities

Customer Registration

Authentication

Profile Management

Address Management

Restaurant Discovery

Food Search

Cart

Checkout

Order Management

Live Tracking

Wallet

Coupons

Reviews

Notifications

Support

Settings

---

# Customer Journey

Open App

↓

Register/Login

↓

Location Detection

↓

Browse Restaurants

↓

Search Food

↓

Select Items

↓

Add to Cart

↓

Checkout

↓

Apply Coupon

↓

Payment

↓

Restaurant Accepts

↓

Food Preparation

↓

Delivery Assigned

↓

Live Tracking

↓

Delivered

↓

Rate Restaurant

↓

Order Completed

---

# Customer Dashboard

Home

Search

Offers

Categories

Nearby Restaurants

Popular Restaurants

Recommended

Recent Orders

Notifications

Wallet

Profile

Support

---

# Home Screen

Top Navigation

Current Address

Search Bar

Offer Banner

Food Categories

Popular Restaurants

Nearby Restaurants

Recommended Foods

Top Rated

Quick Reorder

Footer Navigation

---

# Search System

Search Restaurant

Search Food

Search Category

Search Cuisine

Search Location

Search Recent

Voice Search (Future)

AI Search (Future)

---

# Restaurant Details

Restaurant Information

Rating

Reviews

Delivery Time

Distance

Minimum Order

Menu

Offers

Gallery

Restaurant Details

Open / Closed Status

---

# Menu View

Categories

Veg

Non-Veg

Popular

Recommended

Offers

Addons

Variants

Reviews

Nutrition (Future)

---

# Cart

Add Item

Remove Item

Increase Quantity

Decrease Quantity

Special Instructions

Apply Coupon

Delivery Charge

GST

Packing Charge

Grand Total

---

# Checkout

Delivery Address

Delivery Time

Payment Method

Coupon

Wallet

Order Summary

GST Invoice

Place Order

---

# Payment Methods

Cash on Delivery

Razorpay

Wallet

UPI

Credit Card

Debit Card

Net Banking

---

# Orders

Current Orders

Upcoming Orders

Completed Orders

Cancelled Orders

Order Details

Download Invoice

Reorder

Rate Order

---

# Live Order Tracking

Order Confirmed

Preparing Food

Ready For Pickup

Delivery Assigned

Out For Delivery

Delivered

Real-Time Map

ETA

Delivery Partner Details

OTP Verification

---

# Wallet

Current Balance

Add Money

Wallet History

Refund History

Offers

Cashback

Transactions

---

# Coupons

Available Coupons

My Coupons

Apply Coupon

Coupon History

Coupon Terms

---

# Reviews

Restaurant Review

Food Review

Delivery Review

Photo Upload

Edit Review

Delete Review

---

# Profile

Photo

Name

Email

Phone

Date of Birth

Gender

Language

Notification Preferences

Delete Account

---

# Address Management

Home

Office

Other

GPS Location

Default Address

Edit Address

Delete Address

---

# Notifications

Orders

Offers

Wallet

Coupons

Restaurant

System Alerts

Promotions

---

# Support

Live Chat

Email

Phone

FAQ

Raise Ticket

Track Ticket

---

# Business Rules

Customer cannot place an order from multiple restaurants in one cart.

Only verified users can place orders.

Wallet balance cannot become negative.

Coupon validation is mandatory.

Delivery address is required.

Payment must be successful before online order confirmation.

COD availability depends on restaurant settings.

---

# Permissions

View Own Profile

Update Own Profile

Manage Own Addresses

Place Orders

Cancel Eligible Orders

Rate Completed Orders

Use Wallet

Apply Coupons

View Notifications

Cannot access other users' data.

---

# Database Mapping

users

customer_profiles

customer_addresses

carts

cart_items

orders

order_items

wallets

wallet_transactions

coupons

coupon_usage

notifications

restaurant_reviews

menu_reviews

---

# APIs Used

/auth/*

/customers/*

/restaurants/*

/menu/*

/cart/*

/orders/*

/payments/*

/wallet/*

/coupons/*

/notifications/*

/reviews/*

---

# Error Handling

Restaurant Closed

Out of Stock

Invalid Coupon

Payment Failed

Address Missing

Wallet Insufficient

Network Error

Unauthorized

Order Cancelled

Session Expired

---

# Security

JWT Authentication

HTTPS Only

Secure Payments

Rate Limiting

Input Validation

Device Tracking

Audit Logging

---

# Future Features

AI Food Recommendation

Scheduled Orders

Group Ordering

Table Reservation

Subscription Meals

Loyalty Program

Referral Rewards

Voice Ordering

Nutrition Information

---

# Completion Checklist

✓ Registration

✓ Login

✓ Profile

✓ Addresses

✓ Search

✓ Restaurant

✓ Menu

✓ Cart

✓ Checkout

✓ Payments

✓ Orders

✓ Tracking

✓ Wallet

✓ Coupons

✓ Reviews

✓ Notifications

✓ Support

Production Ready

---

End of Customer Module