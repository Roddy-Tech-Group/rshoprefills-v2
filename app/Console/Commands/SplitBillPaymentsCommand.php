<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Console\Command;

class SplitBillPaymentsCommand extends Command
{
    protected $signature = 'catalog:split-bill-payments';

    protected $description = 'Move prepaid-utility products (subType "Utilities") out of gift-cards into a dedicated bill-payments category';

    public function handle(): int
    {
        $billCategory = Category::firstOrCreate(
            ['slug' => 'bill-payments'],
            ['name' => 'Bill Payments', 'type' => 'digital']
        );

        $giftCards = Category::where('slug', 'gift-cards')->first();

        if (! $giftCards) {
            $this->warn('No gift-cards category found — nothing to split.');

            return self::SUCCESS;
        }

        // Prepaid utilities synced through /vouchers/offers landed under the
        // gift-cards "Utilities" subcategory. Reparent that subcategory, then
        // every product filed under it. Variant subcategory_id is unchanged —
        // it points at the same subcategory row, which simply moved category.
        $utilSub = Subcategory::where('category_id', $giftCards->id)
            ->where('slug', 'utilities')
            ->first();

        if (! $utilSub) {
            $this->info('No "Utilities" subcategory under gift-cards — already split, or none synced.');

            return self::SUCCESS;
        }

        $utilSub->update(['category_id' => $billCategory->id]);

        $moved = Product::where('subcategory_id', $utilSub->id)
            ->where('category_id', $giftCards->id)
            ->update(['category_id' => $billCategory->id]);

        $this->info("Moved {$moved} biller products into the Bill Payments category.");

        return self::SUCCESS;
    }
}
