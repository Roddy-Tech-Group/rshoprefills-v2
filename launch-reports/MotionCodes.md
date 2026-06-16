# Launch Report

## TL;DR
Launch security check (Payments + Rate limits) completed on the supplied files. Overall: webhook signature validation is implemented and there is some throttling for auth (web + admin), plus queued verification for gateway callbacks. However, the payment-session endpoints lack rate limiting and show/verify/cancel are vulnerable to abuse patterns (IDOR and repeated verification attempts). Also, payment providers contain committed mock fallbacks that look like real “secret” defaults and will be a

## Verdict
Launch ready: No
Security level: high

## Detailed Report
Scope: Code paths in controllers/services related to payment session initiation/polling/verify/cancel and webhook handling, plus rate limiting logic in the included auth/login/admin/auth components.

Positive security controls found:
- Webhook signature verification exists for both Flutterwave and NowPayments and rejects missing/invalid signatures (fail-closed).
- Payment confirmation is state-controlled via PaymentSessionService::confirmSession using DB transaction + row locking, and the session state machine prevents illegal transitions.
- Wallet payments require a transaction PIN authorization token that is validated and then consumed.
- Some throttling exists for admin login and user auth flows (Livewire login/register/forgot/reset screens, and admin auth service).

Key security gaps for Payments / Rate limits:
1) No app-level or route-level rate limiting on payment session endpoints (polling, verify, cancel, pay). These endpoints can be hammered by an attacker to cause excessive gateway verification calls and/or repeated state transitions attempts.
2) PaymentSessionController::show/status/verify/cancel/pay do not appear to be scoped to the authenticated user or to validate ownership of the PaymentSession by the caller. With only findOrFail($id), this is potentially IDOR if routes don’t add authorization elsewhere.
3) verify() accepts an optional transaction_id from the client and writes it into the PaymentAttempt gateway_reference before calling provider->verifyPayment(). This creates integrity concerns unless the underlying gateway verification ignores/overrides it.
4) Webhook handlers persist full webhook_payload to PaymentAttempt (good for audit), but there is no explicit idempotency/duplicate processing guard visible in the provided code beyond PaymentAttempt::

## AI Coding Agent Notes
PaymentSessionController exposes sensitive write/verification operations without visible authentication scoping or throttling:
- app/Http/Controllers/Api/PaymentSessionController.php: show(), status(), cancel(), verify(), pay()
- No per-session ownership checks (e.g., session belongs to request()->user()).
- No RateLimiter/Throttle middleware calls in controller methods.

Concrete fixes should focus on:
- Enforce authorization/ownership for PaymentSession and PaymentAttempt in this controller.
- Add rate limiting for: polling/status, verify, pay, cancel, and possibly show.

Evidence of missing protections in the supplied file:
- Controller uses PaymentSession::findOrFail($id) and PaymentSession::select(...)->findOrFail($id) with no user scoping.
- verify() writes request input transaction_id into gateway_reference.
- pay() triggers gateway charges/authorizations immediately.

Also, payment provider “mock secret” fallbacks are present and can lead to accidental acceptance in non-mock environments unless config is guaranteed. This is a launch-blocker item for payments hardening (high confidence).

## Fixable Findings
- ERROR: PaymentSession endpoints likely allow IDOR (no user ownership scoping)
  - Location: app/Http/Controllers/Api/PaymentSessionController.php:1-230
  - PaymentSessionController resolves PaymentSession by ID only (findOrFail) and returns/operates on it without visible scoping to the authenticated user. If routes are not already protected with per-user authorization elsewhere, an attacker could access or affect other users' payment sessions by guessing UUIDs.
- ERROR: No rate limiting on payment session verification/polling/payment/cancel
  - Location: app/Http/Controllers/Api/PaymentSessionController.php:1-230
  - There is no RateLimiter usage or throttle middleware shown in PaymentSessionController methods. Payment verification and pay flows can be hammered (especially status polling and verify), increasing gateway/API load and enabling abuse patterns.
- ERROR: verify() trusts client-provided transaction_id and writes it to gateway_reference before verification
  - Location: app/Http/Controllers/Api/PaymentSessionController.php:1-230
  - In verify(), if request includes transaction_id, the controller sets $attempt->gateway_reference to that client value, then calls provider->verifyPayment($attempt). If verifyPayment relies on gateway_reference/tx_ref, a malicious client may influence verification input integrity.
- ERROR: FlutterwavePaymentProvider uses insecure fallback mock secrets that may be used in real environments
  - Location: app/Domain/Payment/Providers/FlutterwavePaymentProvider.php:1-240
  - FlutterwavePaymentProvider sets $this->secretKey = config('services.flutterwave.secret_key') ?: 'FLW_SECRET_KEY_MOCK'; and similar public_key fallback elsewhere. If misconfigured in staging/prod (empty env var), real payment traffic could be processed using mock keys instead of failing fast.