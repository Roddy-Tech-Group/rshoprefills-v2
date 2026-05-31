<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Enums\Currency;
use App\Domain\Shared\Enums\TransactionCategory;
use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class AdminRewardAnalyticsController extends Controller
{
    public function metrics()
    {
        $totalMinted = WalletTransaction::where('currency', Currency::RCOIN->value)
            ->whereIn('transaction_category', [
                TransactionCategory::RewardCashback->value,
                TransactionCategory::RewardReferral->value,
            ])
            ->sum('amount');

        $totalRedeemed = WalletTransaction::where('currency', Currency::RCOIN->value)
            ->where('transaction_category', TransactionCategory::RewardRedemption->value)
            ->sum('amount');

        $totalReversed = WalletTransaction::where('currency', Currency::RCOIN->value)
            ->where('transaction_category', TransactionCategory::RewardReversal->value)
            ->sum('amount');

        $topReferrers = Referral::select('referrer_id', DB::raw('COUNT(*) as total_referrals'), DB::raw('SUM(total_rewards_generated) as total_earned'))
            ->groupBy('referrer_id')
            ->orderByDesc('total_earned')
            ->with('referrer:id,first_name,last_name,email')
            ->limit(10)
            ->get();

        return response()->json([
            'metrics' => [
                'total_minted' => (int) $totalMinted,
                'total_redeemed' => (int) $totalRedeemed,
                'total_reversed' => (int) $totalReversed,
                'circulating_supply' => (int) ($totalMinted - $totalRedeemed - $totalReversed),
            ],
            'top_referrers' => $topReferrers,
        ]);
    }
}
