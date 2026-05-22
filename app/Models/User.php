<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
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
 * @property string|null $gender "male" | "female" | "other"
 * @property string|null $google_id
 * @property string|null $avatar_url
 * @property string $theme "light" | "dark" | "system"
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $remember_token
 * @property string|null $transaction_pin
 * @property Carbon|null $transaction_pin_set_at
 * @property int $transaction_pin_attempts
 * @property Carbon|null $transaction_pin_locked_until
 * @property Carbon|null $last_transaction_pin_used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class User extends Authenticatable implements MustVerifyEmail
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
        'referral_code',
        'password',
        'google_id',
        'avatar_url',
        'avatar',
        'theme',
        'kyc_status',
        'banned_at',
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
        'transaction_pin',
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
            'banned_at' => 'datetime',
            'password' => 'hashed',
            'transaction_pin_set_at' => 'datetime',
            'transaction_pin_locked_until' => 'datetime',
            'last_transaction_pin_used_at' => 'datetime',
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

    /**
     * Whether the account is suspended (banned).
     */
    public function isBanned(): bool
    {
        return $this->banned_at !== null;
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
     * Get all KYC submissions for this user, newest first.
     */
    public function kycSubmissions(): HasMany
    {
        return $this->hasMany(KycSubmission::class)->latest();
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

    /**
     * Get all payment attempts for this user.
     */
    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    /**
     * Get all persistent notifications for this user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the user's notification preferences.
     */
    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class, 'user_id');
    }

    // ────────────────────────────────────────────────────────────
    //  Transaction PIN Orchestration
    // ────────────────────────────────────────────────────────────

    public function hasTransactionPin(): bool
    {
        return ! is_null($this->transaction_pin);
    }

    public function verifyTransactionPin(string $pin): bool
    {
        if (! $this->hasTransactionPin()) {
            return false;
        }

        return Hash::check($pin, $this->transaction_pin);
    }

    public function incrementTransactionPinAttempts(): void
    {
        $this->transaction_pin_attempts++;
        $this->save();
    }

    public function resetTransactionPinAttempts(): void
    {
        if ($this->transaction_pin_attempts > 0 || $this->transaction_pin_locked_until !== null) {
            $this->transaction_pin_attempts = 0;
            $this->transaction_pin_locked_until = null;
            $this->save();
        }
    }

    public function lockTransactionPin(int $minutes = 15): void
    {
        $this->transaction_pin_locked_until = now()->addMinutes($minutes);
        $this->save();
    }

    public function isTransactionPinLocked(): bool
    {
        return $this->transaction_pin_locked_until && $this->transaction_pin_locked_until->isFuture();
    }

    public function markTransactionPinUsed(): void
    {
        $this->last_transaction_pin_used_at = now();
        $this->save();
    }
}
