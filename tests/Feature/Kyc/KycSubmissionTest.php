<?php

namespace Tests\Feature\Kyc;

use App\Models\KycSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_submit_kyc_documents(): void
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
        ])->assertRedirect();

        $submission = KycSubmission::where('user_id', $user->id)->first();

        $this->assertNotNull($submission);
        $this->assertSame('pending', $submission->status);
        $this->assertSame('pending', $user->fresh()->kyc_status);
        Storage::disk('local')->assertExists($submission->document_front_path);
        Storage::disk('local')->assertExists($submission->selfie_path);
    }

    public function test_submission_requires_front_document_and_selfie(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['kyc_status' => 'unsubmitted']);

        $this->actingAs($user)->post(route('kyc.submit'), [
            'kyc_full_name' => 'Jane Doe',
            'kyc_dob' => '1995-04-12',
            'kyc_country' => 'Nigeria',
            'kyc_document_type' => 'passport',
            'kyc_document_number' => 'A1234567',
        ])->assertSessionHasErrors(['kyc_document_front', 'kyc_selfie']);

        $this->assertSame(0, KycSubmission::count());
    }

    public function test_a_user_cannot_submit_while_already_pending(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['kyc_status' => 'pending']);

        $this->actingAs($user)->post(route('kyc.submit'), [
            'kyc_full_name' => 'Jane Doe',
            'kyc_dob' => '1995-04-12',
            'kyc_country' => 'Nigeria',
            'kyc_document_type' => 'passport',
            'kyc_document_number' => 'A1234567',
            'kyc_document_front' => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
            'kyc_selfie' => UploadedFile::fake()->create('selfie.jpg', 100, 'image/jpeg'),
        ])->assertRedirect();

        $this->assertSame(0, KycSubmission::count());
    }
}
