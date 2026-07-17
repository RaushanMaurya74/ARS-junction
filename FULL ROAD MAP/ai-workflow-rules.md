# AI Workflow Rules
## ARS JUNCTION

Version: 1.0

Status: Production Ready

Purpose:
These rules are mandatory for every AI agent (Antigravity, Cursor, Claude Code, Windsurf, Copilot, etc.) that works on the ARS JUNCTION codebase.

---

# Primary Objective

Build a production-grade Multi-Restaurant Food Ordering & Restaurant ERP platform.

The AI must never sacrifice architecture for speed.

Quality always has higher priority than completion speed.

---

# Golden Rules

Never break existing functionality.

Never rewrite completed modules without approval.

Never mix unrelated modules.

Keep every module independent.

Always follow SOLID principles.

Always produce production-ready code.

No placeholder implementation.

No TODO comments.

No fake APIs.

No dummy logic.

Everything must be functional.

---

# Development Workflow

Step 1

Read all documentation.

↓

Read Architecture.

↓

Read Project Overview.

↓

Read Database Schema.

↓

Read API Specification.

↓

Read Progress Tracker.

↓

Start implementation.

Never skip documentation.

---

# Module Rule

One task = One module.

Example

✔ Authentication

Finish completely.

Test completely.

Update documentation.

Commit.

Then move to Restaurant.

Never build two unrelated modules together.

---

# Independent Module Rule

Authentication

↓

Restaurant

↓

Customer

↓

Menu

↓

Inventory

↓

Orders

↓

Payment

↓

Delivery

↓

Notification

↓

Analytics

↓

Admin

Every module must work independently.

---

# Documentation Rule

Whenever implementation changes,

AI must update

Architecture

Progress Tracker

API Docs

Database Docs

Feature Docs

No documentation should become outdated.

---

# Before Writing Code

Understand feature.

Read dependencies.

Read interfaces.

Read database.

Read APIs.

Read coding standards.

Only then write code.

---

# Forbidden Actions

Never delete existing code.

Never rename folders without reason.

Never change database structure randomly.

Never break API response.

Never hardcode secrets.

Never disable authentication.

Never ignore validation.

Never bypass permissions.

---

# UI Rules

Use design system.

No random colors.

No inline CSS.

Responsive first.

Accessibility required.

Dark mode supported.

Animations optimized.

Loading states required.

Error states required.

Empty states required.

---

# Backend Rules

Use Service Layer.

Use Repository Pattern.

Use DTOs.

Validate every request.

Use Transactions.

Handle Exceptions.

Log Errors.

Never expose stack traces.

---

# API Rules

REST only.

Version every API.

Authentication required.

Authorization required.

Validation required.

Pagination required.

Filtering supported.

Sorting supported.

Consistent response format.

---

# Database Rules

Normalize data.

Use indexes.

Use foreign keys.

Soft delete where required.

Audit logs enabled.

No duplicated data.

Never store business logic inside database.

---

# Security Rules

JWT Authentication.

Refresh Tokens.

Password Hashing.

Helmet.

CORS.

Rate Limiting.

CSRF Protection.

SQL Injection Protection.

XSS Protection.

Input Sanitization.

Encrypted Environment Variables.

---

# Performance Rules

Lazy Loading.

Pagination.

Caching.

Database Indexes.

Image Optimization.

Background Jobs.

Queue Processing.

Compression.

Code Splitting.

---

# Testing Rules

Every feature requires

Unit Test

Integration Test

API Test

UI Test

Error Test

Permission Test

No feature is complete without testing.

---

# Build Rule

After every completed feature

Run Build

Run Tests

Run Lint

Fix Errors

Update Progress Tracker

Commit Changes

Only then continue.

---

# Git Workflow

feature/auth

feature/customer

feature/menu

feature/orders

feature/payment

feature/delivery

feature/admin

Never develop directly on main.

---

# Error Handling

Every API must return

Success Response

Validation Error

Unauthorized

Forbidden

Not Found

Conflict

Internal Error

Never crash application.

---

# Logging

Authentication Logs

Payment Logs

Order Logs

Restaurant Logs

Delivery Logs

Admin Logs

System Logs

Errors Logs

Audit Logs

---

# AI Completion Checklist

Before marking any task complete

✓ Build passes

✓ Tests pass

✓ No lint errors

✓ Documentation updated

✓ API documented

✓ Database documented

✓ Responsive UI

✓ Accessibility checked

✓ Security verified

✓ Performance acceptable

✓ Module independent

Only after all checks pass can AI continue to the next task.

---

# Final Rule

The AI must behave like a senior software architect.

Never guess requirements.

Never invent business logic.

Follow documentation first.

Architecture is the highest priority.

End of Document