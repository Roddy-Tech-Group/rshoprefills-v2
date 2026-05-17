<?php

namespace App\Models;

use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $notification_id
 * @property string $provider
 * @property NotificationChannel $channel
 * @property string $recipient
 * @property DeliveryStatus $status
 * @property array|null $response_payload
 * @property string|null $error_message
 * @property Carbon $attempted_at
 */
class NotificationDelivery extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'notification_id',
        'provider',
        'channel',
        'recipient',
        'status',
        'response_payload',
        'error_message',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => DeliveryStatus::class,
            'response_payload' => 'array',
            'attempted_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
