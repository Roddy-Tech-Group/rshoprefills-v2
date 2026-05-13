<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Audit trail for incoming payment webhooks.
 *
 * @property int $id
 * @property string $gateway
 * @property string $event_type
 * @property string|null $reference
 * @property array $payload
 * @property string|null $signature
 * @property bool $processed
 * @property Carbon|null $processed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PaymentWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway',
        'event_type',
        'reference',
        'payload',
        'signature',
        'processed',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }
}
