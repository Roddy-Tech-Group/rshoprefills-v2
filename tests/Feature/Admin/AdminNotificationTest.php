<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kyc_submission_creates_an_admin_notification(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['kyc_status' => 'unsubmitted']);

        $this->actingAs($user)->post(route('kyc.submit'), [
            'kyc_full_name' => 'Jane Doe',
            'kyc_dob' => '1995-04-12',
            'kyc_country' => 'Nigeria',
            'kyc_document_type' => 'passport',
            'kyc_document_number' => 'A1234567',
            'kyc_document_front' => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
            'kyc_selfie' => UploadedFile::fake()->create('selfie.jpg', 100, 'image/jpeg'),
        ]);

        $this->assertDatabaseHas('admin_notifications', ['type' => 'kyc']);
    }

    public function test_a_new_registration_creates_an_admin_notification(): void
    {
        $user = User::factory()->create();

        event(new Registered($user));

        $this->assertDatabaseHas('admin_notifications', ['type' => 'customer']);
    }
}
