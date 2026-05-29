<?php

namespace App\Domain\Admin\Services\Dashboard;

use App\Domain\Admin\Queries\DashboardMetricsQuery;
use App\Domain\Admin\Resources\DashboardOverviewResource;
use App\Domain\Admin\Resources\LatestTransactionResource;
use App\Domain\Admin\Resources\LatestUserResource;
use App\Domain\Admin\Resources\RevenueChartResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Service to orchestrate the retrieval and formatting of dashboard metrics.
 */
class AdminDashboardService
{
    public function __construct(
        private readonly DashboardMetricsQuery $metricsQuery,
    ) {}

    /**
     * Get aggregated overview metrics formatted for the API.
     */
    public function getOverview(): DashboardOverviewResource
    {
        $metrics = $this->metricsQuery->getOverviewMetrics();

        return new DashboardOverviewResource($metrics);
    }

    /**
     * Get revenue chart data formatted for the API.
     */
    public function getRevenueChart(string $range): AnonymousResourceCollection
    {
        $data = $this->metricsQuery->getRevenueChartData($range);

        return RevenueChartResource::collection($data);
    }

    /**
     * Get the latest paginated users formatted for the API.
     */
    public function getLatestUsers(int $perPage = 10): AnonymousResourceCollection
    {
        $users = $this->metricsQuery->getLatestUsers($perPage);

        return LatestUserResource::collection($users);
    }

    /**
     * Get the latest unified transactions formatted for the API.
     */
    public function getLatestTransactions(int $perPage = 10): AnonymousResourceCollection
    {
        $transactions = $this->metricsQuery->getLatestTransactions($perPage);

        return LatestTransactionResource::collection($transactions);
    }
}
