<?php

namespace Database\Seeders;

use App\Domain\Admin\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('ADMIN_DEFAULT_EMAIL', 'dev@roddytechgroup.com');
        $password = env('ADMIN_DEFAULT_PASSWORD', 'Roddy12345@a');

        Admin::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make($password),
                'role' => AdminRole::SuperAdmin,
                'is_active' => true,
            ]
        );
    }
}
