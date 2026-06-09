<?php

namespace Database\Seeders;

use App\Domain\Admin\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Admin credentials are never hardcoded. In production both ADMIN_DEFAULT_EMAIL
     * and ADMIN_DEFAULT_PASSWORD must be supplied, otherwise we refuse to seed a
     * guessable account. In local/testing we fall back to a generated password
     * (surfaced once via the console) so nothing secret is baked into the repo.
     */
    public function run(): void
    {
        $email = env('ADMIN_DEFAULT_EMAIL');
        $password = env('ADMIN_DEFAULT_PASSWORD');

        if (empty($email) || empty($password)) {
            if (app()->isProduction()) {
                throw new \RuntimeException(
                    'ADMIN_DEFAULT_EMAIL and ADMIN_DEFAULT_PASSWORD must be set before seeding the admin in production.'
                );
            }

            $email = $email ?: 'admin@rshoprefills.test';
            $password = $password ?: Str::password(20);

            $this->command?->warn('AdminSeeder: ADMIN_DEFAULT_* not set, using generated local credentials:');
            $this->command?->line("  email:    {$email}");
            $this->command?->line("  password: {$password}");
        }

        // Pass the raw password: the Admin model's `hashed` cast hashes it on save.
        Admin::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Administrator',
                'password' => $password,
                'role' => AdminRole::SuperAdmin,
                'is_active' => true,
            ]
        );
    }
}
