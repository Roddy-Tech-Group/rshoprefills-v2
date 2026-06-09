<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\password as promptPassword;

class SetAdminPasswordCommand extends Command
{
    /**
     * `--generate` prints a strong random password (nothing ends up in shell
     * history); omit both `--generate` and `--password` to be prompted for one
     * securely. `--password` is supported for scripted use but is visible in
     * shell history, so prefer the other two.
     */
    protected $signature = 'admin:set-password
        {email : The admin account email}
        {--password= : New password (visible in shell history, avoid if possible)}
        {--generate : Generate a strong random password and print it once}';

    protected $description = 'Set or rotate an admin account password';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $admin = Admin::where('email', $email)->first();

        if (! $admin) {
            $this->error("No admin found with email {$email}.");
            $known = Admin::pluck('email')->implode(', ');
            $this->line('Known admin emails: '.($known !== '' ? $known : '(none)'));

            return self::FAILURE;
        }

        $generated = false;

        if ($this->option('generate')) {
            $password = Str::password(20);
            $generated = true;
        } elseif ($this->option('password')) {
            $password = (string) $this->option('password');
        } else {
            $password = promptPassword(
                label: "New password for {$email}",
                required: true,
                validate: fn (string $value) => strlen($value) < 8 ? 'Password must be at least 8 characters.' : null,
            );
        }

        // Assign the raw value: the Admin model's `hashed` cast hashes it once on
        // save, so we must NOT pre-hash here (that is the source-of-truth path).
        $admin->password = $password;
        $admin->save();

        $this->info("Password updated for {$email}.");

        if ($generated) {
            $this->line('Generated password: '.$password);
            $this->warn('Store it now. It will not be shown again.');
        }

        return self::SUCCESS;
    }
}
