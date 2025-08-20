# GEMINI Codebase Guide

This document provides a comprehensive guide to the MedCapsule project, including its architecture, development conventions, and a detailed plan for refactoring and improvement.

## Project Overview

MedCapsule is a Laravel-based medical education platform designed for smart learning, progress tracking, and gamified user engagement.

- **Framework:** Laravel 12.0 (PHP 8.2+)
- **Frontend:** Vite, Tailwind CSS, Alpine.js
- **Database:** MySQL (assumed from migrations)
- **Architecture:** MVC with a Service Layer

The platform features a sophisticated written answer evaluation system, a comprehensive gamification system with XP and achievements, and a secure payment system.

## Building and Running

### Dependencies

- **PHP:** `composer install`
- **Node.js:** `npm install`

### Development

- **Frontend:** `npm run dev`
- **Backend:** `php artisan serve`

### Testing

- **Unit & Feature Tests:** `vendor/bin/phpunit`
- **Static Analysis:** `vendor/bin/phpstan analyse --level=9`
- **End-to-End Tests:** Playwright is configured (`playwright.config.js`)

## Development Conventions

- **Coding Style:** Enforced by `pint.json` and `php-cs-fixer`.
- **Static Analysis:** PHPStan is configured to level 9.
- **Testing:** A comprehensive test suite is in place, including unit, feature, integration, and E2E tests.
- **Commits:** Atomic commits are preferred, with clear and concise messages.

## Refactoring and Improvement Plan

A comprehensive refactoring plan is in place to improve the codebase's maintainability, performance, and security. The following is a summary of the plan.

### Immediate Strike Targets (0-2 Weeks)

#### 1. `WrittenAnswerEvaluationService.php` (~2,196 LOC)

- **Problem:** Monolithic service with multiple responsibilities.
- **Plan:** Refactor into a small orchestrator and multiple focused components.
  - `WrittenAnswerEvaluationService.php` (orchestrator)
  - `MedicalTermNormalizer.php`
  - `SimilarityEngine.php`
  - `AnswerNormalizer.php`
  - `EvaluationResultBuilder.php`
  - `AsyncAnswerEvaluationJob.php` (new queued job)

#### 2. `PaymentController.php` (~998 LOC)

- **Problem:** Controller with heavy business logic and weak validation.
- **Plan:** Refactor into a thin HTTP adapter that calls a `PaymentOrchestrator`.
  - `PaymentController.php` (thin controller)
  - `PaymentOrchestrator.php` (new service)
  - `Providers/*` (provider adapters)
  - `ProcessPaymentRequest.php` (Form Request)
  - `AuditLogger.php` (immutable audit storage)

#### 3. `config/medcapsule.php`

- **Problem:** Single configuration file with mixed domains.
- **Plan:** Split into domain-specific configuration files.
  - `config/domains/quiz.php`
  - `config/domains/payment.php`
  - `config/domains/performance.php`
  - `config/domains/security.php`

### Database Consolidation (2-6 Weeks)

- **Problem:** Overlapping migrations and inconsistent index optimizations.
- **Plan:** Create a single consolidation migration and deprecate old migrations.

### Security Hardening

- **Plan:**
  - Implement `auth:sanctum` and rate-limiting for all sensitive API routes.
  - Implement a `RequestFingerprintGuard` for replay protection.
  - Implement an `ImmutableAuditLogger` for all sensitive operations.

### Performance Monitoring

- **Plan:**
  - Implement a `PerformanceGuardian` to wrap heavy calls and queue fallbacks.
  - Integrate with an APM (NewRelic / Datadog) for tracing.

## How to Contribute

1.  Create a new branch for your feature or bug fix.
2.  Write clean, well-documented code that follows the project's conventions.
3.  Write unit and feature tests for your code.
4.  Ensure that all tests pass before submitting a pull request.
5.  Submit a pull request with a clear and concise description of your changes.
