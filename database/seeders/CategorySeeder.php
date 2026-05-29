<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Gift Cards',
                'slug' => 'gift-cards',
                'type' => 'digital',
                'icon' => 'lucide-gift',
                'sort_order' => 1,
            ],
            [
                'name' => 'Mobile Airtime',
                'slug' => 'mobile-airtime',
                'type' => 'digital',
                'icon' => 'lucide-smartphone',
                'sort_order' => 2,
            ],
            [
                'name' => 'eSIMs',
                'slug' => 'esims',
                'type' => 'digital',
                'icon' => 'lucide-wifi',
                'sort_order' => 3,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
