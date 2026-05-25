<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarnPointsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_earn_points_page_renders_for_guests(): void
    {
        $this->withoutVite();

        $this->get(route('shop.earn-points'))
            ->assertOk()
            ->assertSee('Earn points every time you shop')
            ->assertSee('Rcoin')
            ->assertSee('Refer your friends');
    }
}
