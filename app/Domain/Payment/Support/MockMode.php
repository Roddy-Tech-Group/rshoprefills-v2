<?php

namespace App\Domain\Payment\Support;

/**
 * Single source of truth for whether the payment stack is allowed to fall back
 * to mock gateway credentials. Mock mode is permitted ONLY in local/testing, or
 * when explicitly opted in via PAYMENT_MOCK=true. In every other environment a
 * missing real credential must hard-fail rather than silently process payments
 * against a fake gateway.
 */
final class MockMode
{
    public static function allowed(): bool
    {
        return app()->environment(['local', 'testing'])
            || (bool) config('services.payment_mock', false);
    }
}
