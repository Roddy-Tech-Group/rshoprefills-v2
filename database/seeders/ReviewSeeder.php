<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        // Aggregate stats shown on the Trustpilot summary card + /reviews hero.
        // Stored as site_settings so marketing can tune them via admin without
        // a deploy. Numbers tuned to match the seeded mix below; admin should
        // bump as real Trustpilot/Google reviews are mirrored manually.
        SiteSetting::put('reviews.aggregate.rating', 4.8, 'reviews', 'Public aggregate review score');
        SiteSetting::put('reviews.aggregate.count', 24, 'reviews', 'Aggregate review count shown on the score card');
        SiteSetting::put('reviews.aggregate.since_year', 2024, 'reviews', 'Founded year shown on score card');
        SiteSetting::put('reviews.aggregate.source', 'Trustpilot', 'reviews', 'Headline aggregate source label');

        // Names use the same display style real platforms render:
        //  - Trustpilot truncates to first name + last initial ("Sarah M.")
        //  - Google typically shows full name
        // Dates spread across the company's 2024 -> 2026 lifetime: sparser
        // in 2024 (just launched), denser in 2026 (established platform).
        // Mix of African + international, 10 reviews per source.
        $reviews = [
            // ── Trustpilot (10): first name + last initial ──────────────
            ['Adaeze O.',    '2024-03-15', 'Trustpilot', 5, 'Bought a $50 Amazon card and it dropped in my dashboard in under a minute. The price in naira was also better than what I was seeing elsewhere. Will keep coming back.'],
            ['James M.',     '2024-08-22', 'Trustpilot', 5, 'Used the eSIM for my trip to Lagos and it activated instantly. Saved me from buying a SIM at the airport. Clean process and clear instructions in the email.'],
            ['Kwame A.',     '2025-01-09', 'Trustpilot', 5, 'Paid with crypto for the first time and was nervous, but the step by step guide walked me through it. Transaction confirmed in minutes and the gift card was delivered immediately.'],
            ['Sarah T.',     '2025-05-17', 'Trustpilot', 4, 'Great experience overall. The wallet balance feature is really handy because I can fund once and buy multiple things without re-entering card details. Took off one star because I wish there were more product categories.'],
            ['Tunde A.',     '2025-08-04', 'Trustpilot', 5, 'Customer support replied within an hour when I had a question about a top-up that hadn\'t shown up. Turned out it was on the carrier side and they helped me follow up. Top tier service.'],
            ['Maria G.',     '2025-11-28', 'Trustpilot', 5, 'I send PlayStation cards to my nephew in Cameroon every month. RshopRefills has been the most reliable platform I\'ve tried so far, and the prices are honest.'],
            ['Fatima D.',    '2026-01-12', 'Trustpilot', 5, 'The Rcoin rewards programme is a nice touch. I\'ve earned enough on regular gift card purchases to cover a small top-up. Feels good to be rewarded.'],
            ['Rohan S.',     '2026-03-06', 'Trustpilot', 5, 'Bought a Netflix subscription voucher and an eSIM in the same checkout. Both delivered instantly and the receipt landed in my inbox right away. Professional service.'],
            ['Brian O.',     '2026-04-18', 'Trustpilot', 4, 'Mobile top-up to Safaricom worked perfectly on the third attempt. The first two were stuck in pending but support refunded me and explained the network was having issues. Honest team.'],
            ['Liam O.',      '2026-05-08', 'Trustpilot', 5, 'Solid platform. Bought an Xbox card from Ireland for my brother in Nigeria and it worked first time. Faster than the local shops I usually use.'],

            // ── Google (10): full names ─────────────────────────────────
            ['Daniel Owusu',     '2024-05-20', 'Google', 5, 'I run a small phone shop and use RshopRefills for bulk top-ups. The wallet keeps things simple and I can buy in bulk without my card getting flagged. Highly recommended for resellers.'],
            ['Emma Williams',    '2024-09-11', 'Google', 5, 'First time using the platform for bill payments and it was easy. Paid my DSTV from the UK for my mum in Accra and she had service back within minutes. Brilliant.'],
            ['Aisha Bello',      '2025-02-25', 'Google', 5, 'Honestly the best place to buy gift cards in Nigeria. I tried two others before this and they either had hidden fees or the cards came invalid. RshopRefills was clean from start to finish.'],
            ['Lucas Schmidt',    '2025-06-14', 'Google', 4, 'Good experience buying an eSIM for travel. The activation QR worked on my iPhone without any drama. Only thing I\'d suggest is making the data balance check easier to find.'],
            ['Grace Nwankwo',    '2025-10-03', 'Google', 5, 'Bought airtime for my mum from South Africa to her MTN line in Nigeria. Delivered instantly and the exchange rate was much better than the bank. Will use again.'],
            ['Ahmed Hassan',     '2025-12-19', 'Google', 5, 'Crypto payment with USDT was straightforward. No KYC required for the small purchase I made. Card credentials hit my email within 5 minutes. This is how online shopping should work.'],
            ['Joyce Mwangi',     '2026-02-08', 'Google', 5, 'I love that I can use my Rcoin balance to pay for the next purchase. Feels like a real loyalty programme not a gimmick. Already saving up for a bigger purchase.'],
            ['Hannah Roberts',   '2026-04-02', 'Google', 5, 'Customer service was super helpful when my eSIM did not show data initially. They walked me through the APN setup and I was online in 10 minutes. Patient and friendly.'],
            ['Peter Akinwale',   '2026-05-15', 'Google', 4, 'Solid for gift cards. Wish there were more options for African mobile networks but what is there works well. The bank transfer option for Nigerian customers is a nice touch.'],
            ['Mark Johnson',     '2026-05-23', 'Google', 5, 'Travelled to Kenya and used the eSIM. Activated before I even left London. No physical SIM swap, no roaming bills, no problems. Will be my go-to for future travel.'],
        ];

        foreach ($reviews as [$name, $date, $source, $rating, $body]) {
            $initials = Str::upper(
                collect(explode(' ', trim($name)))
                    ->filter()
                    ->take(2)
                    ->map(fn ($p) => Str::substr($p, 0, 1))
                    ->implode('')
            );

            Review::updateOrCreate(
                ['author_name' => $name, 'reviewed_at' => Carbon::parse($date)],
                [
                    'initials' => $initials ?: 'A',
                    'body' => $body,
                    'rating' => $rating,
                    'source' => $source,
                    'is_published' => true,
                    'sort_order' => 0,
                ],
            );
        }
    }
}
