<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Enums\AdminRole;
use App\Domain\Shared\Enums\Currency;
use App\Models\Admin;
use App\Models\CurrencyRate;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin customers list must show each customer's TOTAL holdings in USD.
 * It previously read only the USD wallet, so customers holding their balance
 * in XAF/NGN showed 0.00 (or "No wallet") despite real money on the account.
 */
class AdminCustomersWalletColumnTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): self
    {
        $this->seed(AdminSeeder::class);
        $admin = Admin::firstOrCreate(
            ['email' => 'test-customers@example.test'],
            ['name' => 'Customers Admin', 'password' => 'password', 'role' => AdminRole::SuperAdmin, 'is_active' => true],
        );
        $this->actingAs($admin, 'admin');

        return $this;
    }

    public function test_non_usd_wallet_balances_show_as_usd_equivalent(): void
    {
        $this->withoutVite()->asAdmin();

        CurrencyRate::updateOrCreate(['code' => 'XAF'], [
            'name' => 'CFA Franc', 'type' => 'fiat', 'rate_per_usd' => 567.0, 'is_active' => true,
        ]);

        $user = User::factory()->create();
        Wallet::create([
            'user_id' => $user->id,
            'currency' => Currency::XAF,
            'balance' => 56700, // = $100 at 567/USD
            'is_active' => true,
        ]);

        $this->get(route('admin.customers'))
            ->assertOk()
            ->assertSee('$100.00 USD');
    }

    public function test_user_without_wallets_shows_no_wallet(): void
    {
        $this->withoutVite()->asAdmin();

        User::factory()->create();

        $this->get(route('admin.customers'))
            ->assertOk()
            ->assertSee('No wallet');
    }
}
