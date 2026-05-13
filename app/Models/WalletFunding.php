<?php

namespace App\Models;

use App\Domain\Shared\Enums\Currency;
use App\Domain\Shared\Enums\FundingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Tracks a wallet funding attempt.
 *
 * @property int $id
 * @property int $user_id
 * @property int $wallet_id
 * @property string $reference
 * @property Currency $currency
 * @property string $amount
 * @property string $gateway
 * @property string|null $gateway_reference
 * @property array|null $gateway_payload
 * @property FundingStatus $status
 * @property string|null $idempotency_key
 * @property Carbon|null $processed_at
 * @property string|null $failed_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WalletFunding extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'reference',
        'currency',
        'amount',
        'gateway',
        'gateway_reference',
        'gateway_payload',
        'status',
        'idempotency_key',
        'processed_at',
        'failed_reason',
    ];

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'amount' => 'decimal:4',
            'gateway_payload' => 'array',
            'status' => FundingStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
