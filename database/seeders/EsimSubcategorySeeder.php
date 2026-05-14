<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class EsimSubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = Category::where('slug', 'esims')->first();

        if (! $category) {
            return;
        }

        $subcategories = [
            [
                'name' => 'Data eSIMs',
                'slug' => 'data-esim',
                'description' => 'Internet-only eSIMs for global travel and local use.',
                'icon' => 'lucide-globe',
                'sort_order' => 1,
            ],
            [
                'name' => 'Number eSIMs',
                'slug' => 'number-esim',
                'description' => 'eSIMs with a dedicated phone number for Voice & SMS.',
                'icon' => 'lucide-phone',
                'sort_order' => 2,
            ],
        ];

        foreach ($subcategories as $sub) {
            Subcategory::updateOrCreate(
                ['category_id' => $category->id, 'slug' => $sub['slug']],
                $sub
            );
        }
    }
}
