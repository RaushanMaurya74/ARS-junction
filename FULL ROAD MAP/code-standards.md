# Code Standards
## ARS JUNCTION

Version: 1.0

Status: Production Ready

Purpose:
This document defines the mandatory coding standards for the entire ARS JUNCTION platform. Every AI assistant, developer, and contributor must follow these standards.

---

# General Principles

- Write clean, readable, maintainable code.
- Never duplicate business logic.
- Keep every module independent.
- Always fix the root cause instead of adding workarounds.
- Follow SOLID principles.
- Follow DRY (Don't Repeat Yourself).
- Follow KISS (Keep It Simple).
- Follow YAGNI (You Aren't Gonna Need It).

---

# Project Structure

One folder = One responsibility.

Never place business logic inside UI components.

Never access database directly from controllers.

Use layered architecture only.

Controller

↓

Service

↓

Repository

↓

Database

---

# Naming Convention

Folders

lowercase-with-dashes

Example

restaurant-dashboard

customer-profile

payment-service

Files

PascalCase

RestaurantCard.tsx

OrderController.ts

PaymentService.ts

Variables

camelCase

Functions

camelCase

Classes

PascalCase

Interfaces

Prefix I

Example

IUser

IRestaurant

Enums

PascalCase

Constants

UPPER_CASE

API Routes

kebab-case

/api/v1/restaurants

/api/v1/orders

---

# TypeScript Rules

Strict Mode Required

No "any"

Use Interfaces

Use Enums

Readonly where possible

Use Utility Types

No Implicit Any

No Unused Variables

Prefer Type Safety

---

# React Rules

Functional Components Only

Hooks Only

No Class Components

One Component = One Responsibility

Reusable Components

No Large Components

Split Components

Maximum Component Size

300 Lines

If larger

Split Component

---

# Component Structure

Component

↓

Hooks

↓

Services

↓

Utilities

↓

Styles

Never mix everything together.

---

# Tailwind Rules

No Inline Styles

No Hardcoded Colors

Use Theme Variables

Reusable Utility Classes

Mobile First

Responsive Required

Dark Mode Supported

---

# API Rules

REST Architecture

JSON Responses

Versioned APIs

/api/v1/

Standard HTTP Status Codes

Input Validation Required

Output Sanitization Required

Authentication Required

Authorization Required

Rate Limiting Required

---

# Response Format

Success

{
success: true,
data: {},
message: ""
}

Error

{
success: false,
message: "",
errors: []
}

Never return inconsistent response structures.

---

# Authentication Rules

JWT

Refresh Token

Password Hashing

OTP Expiry

Session Management

Role Based Access

Never expose passwords.

---

# Validation Rules

Every request must be validated.

Client Validation

+

Server Validation

Use Zod

Never trust client input.

---

# Error Handling

Centralized Error Handler

Meaningful Messages

No Stack Trace Exposure

Log Internal Errors

Return Safe Errors

---

# Database Rules

Use Prisma ORM

Never Raw SQL unless required

Transactions for multiple updates

Indexes on searchable columns

Foreign Keys

Soft Delete

CreatedAt

UpdatedAt

Audit Logs

---

# Repository Rules

Repository only talks to Database.

No business logic.

Only CRUD.

---

# Service Rules

Business Logic belongs here.

Validation

Calculations

Permissions

Transactions

Notifications

Never put business logic inside controllers.

---

# Controller Rules

Receive Request

Validate Input

Call Service

Return Response

Nothing else.

---

# Security Standards

Helmet

CORS

Rate Limiter

SQL Injection Protection

XSS Protection

CSRF Protection

Secure Cookies

Environment Variables

Encrypted Secrets

Never hardcode credentials.

---

# Logging

Use centralized logger.

Log

Authentication

Payments

Orders

Errors

Warnings

Admin Actions

Audit Logs

Never log passwords.

Never log card details.

---

# File Upload Rules

Allowed Types

jpg

png

jpeg

webp

pdf

Maximum Size

Configurable

Scan uploads

Rename files

Store securely

---

# Performance Rules

Pagination

Lazy Loading

Caching

Database Indexes

Code Splitting

Image Optimization

Virtual Lists

Debouncing

Memoization

---

# Git Standards

Feature Branch

One Feature Per Commit

Meaningful Commit Messages

No Direct Main Push

Code Review Required

---

# Documentation Rules

Every module requires

README

API Docs

Database Docs

Workflow

Usage

Examples

Documentation must stay updated.

---

# Testing Rules

Unit Test

Integration Test

API Test

UI Test

Permission Test

Performance Test

Security Test

Minimum Coverage

80%

---

# Code Review Checklist

✓ Clean Code

✓ Naming Correct

✓ Type Safe

✓ Tested

✓ Responsive

✓ Secure

✓ Documented

✓ No Duplicate Logic

✓ Independent Module

✓ Build Passing

---

# Final Rule

Every line of code must be production-ready.

Never write temporary code.

Never leave unfinished implementations.

Always prioritize maintainability, scalability, and security.

End of Document