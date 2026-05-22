<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Contracts\View\View;

/**
 * Local-only previews of the transactional email templates so they can be
 * designed in the browser without triggering real events or sending mail.
 *
 * Each entry renders the same Blade view its Mailable uses, fed representative
 * sample data (the live Mailables pass these same variables from real models).
 * Routes are registered only in the local environment.
 */
class EmailPreviewController extends Controller
{
    /**
     * Template registry, keyed by URL slug.
     *
     * @return array<string, array{label: string, group: string, view: string, data: array<string, mixed>}>
     */
    private function templates(): array
    {
        return [
            'welcome' => [
                'label' => 'Welcome',
                'group' => 'Account',
                'view' => 'emails.welcome',
                'data' => [
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                    'isGoogleAuth' => false,
                ],
            ],
            'password-reset' => [
                'label' => 'Password reset',
                'group' => 'Account',
                'view' => 'emails.auth.password-reset',
                'data' => [
                    'name' => 'Jane Doe',
                    'resetUrl' => 'https://rshoprefills.test/reset-password/sample-token?email=jane@example.com',
                ],
            ],
            'order-placed' => [
                'label' => 'Order placed',
                'group' => 'Orders',
                'view' => 'emails.order.placed',
                'data' => [
                    'name' => 'Jane Doe',
                    'orderNumber' => 'RS-2026-0001',
                    'total' => 49.99,
                    'currency' => 'USD',
                    'itemsCount' => 2,
                ],
            ],
            'order-fulfilled' => [
                'label' => 'Order fulfilled',
                'group' => 'Orders',
                'view' => 'emails.order.fulfilled',
                'data' => [
                    'name' => 'Jane Doe',
                    'orderNumber' => 'RS-2026-0001',
                    'item' => new OrderItem([
                        'product_snapshot' => [
                            'brand_key' => 'amazon',
                            'name' => 'Amazon Gift Card',
                            'country_code' => 'US',
                            'redeem_instructions' => '<p>Go to amazon.com/redeem, sign in, and enter the code below. The balance is added to your Amazon account instantly.</p>',
                        ],
                        'variant_snapshot' => [
                            'face_value' => 25,
                            'currency' => 'USD',
                        ],
                        // Code-only card (the common case). Add a 'pin' key here to
                        // preview a card that also delivers a PIN — the Pin row only
                        // renders when a pin is present.
                        'fulfillment_payload' => [
                            'code' => 'AMZN-1234-5678-9012',
                        ],
                    ]),
                ],
            ],
            'order-refunded' => [
                'label' => 'Refund processed',
                'group' => 'Orders',
                'view' => 'emails.order.refunded',
                'data' => [
                    'name' => 'Jane Doe',
                    'orderNumber' => 'RS-2026-0001',
                    'amount' => 49.99,
                    'currency' => 'USD',
                    'reason' => 'The selected item was unavailable from the provider.',
                ],
            ],
            'wallet-funded' => [
                'label' => 'Wallet funded',
                'group' => 'Wallet',
                'view' => 'emails.wallet.funded',
                'data' => [
                    'name' => 'Jane Doe',
                    'amount' => 100.00,
                    'currency' => 'USD',
                    'reference' => 'TXN-2026-AB12CD',
                    'balanceAfter' => 250.00,
                ],
            ],
            'wallet-debited' => [
                'label' => 'Wallet debited',
                'group' => 'Wallet',
                'view' => 'emails.wallet.debited',
                'data' => [
                    'name' => 'Jane Doe',
                    'amount' => 49.99,
                    'currency' => 'USD',
                    'description' => 'Payment for order RS-2026-0001',
                    'reference' => 'TXN-2026-EF34GH',
                    'balanceAfter' => 200.01,
                ],
            ],
            'admin-new-order-alert' => [
                'label' => 'New order alert',
                'group' => 'Admin',
                'view' => 'emails.admin.new-order-alert',
                'data' => [
                    'orderNumber' => 'RS-2026-0001',
                    'customerName' => 'Jane Doe',
                    'customerEmail' => 'jane@example.com',
                    'totalAmount' => 1499.99,
                    'currency' => 'USD',
                    'isLargeTransaction' => true,
                ],
            ],
        ];
    }

    /**
     * The preview gallery: a sidebar list of every template + an iframe preview.
     */
    public function index(): View
    {
        $templates = collect($this->templates())
            ->map(fn (array $template, string $key): array => [
                'key' => $key,
                'label' => $template['label'],
                'group' => $template['group'],
            ])
            ->groupBy('group');

        return view('dev.email-previews', ['groups' => $templates]);
    }

    /**
     * Render a single template with its sample data (the email body itself).
     */
    public function show(string $key): View
    {
        $templates = $this->templates();

        abort_unless(isset($templates[$key]), 404);

        return view($templates[$key]['view'], $templates[$key]['data']);
    }
}
