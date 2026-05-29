<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\Resources\DashboardOverviewResource;
use App\Domain\Admin\Services\Dashboard\AdminDashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboardService,
    ) {}

    /**
     * Get the high-level aggregated overview metrics.
     */
    public function overview(): DashboardOverviewResource
    {
        return $this->dashboardService->getOverview();
    }

    /**
     * Get revenue chart data.
     * Supports range query parameter: 7d, 30d, 6m, 1y.
     */
    public function revenueChart(Request $request): AnonymousResourceCollection
    {
        $range = $request->query('range', '7d');

        // Simple validation to ensure only allowed ranges pass through
        if (! in_array($range, ['7d', '30d', '6m', '1y'])) {
            $range = '7d';
        }

        return $this->dashboardService->getRevenueChart($range);
    }

    /**
     * Get the latest registered users.
     */
    public function latestUsers(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 10);

        return $this->dashboardService->getLatestUsers($perPage);
    }

    /**
     * Get the latest combined transactions.
     */
    public function latestTransactions(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 10);

        return $this->dashboardService->getLatestTransactions($perPage);
    }
}
