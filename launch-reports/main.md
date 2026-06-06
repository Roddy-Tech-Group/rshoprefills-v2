# Launch Report

## TL;DR
Payments + rate limiting check found no hardcoded credentials/secrets. Webhook signature verification for Flutterwave and NowPayments is implemented and fails closed. However, the payment/session endpoints lack per-session/per-attempt throttling and have inconsistent payload/exception handling that could enable repeated confirm/verify/poll abuse and confusing client behavior under failure.

## Verdict
Launch ready: No
Security level: medium

## Detailed Report
Scope: Payments and rate limits across provided controllers/services and related payment orchestration.

What looks good (security baseline):
- Flutterwave webhook: verifies `verif-hash` against configured `services.flutterwave.webhook_hash` and rejects on mismatch (fails closed).
- NowPayments webhook: verifies `x-nowpayments-sig` using configured `services.nowpayments.ipn_secret` with `hash_equals` and rejects when the secret is missing or signature mismatches.
- Payment session state machine exists (PaymentSession::transitionTo) and blocks invalid transitions.
- Some wallet auth token consumption exists in WalletPaymentProvider (authorization tokens are consumed).
- Admin login has rate limiting (AdminAuthService uses RateLimiter).

Rate limiting gaps (payments-specific):
- There is no visible rate limiting/throttling applied to high-risk API endpoints for payments: showing/polling payment sessions (`status`), initiating payment verification (`verify`), and paying/authorizing (`pay`).
- There is also no visible per-session/per-attempt throttling for repeated verify/pay calls that could cause excess gateway traffic, job dispatch amplification, or brute-force style probing of gateway references.

Concrete bug/abuse risks (specific to provided code):
- PaymentSessionController::verify allows a client to set/overwrite `gateway_reference` on the PaymentAttempt when `transaction_id` is present and the current gateway_reference is not numeric; this is a trust boundary that should be constrained further (e.g., only allow setting from expected/opaque provider values, and never let verify inputs mutate references used for reconciliation).
- verify/pay/cancel endpoints catch exceptions in a way that can leak raw exception messages to clients (`verify` returns `Verification...:`

## AI Coding Agent Notes
Files reviewed: PaymentSessionController, CheckoutApiController, NowPaymentsWebhookController, FlutterwaveWebhookController, payment providers (FlutterwavePaymentProvider, NowPaymentsProvider, WalletPaymentProvider), PaymentSessionService, VerifyPaymentJob/RefundPaymentJob, plus auth/rate-limiting patterns for comparison.

Key security posture decisions already present:
- Webhooks fail closed with cryptographic signature verification.
- There is a state transition matrix for PaymentSession.

What’s missing for a secure payments launch:
- App-wide and payments-specific rate limiting on poll/verify/pay endpoints.
- Stronger invariants around what a client is allowed to mutate during verify.

Immediate recommendation:
- Block repeated calls to `/api/payment-sessions/{id}/status|verify|pay|cancel` per user+session (and optionally per IP), and ensure verify/pay are idempotent and do not dispatch multiple fulfillment/confirm flows under repeated client requests.

## Fixable Findings
- WARNING: No rate limiting on high-risk payment session endpoints (poll/verify/pay/cancel)
  - Location: app/Http/Controllers/Api/PaymentSessionController.php:1-250
  - The API endpoints that drive payment state changes and gateway checks—`PaymentSessionController::status`, `::verify`, `::pay`, and `::cancel`—do not apply any visible throttling/RateLimiter middleware or checks. Under attack, a client can repeatedly poll/trigger verify and cause repeated gateway calls and job dispatch amplification. Add per-route (user+payment_session_id) rate limits (Laravel throttle middleware) and/or internal idempotency checks at the controller/service boundary.
- WARNING: verify() mutates PaymentAttempt gateway_reference based on client-supplied transaction_id
  - Location: app/Http/Controllers/Api/PaymentSessionController.php:1-250
  - `verify()` validates `transaction_id` and, when conditions match, assigns it into `$attempt->gateway_reference` and saves. This creates a trust boundary where a client can potentially influence reconciliation identifiers used downstream. Tighten allowed behavior: only accept transaction_id values when they match the expected gateway reference shape for the selected provider and/or only when the attempt is in a state where provider_reference is truly unset/placeholder; otherwise ignore and do not save.