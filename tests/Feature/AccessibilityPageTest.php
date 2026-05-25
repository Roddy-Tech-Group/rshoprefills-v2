<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilityPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_accessibility_page_renders_for_guests(): void
    {
        $this->withoutVite();

        $this->get(route('shop.accessibility'))
            ->assertOk()
            ->assertSee('Accessibility Statement')
            ->assertSee('WCAG')
            ->assertSee('info@rshoprefill.com');
    }
}
