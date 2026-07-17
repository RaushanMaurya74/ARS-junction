# Authentication & Authorization
## ARS JUNCTION

Version: 1.0

Status: Production Ready

Purpose

This document defines the authentication, authorization, session management, permissions, and security architecture of ARS JUNCTION.

---

# Authentication System

Authentication Method

JWT Access Token

+

Refresh Token

+

Role Based Access Control (RBAC)

---

# Supported Login Methods

Customer

Email + Password

Phone + Password

Google Login (Future)

Apple Login (Future)

---

Restaurant Owner

Email

Password

---

Delivery Partner

Phone

Password

OTP (Optional)

---

Admin

Email

Password

2FA (Recommended)

---

# User Roles

SUPER_ADMIN

ADMIN

CUSTOMER

RESTAURANT_OWNER

RESTAURANT_MANAGER

CHEF

CASHIER

DELIVERY_PARTNER

SUPPLIER

SUPPORT_AGENT

AUDITOR

ACCOUNTANT

Each role has separate permissions.

---

# Registration Flow

Customer

Register

↓

Email Verification

↓

Phone Verification

↓

Login

↓

Profile Completion

↓

Ready

---

Restaurant

Register

↓

Business Details

↓

GST

↓

FSSAI

↓

Bank Details

↓

Document Upload

↓

Admin Review

↓

Approval

↓

Dashboard Access

---

Delivery Partner

Register

↓

Upload Aadhaar

↓

Driving License

↓

Vehicle Details

↓

Background Verification

↓

Approval

↓

Login

---

# Login Flow

Email

↓

Password

↓

Validation

↓

JWT Token

↓

Refresh Token

↓

Store Session

↓

Login Success

---

# JWT Structure

Access Token

15 Minutes

Refresh Token

30 Days

Rotate Refresh Token

Every Refresh Request

---

# Session Management

Each Login Creates

Device

Browser

IP

Platform

Location

Last Activity

Expiration

---

# Logout

Remove Session

Invalidate Refresh Token

Remove Device

Log Activity

---

# Password Rules

Minimum 8 Characters

Uppercase

Lowercase

Number

Special Character

No Common Password

No Username Inside Password

---

# Password Hashing

bcrypt

Cost Factor

12

Never Store Plain Password

---

# Forgot Password

Email

↓

Verification Token

↓

Reset Password

↓

Invalidate Old Sessions

↓

Login Again

---

# Email Verification

Verification Link

Expires

30 Minutes

Single Use

---

# Phone Verification

6 Digit OTP

Expires

5 Minutes

Maximum Attempts

5

---

# Account Lock

Failed Login Attempts

5

↓

Temporary Lock

15 Minutes

↓

Notify User

---

# Role Based Access

Customer

Order Food

Wallet

Reviews

Profile

Addresses

---

Restaurant Owner

Restaurant

Orders

Menu

Inventory

Analytics

Reports

Delivery

Wallet

---

Delivery Partner

Assigned Orders

Live Tracking

Wallet

Earnings

Profile

---

Admin

Everything

---

# Permission Format

restaurant.read

restaurant.create

restaurant.update

restaurant.delete

orders.read

orders.update

orders.cancel

payments.read

payments.refund

analytics.read

users.manage

roles.manage

permissions.manage

---

# Middleware

Authentication

Authorization

Permission

Validation

Rate Limiter

Security

Audit Logger

---

# Refresh Token Rotation

Old Refresh Token

↓

Validate

↓

Generate New Token

↓

Delete Old Token

↓

Save New Token

---

# Device Management

Trusted Devices

Current Device

Recent Devices

Logout Current

Logout All

Remove Device

---

# Security Headers

Helmet

Content Security Policy

Frame Protection

HSTS

No Sniff

Referrer Policy

---

# Session Expiration

Access Token

15 Minutes

Refresh Token

30 Days

Inactive Session

7 Days

Admin Session

12 Hours

---

# Audit Logging

Login

Logout

Password Change

Email Change

Role Change

Permission Change

Profile Update

Restaurant Approval

Payment Refund

Every Sensitive Action Logged

---

# Authorization Matrix

Customer

✓ Own Profile

✓ Own Orders

✓ Own Wallet

✗ Restaurant Dashboard

✗ Admin

---

Restaurant

✓ Own Restaurant

✓ Own Menu

✓ Own Inventory

✓ Own Orders

✗ Other Restaurants

✗ Admin

---

Delivery

✓ Assigned Orders

✓ Own Earnings

✗ Customer Data

✗ Restaurant Settings

---

Admin

✓ Full Access

---

# Security Rules

Never expose passwords.

Never expose JWT Secret.

Never trust client role.

Always validate permissions.

Always verify ownership.

Always log sensitive operations.

Never hardcode secrets.

Never bypass authentication.

---

# Future Authentication

Google OAuth

Apple Login

Microsoft Login

Biometric Login

Passkeys

Multi Factor Authentication

WebAuthn

---

# Authentication Checklist

✓ Secure Password Hashing

✓ JWT

✓ Refresh Token

✓ Session Management

✓ Device Tracking

✓ Role Based Access

✓ Permission Middleware

✓ Audit Logs

✓ Security Headers

✓ Rate Limiting

✓ Email Verification

✓ Phone Verification

Production Ready

---

End of Authentication & Authorization