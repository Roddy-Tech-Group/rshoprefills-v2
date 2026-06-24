<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TradeMedia extends Model
{
    protected $guarded = [];

    protected $appends = ['url'];

    public function trade(): BelongsTo
    {
        return $this->belongsTo(GiftCardTrade::class, 'trade_id');
    }

    public function getUrlAttribute(): string
    {
        if (empty($this->file_path)) {
            return '';
        }

        // Assuming s3 or local disk based on your current setup
        return Storage::disk('public')->url($this->file_path);
    }
}
