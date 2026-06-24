<?php

namespace App\Domain\GiftCardTrading\Services;

use App\Models\GiftCardTrade;
use App\Models\TradeMedia;
use Illuminate\Http\UploadedFile;

class FraudDetectionService
{
    /**
     * Hash the uploaded file to detect duplicates across the system.
     */
    public function generateFileHash(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }

    /**
     * Check if the given hash has been submitted before.
     */
    public function isDuplicateImage(string $hash): bool
    {
        return TradeMedia::where('file_hash', $hash)->exists();
    }

    /**
     * Check if user is exhibiting high velocity trading.
     */
    public function isHighVelocityUser(int $userId): bool
    {
        $recentTradesCount = GiftCardTrade::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return $recentTradesCount > 10; // Threshold can be moved to config
    }
}
