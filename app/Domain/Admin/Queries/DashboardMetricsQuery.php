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
     */
    public function getOverviewMetrics(): array
    {
        $totalUsers = User::count();
        $totalOrders = Order::count();

        // Calculate revenue from successful payments
        $totalRevenue = PaymentAttempt::where('payment_status', PaymentStatus::Paid)->sum('amount');

        $paymentCount = PaymentAttempt::count();
        $walletTxCount = WalletTransaction::count();
        $transactionsCount = $paymentCount + $walletTxCount;

        $successfulPayments = PaymentAttempt::where('payment_status', PaymentStatus::Paid)->count();
        $successRate = $paymentCount > 0 ? round(($successfulPayments / $paymentCount) * 100, 2) : 0.0;

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
     * Get revenue chart data aggregated by date and product category.
     */
    public function getRevenueChartData(string $range = '7d'): array
    {
        $startDate = match ($range) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '6m' => now()->subMonths(6),
            '1y' => now()->subYear(),
            default => now()->subDays(7),
        };

        // Use monthly grouping for long ranges, daily for short ranges
        $dateFormat = in_array($range, ['6m', '1y']) ? '%Y-%m' : '%Y-%m-%d';

        // Aggregate completed orders by product type
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
