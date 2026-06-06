# Launch Report

## TL;DR
Targeted launch security check (Payments, Rate limits) on the supplied codebase files. The payment/webhook flows include several good controls (signature verification with fail-closed, transaction locking, and state-machine transitions). However, the “verify” and “pay” endpoints allow repeated gateway interactions without any explicit per-session/per-attempt rate limiting, and there are a couple of local correctness issues that can lead to unintended behavior during terminal-state handling and (

## Verdict
Launch ready: No
Security level: medium

## Detailed Report
## Scope
- Payments: payment session lifecycle, explicit verify/pay endpoints, webhook handlers, wallet/crypto providers.
- Rate limits: login/admin throttling and any throttling on payment endpoints.

## What looks good
- Webhook signature verification is implemented for both Flutterwave and NowPayments and appears fail-closed (rejects when secret/config/signature missing).
- Payment confirmations and verification are guarded by DB transactions and locks in the session service and verify job.
- PaymentSession state transitions are validated against an allowed transition matrix.
- Admin login has throttling via RateLimiter and login recording.
- Customer login/signup/password reset flows include rate limiting and (optionally) Cloudflare Turnstile.

## Key gaps for launch (Payments + Rate limits)
- **No explicit rate limiting / abuse controls on payment session endpoints** (`pay`, `verify`, `status`, `cancel`). These endpoints can trigger gateway calls and/or fulfillment logic. While the session state machine prevents some invalid transitions, it does not cap repeated calls while a session remains in non-terminal states.
- **Wallet funding / PIN auth flows depend on a verification token**, but the payment session endpoints that call into authorization/confirm logic are not throttled.

## Overall launch decision
Because the scope requires *Payments* and *Rate limits*, the missing throttling on high-impact payment endpoints is a blocker for launch-readiness. Recommended immediate remediation is to add endpoint-level rate limiting (per user + per session/attempt) for `api.payment-sessions.pay`, `api.payment-sessions.verify`, and polling where appropriate.

## Findings
See the concrete, fixable items below (limited to issues visible in supplied files).

## AI Coding Agent Notes
### Where to focus (concrete)
1) **Throttle the explicit payment endpoints** in `app/Http/Controllers/Api/PaymentSessionController.php`:
   - `pay(string $id, Request $request)` (gateway charges, wallet authorization)
   - `verify(string $id, Request $request)` (calls gateway verifyPayment and can trigger confirmation + fulfillment)
   - also consider throttling `status()` and `cancel()`.
   
2) Confirm that your API routes apply throttling middleware for these endpoints (not shown in supplied files). If not, implement local throttling in the controller or add middleware per route group.

### Payment correctness controls already present
- Webhook signature verification:
  - `app/Http/Controllers/Api/Webhooks/FlutterwaveWebhookController.php`
  - `app/Http/Controllers/Api/Webhooks/NowPaymentsWebhookController.php`
- Concurrency/locking during verification and session confirmation:
  - `app/Jobs/VerifyPaymentJob.php`
  - `app/Domain/Payment/Services/PaymentSessionService.php`

### Rate limiting coverage already present (non-payment)
- Admin login throttling:
  - `app/Domain/Admin/Services/AdminAuthService.php`
- Customer auth throttling and Turnstile in Livewire components and `routes/auth.php`.

## Fixable Findings
- WARNING: Verify endpoint triggers gateway verification/fulfillment without any explicit per-session rate limiting
  - Location: app/Http/Controllers/Api/PaymentSessionController.php:55-165
  - `PaymentSessionController::verify()` calls the gateway `verifyPayment()` and, on success, confirms the session and dispatches fulfillment jobs (order items) and/or wallet funding processing. The controller contains no explicit throttling/attempt caps, so a client can repeatedly hammer `verify` for the same session (or across sessions) while the session is non-terminal, potentially causing excessive gateway API calls and (depending on idempotency elsewhere) repeated expensive work.

Fix locally by adding Laravel RateLimiter (or middleware) for this endpoint keyed by user_id + payment_session_id (and optionally payment_attempt_id).
- WARNING: Pay endpoint triggers gateway charges/authorizations without any explicit per-session/per-user rate limiting
  - Location: app/Http/Controllers/Api/PaymentSessionController.php:167-390
  - `PaymentSessionController::pay()` performs gateway charges (card/bank_transfer/apple_pay/mobile_money/crypto/hosted flows) or wallet authorization, but has no explicit throttling in this controller. A malicious client can repeatedly call `pay` for the same session while it is still in a non-terminal state, likely creating repeated gateway charge attempts and/or wasting resources.

Fix locally by adding RateLimiter logic in `pay()` keyed by user_id + payment_session_id + (method) and returning 429 when the limit is exceeded.
- WARNING: Polling and cancel endpoints lack explicit rate limiting controls
  - Location: app/Http/Controllers/Api/PaymentSessionController.php:27-85
  - `PaymentSessionController::status()` and `cancel()` are also high-frequency / state-changing operations for payment sessions and are not explicitly throttled in the controller. Polling endpoints can be abused to cause DB load; cancel could be abused to disrupt customer payments.

Fix locally by applying RateLimiter in `status()` and `cancel()` (or middleware) keyed to user_id + session id.