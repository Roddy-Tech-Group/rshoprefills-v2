<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminRewardAnalyticsController extends Controller
{
    public function metrics()
    {
        $totalMinted = \App\Models\WalletTransaction::where('currency', \App\Domain\Shared\Enums\Currency::RCOIN->value)
            ->whereIn('transaction_category', [
                \App\Domain\Shared\Enums\TransactionCategory::RewardCashback->value,
                \App\Domain\Shared\Enums\TransactionCategory::RewardReferral->value
            ])
            ->sum('amount');

        $totalRedeemed = \App\Models\WalletTransaction::where('currency', \App\Domain\Shared\Enums\Currency::RCOIN->value)
            ->where('transaction_category', \App\Domain\Shared\Enums\TransactionCategory::RewardRedemption->value)
            ->sum('amount');
            
        $totalReversed = \App\Models\WalletTransaction::where('currency', \App\Domain\Shared\Enums\Currency::RCOIN->value)
            ->where('transaction_category', \App\Domain\Shared\Enums\TransactionCategory::RewardReversal->value)
            ->sum('amount');

        $topReferrers = \App\Models\Referral::select('referrer_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_referrals'), \Illuminate\Support\Facades\DB::raw('SUM(total_rewards_generated) as total_earned'))
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
