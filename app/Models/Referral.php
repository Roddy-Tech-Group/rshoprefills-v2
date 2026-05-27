<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'status',
        'total_rewards_generated',
        'total_orders_completed',
        'last_rewarded_at',
    ];

    protected $casts = [
        'total_rewards_generated' => 'integer',
        'total_orders_completed' => 'integer',
        'last_rewarded_at' => 'datetime',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
