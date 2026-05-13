<?php

namespace App\Http\Controllers\Api;

use App\Domain\Shared\Enums\Currency;
use App\Domain\Wallet\Resources\WalletResource;
use App\Domain\Wallet\Services\WalletFundingService;
use App\Domain\Wallet\Services\WalletService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class UserWalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly WalletFundingService $fundingService,
    ) {}

    public function index(Request $request)
    {
        $wallets = $request->user()->wallets; // Assumes relation exists

        return WalletResource::collection($wallets);
    }

    public function show(Request $request, string $currency)
    {
        $currencyEnum = Currency::tryFrom(strtoupper($currency));
        if (! $currencyEnum) {
            return response()->json(['message' => 'Invalid currency.'], 400);
        }

        $wallet = $this->walletService->getOrCreateWallet($request->user(), $currencyEnum);

        return new WalletResource($wallet);
    }

    public function initiateFunding(Request $request)
    {
        $validated = $request->validate([
            'currency' => ['required', new Enum(Currency::class)],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $currency = Currency::from($validated['currency']);
        $wallet = $this->walletService->getOrCreateWallet($request->user(), $currency);

        try {
            $result = $this->fundingService->initializeFunding(
                user: $request->user(),
                wallet: $wallet,
                amount: (float) $validated['amount'],
                currency: $currency
            );

            return response()->json([
                'message' => 'Funding initialized successfully.',
                'payment_link' => $result['payment_link'],
                'reference' => $result['funding']->reference,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
