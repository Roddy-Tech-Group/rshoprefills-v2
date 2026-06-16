<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\DashboardContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The dashboard chart canvases are wire:ignore (Livewire would wipe the
 * rendered SVG), so every server-side filter must push its fresh aggregates
 * to the page via a browser event - that is what makes the filters live,
 * with no reload.
 */
class DashboardChartFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_trends_period_filter_dispatches_fresh_series(): void
    {
        Livewire::test(DashboardContent::class)
            ->call('setRevenueDays', 7)
            ->assertSet('revenueDays', 7)
            ->assertDispatched('trends-data-updated');
    }

    public function test_map_period_filter_dispatches_fresh_aggregates(): void
    {
        Livewire::test(DashboardContent::class)
            ->call('setCountryDays', 30)
            ->assertSet('countryDays', 30)
            ->assertDispatched('map-data-updated');
    }

    public function test_map_category_filter_dispatches_fresh_aggregates(): void
    {
        Livewire::test(DashboardContent::class)
            ->call('setCountryCategory', 'esim')
            ->assertSet('countryCategory', 'esim')
            ->assertDispatched('map-data-updated');
    }
}
