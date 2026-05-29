<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_html_sitemap_renders_for_guests(): void
    {
        $this->withoutVite();

        $this->get(route('shop.sitemap'))
            ->assertOk()
            ->assertSee('Find your way around')
            ->assertSee('Gift Cards')
            ->assertSee('Privacy Policy');
    }

    public function test_the_xml_sitemap_returns_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee('<urlset', false);
        $response->assertSee('gift-cards', false);
    }
}
