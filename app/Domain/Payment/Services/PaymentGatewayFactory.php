<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Interfaces\PaymentProviderInterface;
use App\Domain\Payment\Providers\WalletPaymentProvider;
use App\Domain\Payment\Providers\FlutterwavePaymentProvider;
use App\Domain\Payment\Providers\NowPaymentsProvider;
use App\Domain\Shared\Enums\PaymentGateway;

class PaymentGatewayFactory
{
    public function __construct(
        private readonly WalletPaymentProvider $walletProvider,
        private readonly FlutterwavePaymentProvider $flutterwaveProvider,
        private readonly NowPaymentsProvider $nowPaymentsProvider
    ) {}

    public function getProvider(string|PaymentGateway $gateway): PaymentProviderInterface
    {
        $value = $gateway instanceof PaymentGateway ? $gateway->value : $gateway;

        return match ($value) {
            'wallet', PaymentGateway::Wallet->value => $this->walletProvider,
            'flutterwave', PaymentGateway::Flutterwave->value => $this->flutterwaveProvider,
            'crypto', 'nowpayments', PaymentGateway::NowPayments->value => $this->nowPaymentsProvider,
            default => throw new \InvalidArgumentException("Unsupported payment gateway: {$value}"),
        };
    }
}
