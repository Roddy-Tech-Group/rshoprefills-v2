<?php

namespace App\Domain\Order\Exceptions;

use Exception;

/**
 * Thrown during checkout when a customer-supplied coupon code cannot be
 * applied. The message is safe to surface verbatim to the customer.
 */
class InvalidCouponException extends Exception
{
    public static function unknown(string $code): self
    {
        return new self('That coupon code is not valid.');
    }

    public static function notRedeemable(string $code): self
    {
        return new self('This coupon is no longer available.');
    }

    public static function notInCart(string $code): self
    {
        return new self('This coupon does not apply to any item in your cart.');
    }
}
