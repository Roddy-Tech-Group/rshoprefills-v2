<?php

namespace App\Domain\Admin\Queries;

use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Centralized query class for admin dashboard metrics.
 *
 * Keeps complex aggregations and cross-table unions out of the models
 * and controllers. This ensures queries are optimized and easy to scale.
 */
class DashboardMetricsQuery
{
    /**
     * Get aggregated overview metrics.
     *
     * Accepts an optional preset ('today' | '7d' | '30d' | '90d' | 'year' | 'custom')
     * and optional custom-range bounds. Default null → all-time (preserves the
     * original behaviour so existing callers don't break). Wallet balance total
     * is always all-time — it is a snapshot, not a transactional figure.
     */
    public function getOverviewMetrics(?string $preset = null, ?string $start = null, ?string $end = null): array
    {
        [$startDate, $endDate] = $this->resolveRange($preset, $start, $end);

        $usersQuery = User::query();
        $ordersQuery = Order::query();
        $paymentQuery = PaymentAttempt::query();
        $walletTxQuery = WalletTransaction::query();

        if ($startDate) {
            $usersQuery->where('created_at', '>=', $startDate);
            $ordersQuery->where('created_at', '>=', $startDate);
            $paymentQuery->where('created_at', '>=', $startDate);
            $walletTxQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $usersQuery->where('created_at', '<=', $endDate);
            $ordersQuery->where('created_at', '<=', $endDate);
            $paymentQuery->where('created_at', '<=', $endDate);
            $walletTxQuery->where('created_at', '<=', $endDate);
        }

        $totalUsers = $usersQuery->count();
        $totalOrders = $ordersQuery->count();

        // Revenue is recognised only when an order is completed + fulfilled
        // (order_status = Completed). A paid-but-still-processing order is
        // money in the till but not yet earned — excluded here so the KPI
        // matches the Revenue Overview chart's "completed and fulfilled" rule.
        $totalRevenue = (clone $ordersQuery)
            ->where('order_status', OrderStatus::Completed->value)
            ->sum('total_amount');

        $paymentCount = (clone $paymentQuery)->count();
        $walletTxCount = $walletTxQuery->count();
        $transactionsCount = $paymentCount + $walletTxCount;

        $successfulPayments = (clone $paymentQuery)
            ->where('payment_status', PaymentStatus::Paid)
            ->count();
        $successRate = $paymentCount > 0 ? round(($successfulPayments / $paymentCount) * 100, 2) : 0.0;

        // Wallet balance total stays all-time — current value, not a transaction.
        $walletBalanceTotal = Wallet::sum('balance');

        return [
            'total_users' => $totalUsers,
            'total_orders' => $totalOrders,
            'total_revenue' => (float) $totalRevenue,
            'transactions_count' => $transactionsCount,
            'success_rate' => $successRate,
            'wallet_balance_total' => (float) $walletBalanceTotal,
        ];
    }

    /**
     * Resolve a preset string + optional custom bounds into [start, end] Carbon
     * instances or [null, null] for all-time.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function resolveRange(?string $preset, ?string $start, ?string $end): array
    {
        if ($preset === 'custom' && $start && $end) {
            try {
                return [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()];
            } catch (\Throwable $e) {
                return [null, null];
            }
        }

        return match ($preset) {
            'today' => [now()->startOfDay(), now()],
            '7d' => [now()->subDays(7), now()],
            '30d' => [now()->subDays(30), now()],
            '90d' => [now()->subDays(90), now()],
            'year' => [now()->startOfYear(), now()],
            default => [null, null],
        };
    }

    /**
     * Monthly new-user counts for the last N months (inclusive of the current
     * month). Returns an ordered ['Jan' => 0, 'Feb' => 3, ...] array — keys are
     * the short month label so the dashboard chart can read them directly.
     */
    public function getNewUsersTimeseries(int $months = 6): array
    {
        $months = max(1, min(24, $months));

        $signups = User::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->where('created_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $result[$m->format('M')] = (int) ($signups[$m->format('Y-m')] ?? 0);
        }

        return $result;
    }

