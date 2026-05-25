<?php

namespace Tests\Feature;

use App\Models\User;
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
            ->assertSee('info@rshoprefill.com');
    }

    public function test_account_dropdown_links_are_wired_for_authenticated_users(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        // Rendering any storefront page exercises the authed nav dropdown.
        $this->actingAs($user)
            ->get(route('shop.help'))
            ->assertOk()
            ->assertSee(route('dashboard.orders'), false)
            ->assertSee(route('dashboard.rewards'), false)
            ->assertSee(route('dashboard.kyc'), false);
    }
}
