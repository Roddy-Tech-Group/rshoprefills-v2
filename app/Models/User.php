<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Represents a platform user.
 *
 * password is nullable to support Google OAuth users who sign up
 * without a password. The google_id column will be added in a
 * future migration when we implement Google authentication.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $gender   "male" | "female" | "other"
 * @property string|null $google_id
 * @property string|null $avatar_url
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $remember_token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class User extends Authenticatable // implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'password',
        'google_id',
        'avatar_url',
        'avatar',
        'email_verified_at',
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    // ────────────────────────────────────────────────────────────
    //  Commerce Relationships
    // ────────────────────────────────────────────────────────────

    /**
     * Get a specific wallet by currency. Defaults to USD for legacy support.
     */
    public function wallet(string $currency = 'USD'): HasOne
    {
        return $this->hasOne(Wallet::class)->where('currency', $currency);
    }

    /**
     * Get all wallets owned by this user across different currencies.
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Get all wallet transactions for this user.
     *
     * Denormalized relationship — wallet_transactions has a direct
     * user_id column for fast queries without joining through wallets.
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get all orders placed by this user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all payment records for this user.
     *
     * Denormalized relationship — payments has a direct user_id
     * column for fast queries without joining through orders.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
