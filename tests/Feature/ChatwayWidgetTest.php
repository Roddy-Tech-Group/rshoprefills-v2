<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatwayWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_renders_on_storefront_when_configured(): void
    {
        $this->withoutVite();
        config(['services.chatway.widget_id' => 'test-widget-123']);

        $this->get('/')
            ->assertOk()
            ->assertSee('cdn.chatway.app/widget.js?id=test-widget-123', false);
    }

    public function test_widget_is_absent_on_the_dashboard(): void
    {
        $this->withoutVite();
        config(['services.chatway.widget_id' => 'test-widget-123']);

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('cdn.chatway.app/widget.js', false);
    }

    public function test_widget_is_absent_when_no_id_is_configured(): void
    {
        $this->withoutVite();
        config(['services.chatway.widget_id' => null]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('cdn.chatway.app', false);
    }
}
