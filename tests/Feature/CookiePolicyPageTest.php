<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookiePolicyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_cookie_policy_page_renders_for_guests(): void
    {
        $this->withoutVite();

        $this->get(route('shop.cookie-policy'))
            ->assertOk()
            ->assertSee('Cookie Policy')
            ->assertSee('What are cookies')
            ->assertSee('Types of cookies we use')
            ->assertSee('Managing your cookies');
    }
}
