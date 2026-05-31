<?php

namespace Tests\Feature;

use Database\Seeders\FaqSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_faq_page_renders_for_guests(): void
    {
        $this->withoutVite();
        $this->seed(FaqSeeder::class);

        $this->get(route('shop.faq'))
            ->assertOk()
            ->assertSee('Everything you need to know')
            ->assertSee('How fast is delivery?')
            ->assertSee('What payment methods can I use?')
            // assertSeeText strips HTML entities, so the `&amp;` rendered by
            // Blade becomes `&` for comparison.
            ->assertSeeText('Transaction PIN & Security');
    }
}
