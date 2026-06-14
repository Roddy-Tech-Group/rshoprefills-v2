<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The storefront announcement bar shows up to three admin-set coupon/promo
 * messages and hides entirely when all three slots are blank.
 */
class AnnouncementBarTest extends TestCase
{
    use RefreshDatabase;

    public function test_bar_is_hidden_when_all_slots_blank(): void
    {
        $this->withoutVite();
        SiteSetting::put('announcement.promo_1', '', 'announcement');
        SiteSetting::put('announcement.promo_2', '', 'announcement');
        SiteSetting::put('announcement.promo_3', '', 'announcement');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('id="rshop-promo-bar"', false);
    }

    public function test_bar_shows_a_set_promo(): void
    {
        $this->withoutVite();
        SiteSetting::put('announcement.promo_1', 'Use LAUNCHV2 to get 9% off', 'announcement');

        $this->get('/')
            ->assertOk()
            ->assertSee('Use LAUNCHV2 to get 9% off', false);
    }

    public function test_bar_carries_all_filled_slots_for_rotation(): void
    {
        $this->withoutVite();
        SiteSetting::put('announcement.promo_1', 'Promo One', 'announcement');
        SiteSetting::put('announcement.promo_2', '', 'announcement');
        SiteSetting::put('announcement.promo_3', 'Promo Three', 'announcement');

        $html = $this->get('/')->assertOk()->getContent();

        // Both non-blank slots are present; the blank middle one is skipped.
        $this->assertStringContainsString('Promo One', $html);
        $this->assertStringContainsString('Promo Three', $html);
    }
}
