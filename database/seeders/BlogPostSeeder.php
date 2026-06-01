<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BlogPostSeeder extends Seeder
{
    /**
     * Idempotent: re-running this seeder upserts on the unique `slug` so it
     * is safe to call from CI, prod migrations, or a manual `db:seed --class`.
     *
     * The post list is intentionally inline here. It's the launch content
     * captured at one point in time — once the CMS goes live, the canonical
     * source moves to the database and this seeder stays as historical
     * record + fallback for fresh installs.
     */
    public function run(): void
    {
        $posts = [
            [
                'slug' => 'getting-started-your-first-purchase',
                'category' => 'Guides',
                'title' => 'Getting started: your first purchase on RshopRefills',
                'excerpt' => 'A quick walkthrough of creating an account, choosing a product and checking out in minutes.',
                'image' => 'hero gift.webp',
                'published_at' => '2026-05-18',
                'read_time' => '4 min read',
                'body' => [
                    'New to RshopRefills? Getting started takes just a few minutes. Create an account with your email and phone number, then browse our catalog of gift cards, eSIMs, top-ups, bills and travel.',
                    'When you find what you need, add it to your cart and head to checkout. Pick how you want to pay, with your wallet, card, bank transfer, mobile money or crypto, and confirm.',
                    'Your codes and details are delivered instantly to your dashboard and email. That is it, you are ready to shop the digital world.',
                ],
            ],
            [
                'slug' => 'buy-gift-cards-with-crypto',
                'category' => 'Crypto',
                'title' => 'How to buy a gift card with crypto in three steps',
                'excerpt' => 'Spend your crypto on everyday brands, quickly and securely.',
                'image' => 'best prices.svg',
                'published_at' => '2026-04-28',
                'read_time' => '3 min read',
                'body' => [
                    'Paying with crypto on RshopRefills is simple. First, choose your gift card and the amount you want.',
                    'At checkout, select crypto as your payment method and pick your coin. You will see the exact amount and address to send.',
                    'Once the network confirms your payment, your gift card is delivered instantly. Remember that the blockchain network charges a small fee to process the transaction, which is set by the network, not by us.',
                ],
            ],
            [
                'slug' => 'esim-vs-roaming',
                'category' => 'Travel',
                'title' => 'eSIM vs roaming: which is cheaper for travelers?',
                'excerpt' => 'Stay connected abroad without the shock of a roaming bill.',
                'image' => 'Esim stay connectd.webp',
                'published_at' => '2026-04-02',
                'read_time' => '5 min read',
                'body' => [
                    'Roaming with your home plan can be convenient, but it is often expensive and full of surprises. An eSIM gives you a local data plan at a clear, upfront price.',
                    'With RshopRefills, you buy an eSIM before you travel, then install it by scanning a QR code in your phone settings. You get connected the moment you land, with no physical SIM to swap.',
                    'For most travelers, an eSIM is the cheaper, simpler choice, especially for data-heavy trips.',
                ],
            ],
            [
                'slug' => 'keep-your-account-secure',
                'category' => 'Security',
                'title' => 'Five ways to keep your account and wallet secure',
                'excerpt' => 'Simple habits that protect your money and your data.',
                'image' => 'secured users.webp',
                'published_at' => '2026-03-14',
                'read_time' => '4 min read',
                'body' => [
                    'Your account security is a partnership. Here are five simple habits that go a long way.',
                    'Use a strong, unique password. Set a transaction PIN to authorize wallet payments. Keep your email secure, since it receives your codes. Double-check phone numbers and emails before you pay. And never share your password, PIN or codes with anyone.',
                    'We will never ask you for your password or PIN. If anyone does, it is not us.',
                ],
            ],
            [
                'slug' => 'what-is-a-transaction-pin',
                'category' => 'Product',
                'title' => 'What is a transaction PIN, and why you should set one',
                'excerpt' => 'An extra layer of protection for every wallet payment.',
                'image' => 'secure payments.svg',
                'published_at' => '2026-02-20',
                'read_time' => '3 min read',
                'body' => [
                    'A transaction PIN is a 4-digit code that authorizes payments from your wallet balance. It adds a second layer of protection on top of your password.',
                    'You can set or change your PIN under Security in your dashboard. For your safety, it locks after several wrong attempts and unlocks automatically after a short cooldown.',
                    'Setting a PIN means that even if someone reaches your account, they still cannot spend your wallet balance without it.',
                ],
            ],
            [
                'slug' => 'understanding-network-fees',
                'category' => 'Crypto',
                'title' => 'Understanding crypto network fees',
                'excerpt' => 'Why a small fee appears when you pay with crypto, and who sets it.',
                'image' => 'World Global.webp',
                'published_at' => '2026-01-16',
                'read_time' => '3 min read',
                'body' => [
                    'When you pay with crypto, you may notice a small network fee on top of your order amount. This is normal.',
                    'Network fees are charged by the blockchain itself to process and confirm your transaction. They are not charged by RshopRefills, and they can vary depending on how busy the network is at the time.',
                    'Choosing a less congested network or coin can sometimes reduce the fee. The amount is always shown before you confirm.',
                ],
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::updateOrCreate(
                ['slug' => $post['slug']],
                array_merge($post, [
                    'author' => 'RshopRefills Team',
                    'is_published' => true,
                    'sort_order' => 0,
                    'published_at' => Carbon::parse($post['published_at']),
                ]),
            );
        }
    }
}
