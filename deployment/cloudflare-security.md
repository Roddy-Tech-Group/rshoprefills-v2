# Cloudflare Security & WAF Configuration for RshopRefills V2

This document outlines the required Cloudflare configuration, Content Security Policy (CSP), and firewall rules necessary for the Turnstile integration and general production security.

## 1. Turnstile Setup

### Widget Configuration
- **Domain:** Ensure your production domain is whitelisted in the Cloudflare Turnstile dashboard.
- **Widget Type:** Managed or Invisible mode is recommended.
- **Clearance Level:** Set to challenge suspicious traffic only, minimizing friction for legitimate buyers.

### Environment Variables
Ensure the following variables are correctly set in the production `.env` file:
```env
TURNSTILE_ENABLED=true
TURNSTILE_SITE_KEY=your_site_key
TURNSTILE_SECRET_KEY=your_secret_key

# Granular enforcement toggles
TURNSTILE_ENFORCE_AUTH=true
TURNSTILE_ENFORCE_CHECKOUT=true

# Set to false to enforce strict failure on timeouts
TURNSTILE_FAIL_OPEN=true
```

## 2. Content Security Policy (CSP)

To ensure Turnstile scripts and frames can load correctly, update the CSP headers in your web server (Nginx/Apache) or application middleware to include the following directives:

```http
Content-Security-Policy: 
  script-src 'self' https://challenges.cloudflare.com; 
  frame-src 'self' https://challenges.cloudflare.com;
```

*Note: If you use other third-party services (e.g., payment gateways, analytics), you must append them to these directives.*

## 3. Firewall (WAF) Exclusions

Turnstile protection and rate limiters are applied selectively to prevent locking out automated webhooks and internal integrations. **Never** apply Turnstile or aggressive rate limiting globally.

Ensure that the following critical paths are **EXCLUDED** from aggressive WAF rules, CAPTCHA challenges, and Turnstile middleware:

1. **Payment Webhooks:** 
   - `api/webhooks/flutterwave`
   - `api/webhooks/nowpayments` (or other crypto gateways)
2. **Provider Callbacks:**
   - `api/callbacks/airalo`
   - `api/callbacks/zendit`
3. **Internal & Background Jobs:**
   - `/horizon/*`
   - Scheduler endpoints
   - Admin polling endpoints

## 4. Graceful Degradation Behavior

The application is built to degrade gracefully when Cloudflare Turnstile services are unreachable or timing out.

- **Auth Flows (Login/Register/Password Reset):** Fail CLOSED. If the service is unreachable, authentication attempts will be temporarily blocked.
- **Checkout Flow:** Fail OPEN (conditionally). If Turnstile times out, the `CheckoutController` will allow the transaction to proceed **ONLY IF** the internal `FraudDetectionService` determines the risk score and velocity are within normal bounds.

## 5. Abuse Cooldown

Repeated failed Turnstile submissions are tracked per IP address by the `FraudDetectionService`.
If an IP address exceeds the allowed threshold (e.g., 5 failures), a temporary cooldown (30 minutes) is applied automatically to prevent brute-forcing and spam submissions. This is completely integrated into the checkout and auth validations.
