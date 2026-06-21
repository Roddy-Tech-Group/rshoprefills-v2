<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\User;
use Database\Seeders\FaqSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_help_page_renders_for_guests(): void
    {
        $this->withoutVite();
        $this->seed(FaqSeeder::class);

        $this->get(route('shop.help'))
            ->assertOk()
            ->assertSee('How can we help?')
            ->assertSee('Frequently asked questions')
            ->assertSee('support@rshoprefill.com');
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

    public function test_faq_entries_load_from_the_database(): void
    {
        $this->withoutVite();
        Faq::factory()->create([
            'topic' => 'Orders & Delivery',
            'question' => 'Can pigs really fly?',
            'answer' => 'Only on Tuesdays in this universe.',
            'is_published' => true,
        ]);

        $this->get(route('shop.help'))
            ->assertOk()
            ->assertSee('Can pigs really fly?')
            ->assertSee('Only on Tuesdays in this universe.');
    }

    public function test_unpublished_faqs_are_hidden(): void
    {
        $this->withoutVite();
        Faq::factory()->create([
            'topic' => 'Orders & Delivery',
            'question' => 'Will this question appear?',
            'answer' => 'No, because it is unpublished.',
            'is_published' => false,
        ]);

        $this->get(route('shop.help'))
            ->assertOk()
            ->assertDontSee('Will this question appear?');
    }
}
