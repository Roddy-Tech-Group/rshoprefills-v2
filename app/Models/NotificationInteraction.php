<?php

namespace App\Models;

use App\Domain\Notification\Enums\InteractionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationInteraction extends Model
{
    use HasFactory;

    // The table has a single `interacted_at` timestamp and no created_at/updated_at.
    // Map Eloquent's created timestamp onto interacted_at and disable updated_at so
    // inserts populate it and never reference columns the table doesn't have.
    const UPDATED_AT = null;

    const CREATED_AT = 'interacted_at';

    protected $fillable = [
        'notification_id',
        'user_id',
        'campaign_id',
        'channel',
        'interaction_type',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'interaction_type' => InteractionType::class,
            'metadata' => 'array',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(NotificationCampaign::class);
    }
}
