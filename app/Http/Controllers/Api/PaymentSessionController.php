<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentSession;
use App\Http\Resources\PaymentSessionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentSessionController extends Controller
{
    /**
     * Display the specified payment session details.
     */
    public function show(string $id)
    {
        $session = PaymentSession::findOrFail($id);

        return new PaymentSessionResource($session);
    }

    /**
     * Retrieve the current raw status of the payment session (optimized for fast polling).
     */
    public function status(string $id)
    {
        $session = PaymentSession::select('id', 'status', 'expires_at', 'confirmed_at', 'failed_at')->findOrFail($id);

        return response()->json([
            'id' => $session->id,
            'status' => $session->status,
            'is_expired' => $session->expires_at ? $session->expires_at->isPast() : false,
            'confirmed_at' => $session->confirmed_at?->toIso8601String(),
            'failed_at' => $session->failed_at?->toIso8601String(),
        ]);
    }

    /**
     * Cancel the specified active payment session.
     */
    public function cancel(string $id)
    {
        $session = PaymentSession::findOrFail($id);

        try {
            DB::transaction(function () use ($session) {
                // Check if session can be cancelled
                if (!in_array($session->status, ['pending', 'awaiting_payment'])) {
                    throw new \DomainException("Cannot cancel payment session that is already {$session->status}.");
                }

                $session->transitionTo('cancelled');

                // Cancel polymorphic payment attempt
                $attempt = $session->paymentAttempt;
                if ($attempt && $attempt->payment_status !== \App\Domain\Payment\Enums\PaymentStatus::Paid) {
                    $attempt->update([
                        'payment_status' => \App\Domain\Payment\Enums\PaymentStatus::Failed,
                        'failed_at' => now(),
                    ]);
                }
            });

            return response()->json([
                'message' => 'Payment session cancelled successfully.',
                'status' => 'cancelled',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
