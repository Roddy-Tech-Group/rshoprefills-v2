<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A shared admin-dashboard notification (e.g. a new KYC submission). Visible to
 * every admin; read state is global.
 *
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string|null $message
 * @property string|null $url
 * @property array|null $data
 * @property Carbon|null $read_at
 */
class AdminNotification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'url',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }
}
