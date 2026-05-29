<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAppPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_mobile_app_page_renders(): void
    {
        $this->withoutVite();

        $this->get(route('shop.mobile-app'))
            ->assertOk()
            ->assertSee('Our mobile app is on the way')
            ->assertSee('App Store')
            ->assertSee('Google Play');
    }
}
