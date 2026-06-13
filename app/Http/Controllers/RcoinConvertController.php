<?php

namespace App\Http\Controllers;

use App\Domain\Rewards\Services\RewardEngine;
use App\Domain\Shared\Enums\Currency;
use App\Domain\Shared\Enums\TransactionCategory;
use App\Domain\Wallet\Exceptions\InsufficientBalanceException;
use App\Domain\Wallet\Services\WalletService;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Instant Rcoin → wallet (USD) conversion. Distinct from withdrawal -
 * conversion stays inside the platform, no admin approval needed, customer
 * can immediately spend the credit on any product. Sister to RcoinWithdrawalController
 * which routes money OUT to a bank / mobile money.
 *
 * Math:
 *   USD value = rcoin × rcoin_usd_rate
 *   Must be >= wallet_conversion_min_usd
 * Operation:
 *   1. Debit user's Rcoin wallet (category: RewardRedemption)
 *   2. Credit user's USD wallet with the converted amount (category: Adjustment)
 * Both inside a single DB transaction so a credit failure rolls the debit back.
 */
class RcoinConvertController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function toWallet(Request $request, RewardEngine $rewardEngine): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user, 403);

        if (! Setting::get('wallet_conversion_enabled', true)) {
            return back()->withErrors(['convert_amount' => 'Rcoin conversion is currently disabled.']);
        }

        $minUsd = (float) Setting::get('wallet_conversion_min_usd', 2.00);
        $rate = (float) Setting::rcoinUsdRate();
        $minRcoin = $rate > 0 ? (int) ceil($minUsd / $rate) : 0;

        $validated = $request->validate([
            'convert_amount' => ['required', 'integer', 'min:'.max(1, $minRcoin)],
        ]);

        $rcoinAmount = (int) $validated['convert_amount'];
        $usdValue = round($rcoinAmount * $rate, 4);

        if ($usdValue < $minUsd) {
            return back()->withErrors([
                'convert_amount' => "Minimum conversion is USD {$minUsd} (you requested USD ".number_format($usdValue, 2).').',
            ]);
        }

        $rcoinWallet = $this->walletService->getOrCreateWallet($user, Currency::RCOIN);
        if ((int) $rcoinWallet->balance < $rcoinAmount) {
            return back()->withErrors(['convert_amount' => 'Insufficient Rcoin balance.']);
        }

        try {
            DB::transaction(function () use ($user, $rcoinWallet, $rcoinAmount, $usdValue, $rate) {
                // Debit Rcoin - RewardRedemption category so it shows up
                // distinctly in the customer's transaction history and in
                // the admin analytics dashboard's "redeemed" KPI.
                $debit = $this->walletService->debit(
                    wallet: $rcoinWallet,
                    amount: $rcoinAmount,
                    category: TransactionCategory::RewardRedemption,
                    description: 'Converted to USD wallet balance',
                    metadata: [
                        'usd_credited' => $usdValue,
                        'rate_snapshot' => $rate,
                    ],
                );

                // Credit USD wallet. Adjustment category since this is an
                // internal balance shuffle, not a customer-funded top-up.
                $usdWallet = $this->walletService->getOrCreateWallet($user, Currency::USD);
                $this->walletService->credit(
                    wallet: $usdWallet,
                    amount: $usdValue,
                    category: TransactionCategory::Adjustment,
                    description: 'Converted from Rcoin balance',
                    metadata: [
                        'rcoin_debited' => $rcoinAmount,
                        'rate_snapshot' => $rate,
                        'source_transaction_id' => $debit->id ?? null,
                    ],
                );
            });
        } catch (InsufficientBalanceException $e) {
            return back()->withErrors(['convert_amount' => 'Insufficient Rcoin balance.']);
        } catch (\Throwable $e) {
            Log::error('Rcoin → wallet conversion failed', [
                'user_id' => $user->id,
                'rcoin' => $rcoinAmount,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['convert_amount' => 'Could not complete the conversion. Please try again.']);
        }

        return back()->with('status', "Converted {$rcoinAmount} Rcoin to USD ".number_format($usdValue, 2).' in your wallet.');
    }
}
