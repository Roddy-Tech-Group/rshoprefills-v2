<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerActionsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => AdminRole::SuperAdmin,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_edit_a_customer(): void
    {
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.test']);

        $this->actingAs($this->admin(), 'admin')
            ->patch(route('admin.customer.update', $user), [
                'name' => 'New Name',
                'email' => 'new@example.test',
                'phone' => '237600000000',
                'gender' => 'male',
            ])->assertRedirect();

        $fresh = $user->fresh();
        $this->assertSame('New Name', $fresh->name);
        $this->assertSame('new@example.test', $fresh->email);
        $this->assertSame('male', $fresh->gender);
    }

    public function test_admin_can_ban_and_unban(): void
    {
        $user = User::factory()->create(['banned_at' => null]);
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('admin.customer.ban', $user))->assertRedirect();
        $this->assertNotNull($user->fresh()->banned_at);

        $this->actingAs($admin, 'admin')->post(route('admin.customer.ban', $user))->assertRedirect();
        $this->assertNull($user->fresh()->banned_at);
    }

    public function test_a_banned_customer_is_signed_out_and_blocked(): void
    {
        $user = User::factory()->create(['banned_at' => now()]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_hold_and_release_funds(): void
    {
        $user = User::factory()->create();
        Wallet::create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'balance' => 50,
            'locked_balance' => 0,
            'is_active' => true,
        ]);
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('admin.customer.funds', $user))->assertRedirect();
        $this->assertFalse((bool) $user->wallets()->first()->is_active);

        $this->actingAs($admin, 'admin')->post(route('admin.customer.funds', $user))->assertRedirect();
        $this->assertTrue((bool) $user->wallets()->first()->is_active);
    }
}
