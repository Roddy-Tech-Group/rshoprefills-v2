<?php

namespace App\Domain\Shared\Enums;

/**
 * Categories for classifying wallet transactions.
 */
enum TransactionCategory: string
{
    case Funding = 'funding';
    case Purchase = 'purchase';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
    case Withdrawal = 'withdrawal';
    case Reversal = 'reversal';
    case Transfer = 'transfer';
    
    // Reward Engine specific categories
    case RewardCashback = 'reward_cashback';
    case RewardReferral = 'reward_referral';
    case RewardRedemption = 'reward_redemption';
    case RewardWithdrawal = 'reward_withdrawal';
    case RewardReversal = 'reward_reversal';

    public function label(): string
    {
        return match ($this) {
            self::Funding => 'Funding',
            self::Purchase => 'Purchase',
            self::Refund => 'Refund',
            self::Adjustment => 'Adjustment',
            self::Withdrawal => 'Withdrawal',
            self::Reversal => 'Reversal',
            self::Transfer => 'Transfer',
            self::RewardCashback => 'Cashback Reward',
            self::RewardReferral => 'Referral Reward',
            self::RewardRedemption => 'Reward Redemption',
            self::RewardWithdrawal => 'Reward Withdrawal',
            self::RewardReversal => 'Reward Reversal',
        };
    }
}
