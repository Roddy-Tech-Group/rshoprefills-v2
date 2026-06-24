<?php

namespace Tests\Feature\Cart;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The nav cart popup (which opens on add-to-cart) is a bottom sheet on mobile so
 * it pops up in view even when the shopper added from far down the page; sm+
 * keeps the desktop top-right card. Guards the responsive positioning classes.
 */
class CartPopupMobileTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_popup_is_a_bottom_sheet_on_mobile_and_top_right_on_desktop(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('inset-x-3 bottom-3', false)   // mobile: bottom sheet
            ->assertSee('sm:top-[84px]', false);       // sm+: restores desktop top-right
    }
}
