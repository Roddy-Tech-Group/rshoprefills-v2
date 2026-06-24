<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TradeMessage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function trade(): BelongsTo
    {
        return $this->belongsTo(GiftCardTrade::class, 'trade_id');
    }

    public function sender(): MorphTo
    {
        return $this->morphTo();
    }
}
