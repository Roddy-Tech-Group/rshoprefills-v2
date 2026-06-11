<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_html_pages_advertise_discovery_links_via_rfc8288_header(): void
    {
        $this->withoutVite();

        $response = $this->get(route('home'))->assertOk();

        $link = $response->headers->get('Link');

        $this->assertNotNull($link, 'Homepage must send a Link header for agent discovery.');
        $this->assertStringContainsString('</.well-known/api-catalog>; rel="api-catalog"', $link);
        $this->assertStringContainsString('</sitemap.xml>; rel="sitemap"', $link);
    }

    public function test_the_api_catalog_returns_a_linkset_document(): void
    {
        $response = $this->get(route('well-known.api-catalog'))->assertOk();

        $this->assertStringStartsWith('application/linkset+json', (string) $response->headers->get('Content-Type'));

        $response->assertJsonStructure([
            'linkset' => [
                ['anchor', 'service-doc', 'describedby'],
            ],
        ]);

        $this->assertSame(route('shop.sitemap'), $response->json('linkset.0.service-doc.0.href'));
        $this->assertSame(route('sitemap.xml'), $response->json('linkset.0.describedby.0.href'));
    }

    public function test_the_middleware_does_not_clobber_a_link_header_a_controller_already_set(): void
    {
        $this->withoutVite();

        // The XML sitemap is not text/html, so it must not receive the discovery Link header.
        $response = $this->get(route('sitemap.xml'))->assertOk();

        $this->assertNull($response->headers->get('Link'));
    }
}
