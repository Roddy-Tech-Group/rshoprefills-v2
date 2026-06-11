<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkdownNegotiationTest extends TestCase
{
    use RefreshDatabase;

    public function test_agents_asking_for_markdown_get_a_markdown_response(): void
    {
        $this->withoutVite();

        $response = $this->withHeaders(['Accept' => 'text/markdown'])
            ->get(route('home'))
            ->assertOk();

        $this->assertStringStartsWith('text/markdown', (string) $response->headers->get('Content-Type'));
        $this->assertTrue($response->headers->has('X-Markdown-Tokens'));
        $this->assertGreaterThan(0, (int) $response->headers->get('X-Markdown-Tokens'));
        $this->assertStringContainsString('Accept', (string) $response->headers->get('Vary'));

        // The body should be markdown text, not an HTML document.
        $this->assertStringNotContainsString('<!DOCTYPE', $response->getContent());
        $this->assertStringNotContainsString('<html', $response->getContent());
    }

    public function test_browsers_still_get_html_by_default(): void
    {
        $this->withoutVite();

        $response = $this->get(route('home'))->assertOk();

        $this->assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));
        $this->assertFalse($response->headers->has('X-Markdown-Tokens'));
        $this->assertStringContainsString('Accept', (string) $response->headers->get('Vary'));
    }
}
