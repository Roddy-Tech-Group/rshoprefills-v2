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

    /**
     * Attribute a referral to a newly registered user from a raw referral
     * code (typed on the signup form or carried in the CaptureReferralCookie).
     *
     * First-touch wins: firstOrCreate keeps any existing attribution for this
     * user. Silently no-ops when the code is blank, doesn't match a real
     * user's referral_code, or matches the referred user themselves (no
     * self-referrals). Returns the Referral row when one is attributed.
     */
    public static function attribute(User $referred, ?string $code): ?self
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        $referrer = User::query()
            ->where('referral_code', $code)
            ->where('id', '!=', $referred->id)
            ->first();

        if (! $referrer) {
            return null;
        }

        return static::firstOrCreate(
            ['referred_user_id' => $referred->id],
            [
                'referrer_id' => $referrer->id,
                'status' => 'active',
                'total_rewards_generated' => 0,
                'total_orders_completed' => 0,
            ],
        );
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
