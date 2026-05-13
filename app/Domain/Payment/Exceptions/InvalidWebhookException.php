<?php

namespace App\Domain\Payment\Exceptions;

use Exception;

class InvalidWebhookException extends Exception
{
    public static function signatureMismatch(): self
    {
        return new self('Webhook signature verification failed.');
    }

    public static function missingReference(): self
    {
        return new self('Webhook payload is missing transaction reference.');
    }
}
