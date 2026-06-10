<?php

namespace App\Models;

use App\Domain\Shared\Enums\Currency;
use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Represents a user's wallet — a single balance container.
 *
 * Each user has exactly one wallet (enforced by unique FK in migration).
 * The wallet holds a balance in a specific currency and tracks all
 * movements through WalletTransaction records.
 *
 * @property int $id
 * @property int $user_id
 * @property string $balance
 * @property string $currency
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'balance',
        'locked_balance',
        'currency',
        'is_active',
        'last_activity_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:4',
            'locked_balance' => 'decimal:4',
            'currency' => Currency::class,
            'is_active' => 'boolean',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Helper to get total available balance (balance - locked).
     */
    public function availableBalance(): float
    {
        return max(0, (float) $this->balance - (float) $this->locked_balance);
    }

    /**
     * Format a USD amount for the compact nav wallet chip: exact cents below
     * $1,000, then abbreviated as $1.2k / $3.4M above so the chip stays narrow.
     * Truncated (never rounded up) to one decimal so a balance is never shown
     * larger than it actually is.
     */
    public static function compactUsd(float $amount): string
    {
        $abs = abs($amount);

        if ($abs >= 1_000_000) {
            return '$'.self::abbreviate($amount, 1_000_000).'M';
        }

        if ($abs >= 1_000) {
            return '$'.self::abbreviate($amount, 1_000).'k';
        }

        return '$'.number_format($amount, 2);
    }

    /**
     * One-decimal, truncated-toward-zero abbreviation of `$amount` in `$unit`s,
     * with a trailing `.0` dropped (so $1,000 -> "1", $1,200 -> "1.2"). Works in
     * integer tenths to dodge binary-float rounding (1.2 * 10 != 12.0).
     */
    private static function abbreviate(float $amount, int $unit): string
    {
        $tenths = (int) floor($amount / ($unit / 10));
        $whole = intdiv($tenths, 10);
        $fraction = $tenths % 10;

        return $fraction === 0 ? (string) $whole : $whole.'.'.$fraction;
    }

    /**
     * Get the user who owns this wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
