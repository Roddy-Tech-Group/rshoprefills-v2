<?php

namespace App\Models;

use Database\Factories\BlogPostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A single blog article shown at /blog and /blog/{slug}.
 *
 * @property int $id
 * @property string $slug
 * @property string $category
 * @property string $title
 * @property string $excerpt
 * @property string $image
 * @property array<int, string> $body
 * @property string $author
 * @property string|null $read_time
 * @property Carbon $published_at
 * @property bool $is_published
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BlogPost extends Model
{
    /** @use HasFactory<BlogPostFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'category',
        'title',
        'excerpt',
        'image',
        'body',
        'author',
        'read_time',
        'published_at',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'array',
            'published_at' => 'date',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Default ordering for index pages: newest first, with `sort_order`
     * available as a manual override when an editor wants to pin a post.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('sort_order')->orderByDesc('published_at');
    }
}
