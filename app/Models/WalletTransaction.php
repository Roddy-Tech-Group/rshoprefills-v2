<?php

namespace App\Models;

use App\Domain\Shared\Enums\Currency;
use App\Domain\Shared\Enums\TransactionCategory;
use App\Domain\Shared\Enums\WalletTransactionType;
use Database\Factories\WalletTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Records a single credit or debit movement on a wallet.
 *
 * Every transaction captures balance_before and balance_after to create
 * a full audit trail. This makes reconciliation possible without replaying
 * the entire transaction history.
 *
 * @property int $id
 * @property int $wallet_id
 * @property int $user_id
 * @property WalletTransactionType $type
 * @property string $amount
 * @property string $balance_before
 * @property string $balance_after
 * @property string|null $description
 * @property string|null $reference
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WalletTransaction extends Model
{
    /** @use HasFactory<WalletTransactionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'wallet_id',
        'user_id',
        'type',
        'currency',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference',
        'transaction_category',
        'transaction_group',
        'idempotency_key',
        'source_type',
        'source_id',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WalletTransactionType::class,
            'currency' => Currency::class,
            'amount' => 'decimal:4',
            'balance_before' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'transaction_category' => TransactionCategory::class,
            'metadata' => 'array',
        ];
    }

    /**
     * Get the wallet this transaction belongs to.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the user who owns this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
