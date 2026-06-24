<?php

namespace App\Models;

use App\Enums\TradeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class GiftCardTrade extends Model
{
    protected $guarded = [];

    protected $casts = [
        'declared_value' => 'decimal:2',
        'calculated_payout' => 'decimal:2',
        'status' => TradeStatus::class,
        'reviewed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rate(): BelongsTo
    {
        return $this->belongsTo(GiftCardRate::class, 'rate_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function media(): HasMany
    {
        return $this->hasMany(TradeMedia::class, 'trade_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TradeMessage::class, 'trade_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(TradeAuditLog::class, 'trade_id');
    }

    public function payout(): HasOne
    {
        return $this->hasOne(Payout::class, 'trade_id');
    }
}
