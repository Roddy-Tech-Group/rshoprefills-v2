<?php

namespace Database\Seeders;

use App\Models\PressArticle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PressArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'slug' => 'rcoin-rewards-program',
                'category' => 'News',
                'title' => 'RshopRefills launches the Rcoin rewards program',
                'excerpt' => 'Earn points on every purchase and redeem them for wallet credit across the platform.',
                'image' => 'best prices.svg',
                'published_at' => '2026-05-20',
                'body' => [
                    'RshopRefills has launched Rcoin, a rewards currency that customers earn automatically on every completed order.',
                    'Rcoin can be redeemed for wallet credit and spent on any service on the platform, from gift cards and eSIMs to top-ups, bills and travel. Customers can track their balance and history on their Rewards page.',
                    'The program is part of our mission to give customers more value every time they shop, with no extra steps required.',
                ],
            ],
            [
                'slug' => 'instant-esim-activation',
                'category' => 'Implementation',
                'title' => 'Instant eSIM activation now available worldwide',
                'excerpt' => 'Travelers can buy and activate an eSIM in minutes and stay connected anywhere.',
                'image' => 'Esim stay connectd.webp',
                'published_at' => '2026-04-15',
                'body' => [
                    'RshopRefills now offers instant eSIM activation, so travelers can get connected the moment they arrive, without hunting for a local SIM card.',
                    'After purchase, the eSIM QR code and activation details appear in the order and by email. Customers scan the code in their phone settings to install and activate the plan in minutes.',
                ],
            ],
            [
                'slug' => 'expanding-across-africa',
                'category' => 'News',
                'title' => 'RshopRefills expands access across Africa',
                'excerpt' => 'More countries, more local payment methods and more brands for customers across the continent.',
                'image' => 'World Global.webp',
                'published_at' => '2026-03-10',
                'body' => [
                    'RshopRefills has expanded its coverage across Africa, bringing more global brands and essential services to more customers.',
                    'The expansion adds localized payment methods, including mobile money and bank transfers, alongside cards and crypto, so customers can pay in the way most convenient to them.',
                ],
            ],
            [
                'slug' => 'one-wallet-for-everything',
                'category' => 'Implementation',
                'title' => 'Gift cards, top-ups and bills, all in one wallet',
                'excerpt' => 'A single in-app wallet now powers purchases across every category we offer.',
                'image' => 'graphic_rshoprefill.webp',
                'published_at' => '2026-02-18',
                'body' => [
                    'Customers can now hold balance in an in-app wallet and use it to pay across every category on RshopRefills.',
                    'The wallet supports multiple currencies and is protected by an optional transaction PIN, giving customers a fast, secure way to check out without re-entering payment details each time.',
                ],
            ],
            [
                'slug' => 'pay-your-way',
                'category' => 'News',
                'title' => 'Pay your way: card, bank transfer, mobile money and crypto',
                'excerpt' => 'Customers can settle purchases using the method most convenient to them, including crypto.',
                'image' => 'secure fast reliable.svg',
                'published_at' => '2026-01-22',
                'body' => [
                    'RshopRefills now lets customers pay with cards, bank transfers, mobile money, crypto or their wallet balance, all from one checkout.',
                    'Every payment is protected by automated fraud-prevention systems, and card details are handled by regulated payment gateways, so full card numbers are never stored on our servers.',
                ],
            ],
            [
                'slug' => 'sixty-second-refund-protection',
                'category' => 'Implementation',
                'title' => 'Automatic refund protection delivers in 60 seconds',
                'excerpt' => 'If a delivery fails, our system detects it and refunds the wallet automatically, usually within a minute.',
                'image' => 'instant delivery.webp',
                'published_at' => '2025-12-05',
                'body' => [
                    'RshopRefills has built failed-transaction protection directly into the platform.',
                    'If a payment is taken but the item cannot be delivered because of a network timeout, the system detects the failure and issues an instant refund to the customer wallet, usually within 60 seconds, with no need to contact support.',
                ],
            ],
        ];

        foreach ($articles as $article) {
            PressArticle::updateOrCreate(
                ['slug' => $article['slug']],
                array_merge($article, [
                    'is_published' => true,
                    'sort_order' => 0,
                    'published_at' => Carbon::parse($article['published_at']),
                ]),
            );
        }
    }
}
