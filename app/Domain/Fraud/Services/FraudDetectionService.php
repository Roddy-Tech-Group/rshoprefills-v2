<?php

namespace App\Domain\Fraud\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class FraudDetectionService
{
    /**
     * Inspect a checkout attempt for signs of fraud or abuse.
     *
     * @param User $user
     * @param float $amount
     * @param string $ip
     * @return bool Returns true if the transaction is flagged as suspicious.
     */
    public function isSuspiciousCheckout(User $user, float $amount, string $ip): bool
    {
        $flagged = false;
        $reason = '';

        // 1. Velocity check: Too many purchases in a short window
        $purchaseCountKey = "fraud_velocity_purchases_{$user->id}";
        $recentPurchases = Cache::tags(['fraud'])->get($purchaseCountKey, 0);
        
        if ($recentPurchases > 10) {
            $flagged = true;
            $reason = "User exceeded 10 purchases in an hour.";
        }

        // 2. High Value Transaction for new accounts
        if (!$flagged && $amount > 500 && $user->created_at->diffInDays(now()) < 7) {
            $flagged = true;
            $reason = "High value transaction (>500) for a new account (<7 days).";
        }

        // 3. IP address velocity (prevent carding attacks)
        if (!$flagged) {
            $ipVelocityKey = "fraud_velocity_ip_{$ip}";
            $recentIpCheckouts = Cache::tags(['fraud'])->get($ipVelocityKey, 0);
            
            if ($recentIpCheckouts > 15) {
                $flagged = true;
                $reason = "More than 15 checkouts from the same IP {$ip} in an hour.";
            }
        }

        if ($flagged) {
            $adminEmail = env('ADMIN_ALERT_EMAIL', 'admin@roddytechgroup.com');
            \Illuminate\Support\Facades\Notification::route('mail', $adminEmail)
                ->notify(new \App\Notifications\CriticalSystemAlert(
                    title: 'Fraud Alert: Suspicious Checkout Blocked',
                    description: "A checkout attempt was blocked by the Fraud Detection Engine.",
                    context: [
                        'User ID' => $user->id,
                        'Email' => $user->email,
                        'Amount' => $amount,
                        'IP Address' => $ip,
                        'Reason' => $reason,
                    ]
                ));
            return true;
        }

        return false;
    }

    /**
     * Record a successful checkout attempt to update velocity metrics.
     *
     * @param User $user
     * @param string $ip
     */
    public function recordCheckout(User $user, string $ip): void
    {
        $purchaseCountKey = "fraud_velocity_purchases_{$user->id}";
        $currentPurchases = Cache::tags(['fraud'])->get($purchaseCountKey, 0);
        Cache::tags(['fraud'])->put($purchaseCountKey, $currentPurchases + 1, now()->addHour());

        $ipVelocityKey = "fraud_velocity_ip_{$ip}";
        $currentIpCheckouts = Cache::tags(['fraud'])->get($ipVelocityKey, 0);
        Cache::tags(['fraud'])->put($ipVelocityKey, $currentIpCheckouts + 1, now()->addHour());
    }
}
