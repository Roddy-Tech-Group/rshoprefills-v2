<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Customer-submitted reviews. A shopper leaves a review after a completed order;
 * it is stored unpublished + flagged as a customer submission so an admin
 * approves it before it appears on the storefront. One review per customer keeps
 * the wall honest and stops spam.
 */
class ReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'author_name' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'min:8', 'max:1000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        // One submission per account: if they already have a pending review,
        // update it; otherwise create. Already-approved reviews are left alone.
        $existing = Review::where('user_id', $user->id)
            ->where('is_customer_submitted', true)
            ->where('is_published', false)
            ->first();

        $name = trim($validated['author_name']);
        $initials = Str::of($name)->explode(' ')->filter()
            ->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))
            ->take(2)->implode('') ?: Str::upper(Str::substr($name, 0, 2));

        $payload = [
            'user_id' => $user->id,
            'author_name' => $name,
            'initials' => $initials,
            'body' => trim($validated['body']),
            'rating' => (int) $validated['rating'],
            'source' => 'RshopRefills',
            'reviewed_at' => now()->toDateString(),
            'is_published' => false,
            'is_customer_submitted' => true,
        ];

        if ($existing) {
            $existing->update($payload);
        } else {
            Review::create($payload);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Thank you! Your review will appear once our team approves it.',
        ]);
    }
}
