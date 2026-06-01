# RshopRefills V2

**Buy gift cards, eSIMs, mobile top-ups and bill payments. One ecosystem, all your digital solutions.**

RshopRefills is a digital commerce and wallet platform. V2 is a ground-up rebuild on a modern Laravel 12 stack with a domain-driven architecture, a multi-currency wallet, a full rewards economy, multi-gateway payments (card, mobile money, crypto, wallet), automated supplier fulfillment, and an installable PWA experience.

The first product of **Roddy Technologies** - built in Cameroon, serving the world.

---

## Table of contents

- [What's new in V2](#whats-new-in-v2)
- [What it sells](#what-it-sells)
- [Key features](#key-features)
- [Tech stack](#tech-stack)
- [Architecture](#architecture)
- [Getting started](#getting-started)
- [Running the app](#running-the-app)
- [Testing](#testing)
- [Deployment](#deployment)
- [Project structure](#project-structure)
- [Credits](#credits)

---

## What's new in V2

V2 is not a patch on V1 - it is a rebuild around reliability, money-safety and scale.

- **Domain-driven architecture.** Business logic lives in 19 isolated domains (Wallet, Payment, Ledger, Fulfillment, Fraud, Rewards, and more) instead of fat controllers.
- **Money-safe wallet + ledger.** Balances are locked then debited through an auditable ledger, with scheduled reconciliation jobs that detect and repair drift.
- **Multi-gateway checkout.** Card, mobile money, crypto and wallet in one flow, with region-aware payment-method selection.
- **RCOIN rewards economy.** Earn, redeem, refer, withdraw and convert - a complete loyalty layer, not a points counter.
- **Automated supplier fulfillment.** Orders fulfill asynchronously through provider integrations with webhooks, polling and retries.
- **Security hardening.** Global transaction-PIN enforcement, KYC tiers, Cloudflare Turnstile on every sensitive gate, fraud detection and a full audit trail.
- **Installable PWA.** Manifest, service worker and an iOS-aware install experience so the storefront behaves like a native app.
- **Search-ready SEO.** Centralized meta, Open Graph + Twitter cards, Organization JSON-LD and a dynamic sitemap covering every public page.

## What it sells

| Product | Description |
| --- | --- |
| **Gift cards** | Hundreds of global brands, one card per brand, region-aware pricing. |
| **eSIMs** | Travel data plans with QR + manual activation, top-ups and low-data alerts. |
| **Mobile top-ups** | Airtime and data bundles for operators worldwide. |
| **Bill payments** | Prepaid utilities and services. |
| **Flights & stays** | Travel booking surfaces. |

## Key features

**Wallet & money**
- Multi-currency wallets (USD, NGN, GBP, GHS, XAF and RCOIN) with live FX conversion.
- Lock/debit ledger flow, manual admin credit/debit with mandatory reason + audit, and automated balance reconciliation jobs.
- Transaction PIN required on every wallet payment, with attempt lockout protection.

**Payments**
- Flutterwave (cards + mobile money), NowPayments (crypto) and Wallet, plus RCOIN, coupons and saved cards - with webhook verification and pending-funding recovery.

**Rewards (RCOIN)**
- Cashback on purchases, referral credits, redemption, withdrawal and conversion, with a per-user earnings multiplier admin control.

**Catalog & fulfillment**
- Synced catalog from suppliers (Zendit gift cards / top-ups / eSIMs, Airalo eSIMs), one-card-per-brand normalization, and asynchronous fulfillment with webhooks, polling and retries on dedicated queues.

**Accounts & trust**
- Email + KYC verification tiers with a Facebook-style verified badge, social login (Socialite), and suspension / ban / funds-hold controls.

**Admin operations**
- Customer management (edit, ban, suspend, hold funds, set KYC, reset transaction PIN, send password reset, log in as customer), catalog / commerce / fintech / notification dashboards, plus a CMS for blog, press, FAQs and reviews.

**Notifications**
- Multi-channel delivery (in-app database + email via Resend) with preferences, async dispatch, delivery monitoring and auto-retry.

**Experience**
- Country / language / currency switching, dark mode, installable PWA, and a unified design system (10px radius, shared badge + component library).

## Tech stack

- **PHP** 8.2, **Laravel** 12
- **Livewire** 4 + **Volt** 1 (single-file components), **Flux UI** 2
- **Laravel Horizon** 5 (Redis queues), **Socialite** 5
- **Tailwind CSS** 4 + Vite, Alpine.js, GSAP
- **PHPUnit** 11, **Laravel Pint** (code style), **Laravel Pail** (logs)

## Architecture

Business logic is organized into self-contained domains under `app/Domain`:

```
Admin  Audit  Auth  Cart  Catalog  Checkout  Fraud  Fulfillment
Ledger  Notification  Order  Payment  Product  Reconciliation
Rewards  Security  Shared  Transaction  Wallet
```

Each domain owns its Services, Providers, Jobs, Events, Listeners, Enums and Exceptions. HTTP controllers and Livewire/Volt components stay thin and delegate into the domain layer.

## Getting started

**Requirements:** PHP 8.2+, Composer, Node 18+, a database (MySQL/PostgreSQL), and Redis (for queues/Horizon).

```bash
# 1. Clone + install
git clone https://github.com/Roddy-Tech-Group/rshoprefills-v2.git
cd rshoprefills-v2
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database
php artisan migrate --seed

# 4. Storage
php artisan storage:link
```

Configure your `.env` for: database, Redis, mail (Resend), payment gateways (Flutterwave, NowPayments), suppliers (Zendit, Airalo), Cloudflare Turnstile, and Socialite (Google).

## Running the app

```bash
# All-in-one dev runtime (server, queue, logs, vite)
composer run dev

# Or run the pieces individually
php artisan serve
php artisan queue:listen      # or: php artisan horizon
npm run dev
```

Build assets for production with `npm run build`.

## Testing

```bash
php artisan test --compact            # full suite
php artisan test --filter=SomeTest    # a single test
vendor/bin/pint                       # fix code style
```

## Deployment

- Run `npm run build`, then cache config / routes / views:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- Run **Horizon** as the queue worker (requires the `pcntl` extension - available on Linux, not Windows local dev).
- Serve over **HTTPS** so the PWA is installable and the service worker is active.
- Submit `/sitemap.xml` to Google and Bing Search Console.

## Project structure

```
app/
  Domain/             # Business logic by domain (services, providers, jobs, events)
  Http/Controllers/   # Thin HTTP layer (web + API + admin)
  Models/             # Eloquent models
  Jobs/               # Queued jobs (fulfillment, refunds, reconciliation)
resources/
  views/              # Blade + Livewire/Volt views (storefront, dashboard, admin)
  js/ | css/          # Frontend (Alpine, GSAP, Tailwind)
routes/
  web.php | admin.php # Storefront/dashboard + admin routes
tests/                # PHPUnit feature + unit tests
```

## Credits

Built by **Roddy Technologies**.

- **Divine Ofeh** - Chief Executive Officer (CEO)
- **Johnpaul** - Chief Technology Officer (CTO)

Building an Africa-to-international digital ecosystem, from Cameroon to the world. RshopRefills is the team's first product, with continuous updates shipping to solve real problems for the community.
