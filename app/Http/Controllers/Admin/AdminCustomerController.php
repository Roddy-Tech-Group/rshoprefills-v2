<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Notification\Enums\DeliveryStatus;
use App\Domain\Notification\Enums\NotificationChannel;
use App\Http\Controllers\Controller;
use App\Mail\AdminDirectMessageMail;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Admin actions on a customer account: edit profile, ban/unban, hold/release
 * wallet funds. Lives behind the `admin` middleware group.
 */
class AdminCustomerController extends Controller
{
    /**
     * Edit a customer's core profile fields.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'in:male,female,other'],
        ]);

        $user->update($validated);

        return back()->with('status', 'Customer details updated.');
    }

    /**
     * Ban or unban the customer. Banning blocks login + access via middleware.
     */
    public function toggleBan(User $user): RedirectResponse
    {
        $banned = $user->banned_at !== null;

        $user->update(['banned_at' => $banned ? null : now()]);

        return back()->with('status', $banned
            ? 'Customer unbanned.'
            : 'Customer banned. They have been blocked from signing in.');
    }

    /**
     * Hold or release the customer's funds by freezing every wallet. Frozen
     * wallets cannot be debited (guarded in WalletService::debit).
     */
    public function toggleFunds(User $user): RedirectResponse
    {
        // If any wallet is still active we are holding; otherwise releasing.
        $hold = $user->wallets()->where('is_active', true)->exists();

        $user->wallets()->update(['is_active' => ! $hold]);

        return back()->with('status', $hold
            ? 'Customer funds placed on hold.'
            : 'Customer funds released.');
    }

    /**
     * Send a direct message to the customer. The dashboard notification (database
     * row) is the source of truth and saves first; the email is best-effort and
     * its failure is logged but never propagated — a misconfigured mail driver
     * shouldn't 500 the admin's click. The type ('notification' or 'warning')
     * changes the tone + accent in both surfaces.
     */
    public function message(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:notification,warning'],
            'body' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $admin = $request->user('admin');
        $isWarning = $validated['type'] === 'warning';
        $title = $isWarning ? 'Account warning' : 'Message from RshopRefills';

        // 1. Dashboard notification — always lands. We write directly to the
        //    custom `notifications` table (App\Models\Notification) to match
        //    the project's DatabaseChannel pattern; Laravel's default Notifiable
        //    targets a different `data` JSON column that this table doesn't have.
        $dbNotification = Notification::create([
            'user_id' => $user->id,
            'type' => AdminDirectMessageMail::class,
            'title' => $title,
            'message' => $validated['body'],
            'channel' => NotificationChannel::Database,
            'status' => DeliveryStatus::Sent,
            'priority' => $isWarning ? 'high' : 'normal',
            'metadata' => [
                'kind' => $validated['type'],
                'admin_name' => $admin?->name,
            ],
            'sent_at' => now(),
        ]);

        NotificationDelivery::create([
            'notification_id' => $dbNotification->id,
            'provider' => 'database',
            'channel' => NotificationChannel::Database,
            'recipient' => (string) $user->id,
            'status' => DeliveryStatus::Sent,
            'response_payload' => ['notification_id' => $dbNotification->id],
            'attempted_at' => now(),
        ]);

        // 2. Email — best-effort. Sending the Mailable directly keeps a transport
        //    failure from rolling back the database row above.
        $emailDelivered = true;
        try {
            Mail::to($user->email)->send(new AdminDirectMessageMail(
                recipient: $user,
                type: $validated['type'],
                body: $validated['body'],
                adminName: $admin?->name,
            ));
        } catch (Throwable $e) {
            $emailDelivered = false;
            Log::warning('Admin direct message: email send failed', [
                'user_id' => $user->id,
                'type' => $validated['type'],
                'error' => $e->getMessage(),
            ]);
        }

        $status = $validated['type'] === 'warning'
            ? 'Warning sent to the customer.'
            : 'Message sent to the customer.';

        if (! $emailDelivered) {
            $status .= ' (Dashboard delivered; email could not be sent — check mail driver.)';
        }

        // Explicit redirect (not back()) so the browser's address bar ends up on
        // the customer detail page rather than the POST endpoint — a refresh
        // after submit would otherwise GET this URL and 405.
        return redirect()->route('admin.customer', $user)->with('status', $status);
    }
}
