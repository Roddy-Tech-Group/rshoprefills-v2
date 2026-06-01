<?php

namespace Tests\Feature;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_attributes_a_referral_when_the_code_matches_another_user(): void
    {
        $referrer = User::factory()->create(['referral_code' => 'FRIEND123']);
        $referred = User::factory()->create();

        $referral = Referral::attribute($referred, 'FRIEND123');

        $this->assertNotNull($referral);
        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $referrer->id,
            'referred_user_id' => $referred->id,
            'status' => 'active',
        ]);
    }

    public function test_it_trims_whitespace_around_the_code(): void
    {
        $referrer = User::factory()->create(['referral_code' => 'FRIEND123']);
        $referred = User::factory()->create();

        $referral = Referral::attribute($referred, '  FRIEND123  ');

        $this->assertNotNull($referral);
        $this->assertSame($referrer->id, $referral->referrer_id);
    }

    public function test_it_does_nothing_for_a_blank_code(): void
    {
        $referred = User::factory()->create();

        $this->assertNull(Referral::attribute($referred, ''));
        $this->assertNull(Referral::attribute($referred, null));
        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_it_does_nothing_for_an_unknown_code(): void
    {
        $referred = User::factory()->create();

        $this->assertNull(Referral::attribute($referred, 'NOPE-NO-MATCH'));
        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_it_rejects_self_referral(): void
    {
        $user = User::factory()->create(['referral_code' => 'MINE777']);

        $this->assertNull(Referral::attribute($user, 'MINE777'));
        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_first_touch_wins_and_existing_attribution_is_kept(): void
    {
        $first = User::factory()->create(['referral_code' => 'FIRST111']);
        $second = User::factory()->create(['referral_code' => 'SECOND22']);
        $referred = User::factory()->create();

        Referral::attribute($referred, 'FIRST111');
        $kept = Referral::attribute($referred, 'SECOND22');

        $this->assertSame($first->id, $kept->referrer_id);
        $this->assertDatabaseCount('referrals', 1);
    }
}
