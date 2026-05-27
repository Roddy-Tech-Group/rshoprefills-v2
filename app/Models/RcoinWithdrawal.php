<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RcoinWithdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'rcoin_amount',
        'usd_value',
        'fee_usd',
        'payout_usd',
        'method',
        'payout_details',
        'rate_snapshot',
        'status',
        'reject_reason',
        'reviewed_by',
        'reviewed_at',
        'paid_at',
        'payout_reference',
        'debit_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'rcoin_amount' => 'integer',
            'usd_value' => 'decimal:4',
            'fee_usd' => 'decimal:4',
            'payout_usd' => 'decimal:4',
            'rate_snapshot' => 'decimal:8',
            'payout_details' => 'array',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function debitTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'debit_transaction_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
