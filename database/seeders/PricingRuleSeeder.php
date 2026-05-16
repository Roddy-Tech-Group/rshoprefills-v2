<?php

namespace Database\Seeders;

use App\Models\PricingRule;
use Illuminate\Database\Seeder;

class PricingRuleSeeder extends Seeder
{
    /*
     * Seeds the global platform markup — a pricing rule with product_id,
     * subcategory_id and category_id all null, so it applies to every product
     * that has no more specific rule.
     *
     * Only creates the row when no global rule exists, so re-running never
     * clobbers a value the admin has since changed.
     *
     * markup_value below (8%) is a launch placeholder — the real figure is a
     * business decision and should be set/confirmed by the CTO via admin.
     */
    public function run(): void
    {
        $globalRuleExists = PricingRule::whereNull('product_id')
            ->whereNull('subcategory_id')
            ->whereNull('category_id')
            ->exists();

        if ($globalRuleExists) {
            return;
        }

        PricingRule::create([
            'category_id' => null,
            'subcategory_id' => null,
            'product_id' => null,
            'markup_type' => 'percentage',
            'markup_value' => 8,
            'is_active' => true,
        ]);
    }
}
