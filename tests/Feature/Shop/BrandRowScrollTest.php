<?php

namespace Tests\Feature\Shop;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The shared brand-row drives both the storefront product rows (grid mode) and
 * the eSIM plan carousel (carousel mode). Both must scroll with native touch
 * momentum on mobile, like the testimonial carousel - so grid mode carries
 * -webkit-overflow-scrolling:touch, and carousel mode must NOT pin a container
 * scroll-behavior:smooth (which overrides that momentum and feels sluggish).
 */
class BrandRowScrollTest extends TestCase
{
    public function test_grid_mode_product_rows_have_native_momentum_scrolling(): void
    {
        $html = Blade::render('<x-home.brand-row title="Products"><li>card</li></x-home.brand-row>');

        $this->assertStringContainsString('[-webkit-overflow-scrolling:touch]', $html);
    }

    public function test_carousel_mode_keeps_momentum_without_overriding_it(): void
    {
        $html = Blade::render('<x-home.brand-row title="Plans" :carousel="true"><li>plan</li></x-home.brand-row>');

        $this->assertStringContainsString('[-webkit-overflow-scrolling:touch]', $html);
        $this->assertStringNotContainsString('scroll-behavior: smooth', $html);
    }
}
