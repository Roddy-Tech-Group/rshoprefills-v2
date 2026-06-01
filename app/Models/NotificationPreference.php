<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 * @property bool $email_enabled
 * @property bool $marketing_enabled
 * @property bool $order_notifications
 * @property bool $wallet_notifications
 * @property bool $security_notifications
 */
class NotificationPreference extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'email_enabled',
        'marketing_enabled',
        'order_notifications',
        'wallet_notifications',
        'security_notifications',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'email_enabled' => 'boolean',
            'marketing_enabled' => 'boolean',
            'order_notifications' => 'boolean',
            'wallet_notifications' => 'boolean',
            'security_notifications' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
