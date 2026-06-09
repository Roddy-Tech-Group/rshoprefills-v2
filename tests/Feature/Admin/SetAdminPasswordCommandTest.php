<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetAdminPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'rotate@example.test'): Admin
    {
        return Admin::create([
            'email' => $email,
            'name' => 'Rotate Admin',
            'password' => 'OldPassword1!',
            'role' => AdminRole::SuperAdmin,
            'is_active' => true,
        ]);
    }

    public function test_it_sets_a_new_password_that_authenticates(): void
    {
        $admin = $this->admin();

        $this->artisan('admin:set-password', [
            'email' => $admin->email,
            '--password' => 'BrandNewPass1!',
        ])->assertSuccessful();

        $admin->refresh();
        // Stored value is hashed once (not double-hashed) and verifies cleanly.
        $this->assertTrue(Hash::check('BrandNewPass1!', $admin->password));
        $this->assertFalse(Hash::check('OldPassword1!', $admin->password));
    }

    public function test_generate_option_sets_a_working_random_password(): void
    {
        $admin = $this->admin('gen@example.test');
        $original = $admin->password;

        $this->artisan('admin:set-password', [
            'email' => $admin->email,
            '--generate' => true,
        ])->assertSuccessful();

        $admin->refresh();
        $this->assertNotSame($original, $admin->password);
    }

    public function test_it_fails_for_unknown_admin(): void
    {
        $this->artisan('admin:set-password', [
            'email' => 'nobody@example.test',
            '--password' => 'whatever1!',
        ])->assertFailed();
    }
}
