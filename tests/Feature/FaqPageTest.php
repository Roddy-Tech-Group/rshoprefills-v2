<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_faq_page_renders_for_guests(): void
    {
        $this->withoutVite();

        $this->get(route('shop.faq'))
            ->assertOk()
            ->assertSee('Everything you need to know')
            ->assertSee('What is RshopRefills?')
            ->assertSee('What are network fees?')
            ->assertSee('Transaction PIN and security');
    }
}
