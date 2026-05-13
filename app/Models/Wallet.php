<?php

namespace App\Models;

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
        'currency',
        'is_active',
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
            'is_active' => 'boolean',
        ];
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
