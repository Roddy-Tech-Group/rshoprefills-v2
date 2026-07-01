<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Domain\Payment\Services\CryptoFeeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CryptoFeeController extends Controller
{
    public function __construct(
        private readonly CryptoFeeService $cryptoFeeService,
    ) {}

    /**
     * Get a pre-checkout fee estimate for a crypto payment.
     * Used by the frontend to show the customer what they will pay before
     * they click "Continue to payment" and generate a real invoice.
     */
    public function estimate(Request $request)
    {
        $request->validate([
            'amount_usd' => ['required', 'numeric', 'min:0.01'],
            'pay_currency' => ['required', 'string'],
        ]);

        $amountUsd = (float) $request->input('amount_usd');
        $payCurrency = $request->input('pay_currency');

        $breakdown = $this->cryptoFeeService->estimateBreakdown($amountUsd, $payCurrency);

        return response()->json($breakdown->toArray());
    }
}
