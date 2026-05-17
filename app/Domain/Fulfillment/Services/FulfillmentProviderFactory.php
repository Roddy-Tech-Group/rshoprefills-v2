<?php

namespace App\Domain\Fulfillment\Services;

use App\Domain\Fulfillment\Interfaces\FulfillmentProviderInterface;
use App\Domain\Fulfillment\Providers\ZenditFulfillmentProvider;

class FulfillmentProviderFactory
{
    public function __construct(
        private readonly ZenditFulfillmentProvider $zenditProvider
    ) {}

    public function getProvider(string $providerName): FulfillmentProviderInterface
    {
        return match (strtolower($providerName)) {
            'zendit' => $this->zenditProvider,
            default => throw new \InvalidArgumentException("Unsupported fulfillment provider: {$providerName}"),
        };
    }
}
