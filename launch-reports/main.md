# Launch Report

## TL;DR
Payments + rate-limits coverage exists in multiple places (admin auth throttling; auth pages throttled; Turnstile validation; webhook signature checks), but the payment session endpoints lack explicit API rate limiting/idempotency guards. Additionally, webhook handlers log full payloads at info level and rely on env/default secrets that can degrade security if misconfigured.

## Verdict
Launch ready: No
Security level: medium

## Detailed Report
Scope: payment initiation/verification endpoints + payment webhooks + selected rate-limiting patterns.

What looks good:
- Payment session state transitions are centralized (PaymentSession::transitionTo) with an allowed transition matrix.
- Payment verification is retried via queued VerifyPaymentJob and uses DB row locks to reduce race issues.
- Wallet funding includes a token authorization flow (WalletPaymentProvider::authorizeTransaction) and consumes the auth token.
- Flutterwave and NowPayments webhooks attempt signature verification (Flutterwave via header verif-hash + env/config hash; NowPayments via x-nowpayments-sig + optional HMAC).
- Admin login includes rate limiting (RateLimiter) and lockout-style failures.
- Storefront auth flows (login/register/forgot/reset) include throttling (routes/auth.php uses throttle:10,1 for guest routes; Livewire components also use RateLimiter).

High-risk gaps for payments launch readiness:
- No explicit API rate limiting is applied to the payment session endpoints (show/status/verify/cancel/pay). This creates brute-force/polling/abuse risk (especially verify/cancel/pay, which can trigger payment confirmation and fulfillment side effects).
- PaymentSessionController::verify() accepts a client-provided transaction_id and persists it to the attempt without additional server-side authorization/idempotency checks visible in this controller.
- Webhook handlers log full request payloads at info level, which can expose sensitive payment metadata in logs.
- Webhook signature “secrets” appear to fall back to mock defaults (Flutterwave env default; NowPayments config default). If those fallbacks ever run in non-dev environments, signature verification may effectively be weakened.

Overall: The core payment lifecycle mechanics and some ver

## AI Coding Agent Notes
Target endpoints in this repo:
- app/Http/Controllers/Api/PaymentSessionController.php: pay(), verify(), cancel(), status(), show().
- app/Http/Controllers/Api/Webhooks/FlutterwaveWebhookController.php: handle() for Flutterwave.
- app/Http/Controllers/Api/Webhooks/NowPaymentsWebhookController.php: handle() for NowPayments.

Rate limiting checks found:
- Admin throttling exists: app/Domain/Admin/Services/AdminAuthService.php uses RateLimiter and an email+IP key.
- Guest auth throttling exists: routes/auth.php uses middleware(['guest','throttle:10,1']).
- Login/register Livewire components add additional RateLimiter logic.
- Payments endpoints: no RateLimiter middleware or in-code throttling found in the supplied files for payment session actions/polling.

Payments security checks found:
- VerifyPaymentJob uses DB locks and verifies against provider.
- PaymentSessionService confirms sessions with locking and transitions.
- Webhooks: both attempt signature verification, but logging + mock-fallback secret behavior should be reviewed.

Launch readiness decision rationale:
Because verify/pay endpoints can be called repeatedly and can cause confirmation/fulfillment transitions, missing API throttling/idempotency guards is a blocker for Payments launch under the selected criteria (Payments, Rate limits).

## Fixable Findings
- WARNING: No API rate limiting on payment session verify/pay/cancel endpoints
  - Location: app/Http/Controllers/Api/PaymentSessionController.php:1-220
  - The payment session controller contains state-changing endpoints (pay(), verify(), cancel()) but the supplied code shows no RateLimiter middleware or in-method throttling. This enables excessive polling/verification attempts against session IDs and can increase abuse and gateway costs.
- WARNING: Webhook handlers log full request payloads (possible sensitive data in logs)
  - Location: app/Http/Controllers/Api/Webhooks/FlutterwaveWebhookController.php:1-35
  - Webhook handlers log $request->all() at info level. Payment providers often include sensitive transaction/customer details; this increases exposure via log aggregation.
- WARNING: Webhook signature verification falls back to mock default secrets
  - Location: app/Http/Controllers/Api/Webhooks/FlutterwaveWebhookController.php:1-35
  - FlutterwaveWebhookController uses env('FLUTTERWAVE_SECRET_HASH','FLW_SECRET_HASH_MOCK') and NowPaymentsWebhookController uses config('services.nowpayments.ipn_secret','NOWPAYMENTS_IPN_MOCK'). If these defaults are accidentally active in non-dev/staging environments, signature verification may effectively be bypassed.