<?php

namespace App\Models;

use App\Domain\Admin\Enums\AdminRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Represents a platform administrator.
 *
 * Extends Authenticatable so it works seamlessly with Laravel's
 * auth system as a separate guard provider. Completely isolated
 * from the User model — different table, different guard,
 * different session.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property AdminRole $role
 * @property string|null $avatar_url
 * @property string $theme "light" | "dark" | "system"
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 * @property string|null $remember_token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The table associated with the model.
     */
    protected $table = 'admins';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar_url',
        'theme',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => AdminRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // ────────────────────────────────────────────────────────────
    //  Helper Methods
    // ────────────────────────────────────────────────────────────

    /**
     * Check if this admin has the super_admin role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === AdminRole::SuperAdmin;
    }

    /**
     * Check if this admin account is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Get the admin's initials for avatar fallback.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /**
     * A deterministic SVG initials avatar (base64 data URI) for use when the
     * admin hasn't uploaded a photo. Mirrors {@see User::initialsAvatar()}.
     */
    public function initialsAvatar(int $size = 128): string
    {
        $initials = Str::of($this->initials())->upper()->substr(0, 2)->value() ?: 'U';
        $initials = htmlspecialchars($initials, ENT_QUOTES);

        $palette = ['#2563eb', '#7c3aed', '#0d9488', '#dc2626', '#ea580c', '#0891b2', '#4f46e5', '#db2777', '#16a34a', '#d97706'];
        $bg = $palette[crc32((string) ($this->name ?: $this->email ?: 'admin')) % count($palette)];
        $fontSize = (int) round($size * 0.42);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$size.' '.$size.'">'
            .'<rect width="'.$size.'" height="'.$size.'" fill="'.$bg.'"/>'
            .'<text x="50%" y="50%" dy=".05em" fill="#ffffff" font-family="system-ui,-apple-system,Segoe UI,Roboto,sans-serif" font-size="'.$fontSize.'" font-weight="600" text-anchor="middle" dominant-baseline="central">'.$initials.'</text>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Record a login timestamp.
     */
    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}
