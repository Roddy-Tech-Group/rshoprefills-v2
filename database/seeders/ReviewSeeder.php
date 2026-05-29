<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        // Aggregate stats shown on the Trustpilot summary card. Stored as
        // site_settings so marketing can tune the headline rating/count without
        // a deploy once the CMS UI lands.
        SiteSetting::put('reviews.aggregate.rating', 4.4, 'reviews', 'Public Trustpilot score');
        SiteSetting::put('reviews.aggregate.count', 446, 'reviews', 'Public Trustpilot review count');
        SiteSetting::put('reviews.aggregate.since_year', 2018, 'reviews', 'Founded year shown on Trustpilot card');
        SiteSetting::put('reviews.aggregate.source', 'Trustpilot', 'reviews', 'Aggregate source label');

        $reviews = [
            ['HG', 'Harshit Garg', '2026-05-12', 'This is the best after trying many i can say that\'s the best, like within 5 minutes u get the pin code voucher without any error.. very fast service... and this is my TOP number 1'],
            ['J',  'Jay',          '2026-05-12', 'I had some issues with my first purchase and the support team spent their valiant efforts on fixing these issues for me and I am truly grateful for the customer service.'],
            ['C',  'carlbooze',    '2026-05-10', 'It were fast, felt secure and safe, no issues at all. Very well guide to send the crypto with copy paste adress and amount to send so its impossible to mess up. Thank you very much'],
            ['M',  'Micheal',      '2026-05-02', 'Very easy to use and quick support'],
            ['GH', 'gfjg hgdh',    '2026-05-01', 'Always the best site to purchase gift card fastest and trusted'],
            ['ZA', 'Zul Aliffi',   '2026-04-28', 'I have tried many sites for local gift cards but this has been the smoothest delivery.'],
        ];

        foreach ($reviews as [$initials, $name, $date, $body]) {
            Review::updateOrCreate(
                ['author_name' => $name, 'reviewed_at' => Carbon::parse($date)],
                [
                    'initials' => $initials,
                    'body' => $body,
                    'rating' => 5,
                    'source' => 'Trustpilot',
                    'is_published' => true,
                    'sort_order' => 0,
                ],
            );
        }
    }
}
