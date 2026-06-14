<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single customer review surfaced on the homepage carousel.
 *
 * @property int $id
 * @property string $initials
 * @property string $author_name
 * @property string $body
 * @property float $rating
 * @property string $source
 * @property Carbon $reviewed_at
 * @property bool $is_published
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'initials',
        'author_name',
        'body',
        'rating',
        'source',
        'reviewed_at',
        'is_published',
        'is_customer_submitted',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'date',
            'is_published' => 'boolean',
            'is_customer_submitted' => 'boolean',
            'rating' => 'float',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('sort_order')->orderByDesc('reviewed_at');
    }

    /** Customer submissions still awaiting admin approval. */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_customer_submitted', true)->where('is_published', false);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
