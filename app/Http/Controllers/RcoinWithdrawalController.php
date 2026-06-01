<?php

namespace App\Http\Controllers;

use App\Domain\Shared\Enums\Currency;
use App\Domain\Shared\Enums\TransactionCategory;
use App\Domain\Wallet\Exceptions\InsufficientBalanceException;
use App\Domain\Wallet\Services\WalletService;
use App\Models\RcoinWithdrawal;
use App\Models\Setting;
use App\Support\FeatureFlag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Customer-facing Rcoin withdrawal flow. The form lives on
 * /dashboard/rewards. Submitting POSTs here, which:
 *   1. Validates against the live withdrawal_* settings (enabled, min, fee, rate)
 *   2. Locks the Rcoin wallet, debits the requested amount via WalletService
 *      so balance integrity holds (race-free even under concurrent submits)
 *   3. Persists the RcoinWithdrawal row in `pending` status linked to the
 *      debit transaction (admin can later approve→paid OR reject→credit-back)
 *
 * Admin approval/rejection happens at /admin/content/rewards-withdrawals.
 */
class RcoinWithdrawalController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function store(Request $request): RedirectResponse
    {
        // features.wallet_withdraw_enabled kill-switch.
        if (! FeatureFlag::on('wallet_withdraw')) {
            return back()->withErrors(['amount' => 'Rcoin withdrawals are temporarily disabled.']);
        }

        $user = $request->user();
        abort_if(! $user, 403);

        // Master switch - disabled in settings = page form shouldn't show
        // the submit button, but enforce it server-side too.
        if (! Setting::get('withdrawal_enabled', false)) {
            return back()->withErrors(['withdraw_amount' => 'Rcoin withdrawals are currently disabled.']);
        }

        // Optional compliance gate - finance/legal can require KYC before
        // letting funds leave the platform. Soft by default; flip the setting
        // ON when regulatory exposure justifies the extra friction.
        if (Setting::get('require_kyc_for_withdrawal', false) && strtolower((string) $user->kyc_status) !== 'verified') {
            return back()->withErrors(['withdraw_amount' => 'Please complete identity verification (KYC) before requesting a withdrawal.']);
        }

        $minRcoin = (int) Setting::get('withdrawal_min_rcoin', 2000);
        $minUsd = (float) Setting::get('withdrawal_minimum_usd', 10.00);
        $feePct = (float) Setting::get('withdrawal_fee_percentage', 0);
        $rate = (float) Setting::get('withdrawal_conversion_rate', 0.005);

        $validated = $request->validate([
            'withdraw_amount' => ['required', 'integer', 'min:'.$minRcoin],
            'withdraw_method' => ['required', 'in:wallet,bank,mobile_money'],
            'account_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $rcoinAmount = (int) $validated['withdraw_amount'];
        $usdValue = round($rcoinAmount * $rate, 4);
        $feeUsd = round($usdValue * ($feePct / 100), 4);
        $payoutUsd = round($usdValue - $feeUsd, 4);

        if ($usdValue < $minUsd) {
            return back()->withErrors([
                'withdraw_amount' => "Minimum withdrawal is USD {$minUsd} (you requested USD ".number_format($usdValue, 2).').',
            ]);
        }

        $wallet = $this->walletService->getOrCreateWallet($user, Currency::RCOIN);
        if ((int) $wallet->balance < $rcoinAmount) {
            return back()->withErrors(['withdraw_amount' => 'Insufficient Rcoin balance.']);
        }

        try {
            $withdrawal = DB::transaction(function () use (
                $user, $rcoinAmount, $usdValue, $feeUsd, $payoutUsd, $rate, $validated, $wallet
            ) {
                $debitTxn = $this->walletService->debit(
                    wallet: $wallet,
                    amount: $rcoinAmount,
                    category: TransactionCategory::RewardWithdrawal,
                    description: 'Withdrawal request (pending review)',
                    metadata: [
                        'method' => $validated['withdraw_method'],
                        'usd_value' => $usdValue,
                        'fee_usd' => $feeUsd,
                        'rate' => $rate,
                    ],
                );

                $payoutDetails = match ($validated['withdraw_method']) {
                    'bank' => [
                        'account_name' => $validated['account_name'] ?? null,
                        'account_number' => $validated['account_number'] ?? null,
                    ],
                    'mobile_money' => [
                        'phone' => $validated['phone'] ?? null,
                    ],
                    default => [], // wallet payout - no details needed
                };

                return RcoinWithdrawal::create([
                    'user_id' => $user->id,
                    'rcoin_amount' => $rcoinAmount,
                    'usd_value' => $usdValue,
                    'fee_usd' => $feeUsd,
                    'payout_usd' => $payoutUsd,
                    'method' => $validated['withdraw_method'],
                    'payout_details' => $payoutDetails,
                    'rate_snapshot' => $rate,
                    'status' => 'pending',
                    'debit_transaction_id' => $debitTxn->id ?? null,
                ]);
            });
        } catch (InsufficientBalanceException $e) {
            return back()->withErrors(['withdraw_amount' => 'Insufficient Rcoin balance.']);
        } catch (\Throwable $e) {
            Log::error('Rcoin withdrawal request failed', [
                'user_id' => $user->id,
                'amount' => $rcoinAmount,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['withdraw_amount' => 'Could not submit your withdrawal. Please try again.']);
        }

        return back()->with('status', "Withdrawal of {$withdrawal->rcoin_amount} Rcoin submitted. We'll review it within 24 hours.");
    }
}
