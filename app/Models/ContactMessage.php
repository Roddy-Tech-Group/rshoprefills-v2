<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A message submitted through the storefront Contact page.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string $email
 * @property string|null $subject
 * @property string|null $order_id
 * @property string $message
 * @property string $status "new" | "read" | "resolved"
 * @property string|null $ip_address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ContactMessage extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'order_id',
        'message',
        'status',
        'ip_address',
    ];

    /**
     * The signed-in customer who sent the message, if any.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
