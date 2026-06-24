<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TradeAuditLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function trade(): BelongsTo
    {
        return $this->belongsTo(GiftCardTrade::class, 'trade_id');
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
