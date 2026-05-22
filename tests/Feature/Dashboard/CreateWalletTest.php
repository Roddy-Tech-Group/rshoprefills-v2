<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CreateWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_open_a_wallet_in_a_new_currency(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertDatabaseMissing('wallets', ['user_id' => $user->id, 'currency' => 'GBP']);

        Volt::test('dashboard.create-wallet')
            ->set('currency', 'GBP')
            ->call('create')
            ->assertRedirect(route('dashboard.wallet'));

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'currency' => 'GBP',
        ]);
    }

    public function test_creating_a_wallet_is_idempotent(): void
    {
        $user = User::factory()->create();
        Wallet::create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'balance' => 0,
            'locked_balance' => 0,
            'is_active' => true,
        ]);
        $this->actingAs($user);

        Volt::test('dashboard.create-wallet')
            ->set('currency', 'USD')
            ->call('create');

        $this->assertSame(1, Wallet::where('user_id', $user->id)->where('currency', 'USD')->count());
    }

    public function test_available_currencies_excludes_ones_already_held(): void
    {
        $user = User::factory()->create();
        Wallet::create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'balance' => 0,
            'locked_balance' => 0,
            'is_active' => true,
        ]);
        $this->actingAs($user);

        $available = collect(Volt::test('dashboard.create-wallet')->instance()->availableCurrencies())
            ->map(fn ($currency) => $currency->value)
            ->all();

        $this->assertNotContains('USD', $available);
        $this->assertContains('GBP', $available);
    }
}
