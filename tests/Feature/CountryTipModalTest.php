<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The storefront greets first-time visitors with a one-per-session modal that
 * points them at the country pill so they can switch the catalogue to their
 * own region. It lives in the storefront layout, so every storefront page
 * carries it; the sessionStorage gate keeps it to a single appearance.
 */
class CountryTipModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_renders_the_first_visit_country_nudge(): void
    {
        $this->withoutVite();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rshopRegionTip', false)        // once-per-session gate
            ->assertSee('country pill')                  // the element it points visitors to
            ->assertSee('Switch country')                // CTA that opens the locale switcher
            ->assertSee('Pay in your currency');         // step in the how-it-works flow pill
    }
}
