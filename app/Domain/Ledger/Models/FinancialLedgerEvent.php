<?php

namespace App\Domain\Ledger\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialLedgerEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'wallet_id',
        'wallet_transaction_id',
        'event_type',
        'amount',
        'balance_after',
        'currency',
        'hash',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public static function generateHash(array $data, ?string $previousHash = null): string
    {
        $payload = json_encode([
            'wallet_id' => $data['wallet_id'],
            'wallet_transaction_id' => $data['wallet_transaction_id'] ?? null,
            'event_type' => $data['event_type'],
            'amount' => $data['amount'],
            'balance_after' => $data['balance_after'],
            'currency' => $data['currency'],
            'previous_hash' => $previousHash,
        ]);

        return hash('sha256', $payload . config('app.key'));
    }
}
