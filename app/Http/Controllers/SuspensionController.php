<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SuspensionController extends Controller
{
    /**
     * Customer-initiated suspension review request. Suspended users see a
     * banner with a "Request review" button on their dashboard; submitting it
     * stamps `suspension_review_requested_at` so admins can see the queue,
     * and adds an entry to the shared admin notification feed.
     *
     * Idempotent: re-clicking does NOT spam the admin feed — we only insert
     * a new notification if there isn't already a pending request from this
     * user. The timestamp is refreshed each click so admins can see the most
     * recent ask.
     */
    public function requestReview(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user, 403);

        if (! $user->isSuspended()) {
            return back()->with('status', 'Your account is no longer suspended.');
        }

        $alreadyPending = $user->hasRequestedSuspensionReview();

        $user->update(['suspension_review_requested_at' => now()]);

        if (! $alreadyPending) {
            AdminNotification::create([
                'type' => 'suspension.review_requested',
                'title' => 'Suspension review requested',
                'message' => $user->name.' ('.$user->email.') has requested a review of their suspension.',
                'url' => route('admin.customer', $user),
                'data' => [
                    'user_id' => $user->id,
                    'requested_at' => now()->toIso8601String(),
                    'suspension_reason' => $user->suspension_reason,
                ],
            ]);
        }

        return back()->with('status', 'Your review request has been sent. We will get back to you shortly.');
    }
}
