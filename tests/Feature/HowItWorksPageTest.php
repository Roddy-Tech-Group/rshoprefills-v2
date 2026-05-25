<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HowItWorksPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_how_it_works_page_renders_for_guests(): void
    {
        $this->withoutVite();

        $this->get(route('shop.how-it-works'))
            ->assertOk()
            ->assertSee('Shopping made simple')
            ->assertSee('Easy and convenient')
            ->assertSee('Step 1')
            ->assertSee('Ready to shop today?');
    }
}
