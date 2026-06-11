<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rcoin is rewards points, not a spendable wallet - the customer's wallet
 * surfaces must never render it as a wallet card. It has its own rewards UI.
 */
class WalletPageRcoinExclusionTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_wallets_page_hides_the_rcoin_wallet(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Wallet::factory()->for($user)->create(['currency' => 'USD', 'balance' => 25]);
        Wallet::factory()->for($user)->create(['currency' => 'RCOIN', 'balance' => 4373]);

        $response = $this->withoutVite()->actingAs($user)->get(route('dashboard.wallet'));

        $response->assertOk();
        $response->assertSee('USD');
        $response->assertDontSee('RCOIN');
    }
}
