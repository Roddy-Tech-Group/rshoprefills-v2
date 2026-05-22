<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_help_page_renders_for_guests(): void
    {
        $this->withoutVite();

        $this->get(route('shop.help'))
            ->assertOk()
            ->assertSee('How can we help?')
            ->assertSee('Frequently asked questions')
            ->assertSee('support@rshoprefills.com');
    }
}