    /**
     * Get revenue chart data aggregated by date and product category.
     *
     * Accepts either a named preset ('7d' | '30d' | '6m' | '1y') or a raw int
     * day count (e.g. 15). Day counts always group daily; '6m' and '1y' group
     * monthly. The int path lets the dashboard's 1-30 day picker drive the
     * chart at full granularity without each day mapping to a coarser bucket.
     */
    public function getRevenueChartData(string|int $range = '7d'): array
    {
        if (is_int($range) || ctype_digit((string) $range)) {
            $days = max(1, (int) $range);
            $startDate = now()->subDays($days);
            $dateFormat = '%Y-%m-%d';
        } else {
            $startDate = match ($range) {
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
                '6m' => now()->subMonths(6),
                '1y' => now()->subYear(),
                default => now()->subDays(7),
            };

            // Use monthly grouping for long ranges, daily for short ranges
            $dateFormat = in_array($range, ['6m', '1y']) ? '%Y-%m' : '%Y-%m-%d';
        }

        // Aggregate completed + fulfilled orders by product type. Revenue is
        // recognised only when fulfillment finishes (order_status = Completed),
        // so a paid-but-still-processing order does NOT appear here yet.
        $results = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('categories', 'order_items.category_id', '=', 'categories.id')
            ->where('orders.order_status', OrderStatus::Completed->value)
            ->where('orders.completed_at', '>=', $startDate)
            ->select(
                DB::raw("DATE_FORMAT(orders.completed_at, '{$dateFormat}') as date"),
                'categories.slug as category_slug',
                DB::raw('SUM(order_items.subtotal_amount) as total')
            )
            ->groupBy('date', 'categories.slug')
            ->orderBy('date')
            ->get();

        $chartData = [];

        foreach ($results as $row) {
            $date = $row->date;

            if (! isset($chartData[$date])) {
                $chartData[$date] = [
                    'date' => $date,
                    'gift_cards' => 0,
                    'esim' => 0,
                    'topup' => 0,
                    'other' => 0,
                ];
            }

            // Pivot product types into fixed categories
            $slug = strtolower($row->category_slug);
            $key = match (true) {
                str_contains($slug, 'gift') => 'gift_cards',
                str_contains($slug, 'esim') => 'esim',
                str_contains($slug, 'topup') || str_contains($slug, 'airtime') || str_contains($slug, 'data') => 'topup',
                default => 'other',
            };

            $chartData[$date][$key] += (float) $row->total;
        }

        return array_values($chartData);
    }

    /**
     * Get the latest registered users with their wallets.
     */
    public function getLatestUsers(int $perPage = 10): LengthAwarePaginator
    {
        return User::with('wallet')->latest()->paginate($perPage);
    }

    /**
     * Get a unified stream of the latest transactions (Payments + Wallet Txns).
     */
    public function getLatestTransactions(int $perPage = 10): LengthAwarePaginator
    {
        $payments = DB::table('payment_attempts')
            ->join('users', 'payment_attempts.user_id', '=', 'users.id')
            ->select(
                'payment_attempts.id',
                DB::raw('COALESCE(payment_attempts.gateway_reference, payment_attempts.idempotency_key) as reference'),
                'users.name as customer_name',
                DB::raw("'payment' as type"),
                'payment_attempts.amount',
                'payment_attempts.currency',
                'payment_attempts.payment_status as status',
                'payment_attempts.created_at as date',
                'payment_attempts.gateway as gateway',
                DB::raw("'payment' as source")
            );

        $walletTransactions = DB::table('wallet_transactions')
            ->join('users', 'wallet_transactions.user_id', '=', 'users.id')
            ->select(
                'wallet_transactions.id',
                'wallet_transactions.reference',
                'users.name as customer_name',
                'wallet_transactions.type as type',
                'wallet_transactions.amount',
                'wallet_transactions.currency',
                DB::raw("'completed' as status"), // Wallet txns are recorded as completed
                'wallet_transactions.created_at as date',
                DB::raw("'wallet' as gateway"),
                DB::raw("'wallet_transaction' as source")
            );

        // Union both tables into a single timeline and paginate natively
        return $payments->unionAll($walletTransactions)
            ->orderByDesc('date')
            ->paginate($perPage);
    }
}
