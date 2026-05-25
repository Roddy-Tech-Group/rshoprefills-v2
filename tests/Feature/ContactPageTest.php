<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_contact_page_renders_for_guests(): void
    {
        $this->withoutVite();

        $this->get(route('shop.contact'))
            ->assertOk()
            ->assertSee('Get in touch')
            ->assertSee('Send us a message');
    }

    public function test_a_visitor_can_submit_a_contact_message(): void
    {
        $response = $this->post(route('contact.send'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
            'subject' => 'Order not delivered',
            'order_id' => 'ORD-AB12CD34',
            'message' => 'I have a question about my recent order, please help.',
        ]);

        $response->assertRedirect(route('shop.contact'));
        $response->assertSessionHas('contact_sent', true);

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'jane@example.test',
            'subject' => 'Order not delivered',
            'order_id' => 'ORD-AB12CD34',
            'status' => 'new',
        ]);
        $this->assertDatabaseHas('admin_notifications', ['type' => 'contact']);
    }

    public function test_submission_requires_name_email_and_message(): void
    {
        $this->post(route('contact.send'), [])
            ->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_a_too_short_message_is_rejected(): void
    {
        $this->post(route('contact.send'), [
            'name' => 'Jane',
            'email' => 'jane@example.test',
            'message' => 'hi',
        ])->assertSessionHasErrors('message');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_honeypot_silently_drops_bot_submissions(): void
    {
        $response = $this->post(route('contact.send'), [
            'name' => 'Spam Bot',
            'email' => 'bot@example.test',
            'message' => 'This is some spam content right here.',
            'website' => 'http://spam.example',
        ]);

        $response->assertRedirect(route('shop.contact'));
        $response->assertSessionHas('contact_sent', true);
        $this->assertSame(0, ContactMessage::count());
    }
}
