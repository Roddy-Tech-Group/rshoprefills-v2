<?php

namespace App\Http\Controllers;

use App\Domain\Cart\Services\CartPricingService;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Storefront eSIM pages. A country is sold by more than one supplier, each of which
 * syncs its own Product per country. The store merges them: a country page shows
 * every supplier's available plans together (data + voice) under one roof. Regional
 * and global eSIMs (no real 2-letter country code) stay per-product so distinct
 * regions are never collapsed into each other.
 */
class EsimStoreController extends Controller
{
    /** The /esims entry point: a lean landing — hero + browse-by-location grid.
     *  ?scope=popular|local|regional|global|all selects the active category. */
    public function index(Request $request)
    {
        $allowed = ['popular', 'local', 'regional', 'global', 'all'];
        $scope = strtolower((string) $request->query('scope', 'popular'));
        if (! in_array($scope, $allowed, true)) {
            $scope = 'popular';
        }

        return view('shop.esims', ['catalog' => self::catalogSummary(), 'activeScope' => $scope]);
    }

    /**
     * Deduped, cached eSIM catalog summary for the location grids: one entry per
     * country (cheapest "from" price across all suppliers), regional/global kept
     * per-product. Shared by the landing page and the country store.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function catalogSummary(): Collection
    {
        // Popular destinations, mirroring Airalo's "Popular" set for this market.
        $popularIso = ['CM', 'FR', 'GB', 'CG', 'US', 'MA', 'CF', 'EG', 'IN', 'CI', 'CD', 'SN', 'GH', 'GA', 'KE', 'AZ', 'AU', 'ZA', 'TR', 'TH'];

        return Cache::remember('esim-catalog-summary-v5', now()->addHour(), function () use ($popularIso) {
            $pricing = app(CartPricingService::class);
            $cat = Category::where('slug', 'esims')->first();

            return Product::query()
                ->where('is_active', true)
                ->when($cat, fn ($q) => $q->where('category_id', $cat->id))
                ->with(['variants' => fn ($q) => $q->where('is_available', true)])
                ->orderBy('name')
                ->get()
                ->groupBy(function ($p) {
                    $cc = strtoupper((string) $p->country_code);

                    return (strlen($cc) === 2 && $cc !== 'WW') ? $cc : 'slug:'.$p->slug;
                })
                ->map(function ($group) use ($pricing, $popularIso) {
                    $rep = $group->first();
                    $meta = self::regionMeta($rep->country_code, $rep->slug, $rep->name);
                    $hasFlag = strlen($meta['cc']) === 2 && $meta['cc'] !== 'WW';

                    $cheapest = $group
                        ->flatMap(fn ($p) => $p->variants->each(fn ($v) => $v->setRelation('product', $p)))
                        ->sortBy('cost_price')
                        ->first();
                    if (! $cheapest) {
                        return null;
                    }
                    $from = round((float) $pricing->calculatePricing($cheapest, 1)['unit_price_snapshot'], 2);

                    return [
                        'slug' => $rep->slug,
                        'name' => $meta['name'],
                        'flag' => $hasFlag ? Product::flagUrl($meta['cc']) : self::globalFlag(),
                        'from' => $from,
                        'scope' => $meta['scope'],
                        'popular' => $hasFlag && in_array($meta['cc'], $popularIso, true),
                    ];
                })
                ->filter()
                ->sortBy('name')
                ->values();
        });
    }

    /**
     * Clean display name + scope (local / regional / global) for an eSIM product.
     * Suppliers label every region "Global eSIM", so for non-country products we
     * derive the real region from the slug (esim-ww-europe -> "Europe").
     *
     * @return array{cc: string, scope: string, name: string}
     */
    public static function regionMeta(?string $countryCode, string $slug, ?string $name = null): array
    {
        $cc = strtoupper(trim((string) $countryCode));

        // Real ISO country -> local. Always show the canonical full country name
        // (suppliers are inconsistent: some use the ISO code, some abbreviations).
        // Fall back to the cleaned supplier name only when the ISO isn't in our map.
        if (strlen($cc) === 2 && $cc !== 'WW') {
            $full = array_flip(config('countries.codes', []))[$cc] ?? null;
            $clean = (string) str((string) $name)->replaceLast(' Data eSIM', '')->replaceLast(' eSIM', '')->trim();

            return ['cc' => $cc, 'scope' => 'local', 'name' => $full ?: ($clean !== '' ? $clean : $cc)];
        }

        // Non-country product: derive the real region from the slug.
        $label = (string) str($slug)
            ->replaceFirst('esim-ww-', '')
            ->replaceFirst('esim-', '')
            ->replace('-', ' ')
            ->trim()
            ->title();
        $label = str_replace([' And ', ' Of ', ' The '], [' and ', ' of ', ' the '], $label);
        if ($label === '' || strtolower($label) === 'esim') {
            $label = 'Global';
        }

        $lower = strtolower($label);
        if ($cc === '' || str($lower)->contains(['global', 'world', 'discover'])) {
            $scope = 'global';
        } elseif (str($lower)->contains(['africa', 'asia', 'europe', 'oceania', 'america', 'caribbean', 'middle east', 'balkan', 'gulf', 'nordic', 'union', 'mena', 'latin'])) {
            $scope = 'regional';
        } else {
            // A single named territory that isn't an ISO country (Azores, Scotland).
            $scope = 'local';
        }

        return ['cc' => $cc, 'scope' => $scope, 'name' => $label];
    }

    /** Flag image used for non-country (regional / global) eSIMs. */
    public static function globalFlag(): string
    {
        return asset('assets/'.rawurlencode('Global png 11.webp'));
    }

    /** A specific region by slug; still merges by country so every supplier shows. */
    public function show(string $slug)
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('slug', 'esims'))
            ->firstOrFail();

        return $this->renderCountry($product);
    }

    /** Stable entry point by ISO country code (e.g. /esims/country/US) — used for
     *  footer/nav links that shouldn't depend on sync-generated slugs. */
    public function country(string $code)
    {
        $cc = strtoupper($code);

        $product = Product::query()
            ->where('is_active', true)
            ->where('country_code', $cc)
            ->whereHas('category', fn ($q) => $q->where('slug', 'esims'))
            ->withCount(['variants' => fn ($q) => $q->where('is_available', true)])
            ->orderByDesc('variants_count')
            ->firstOrFail();

        return $this->renderCountry($product);
    }

    private function renderCountry(Product $product)
    {
        $cc = strtoupper((string) $product->country_code);

        if ($this->isLocalCountry($cc)) {
            $variants = $this->mergedCountryVariants($cc);
        } else {
            // Regional / global: keep this product's own plans only.
            $product->loadMissing(['variants' => fn ($q) => $q->where('is_available', true)]);
            $variants = $product->variants->each(fn ($v) => $v->setRelation('product', $product))->values();
        }

        return view('shop.esim', ['product' => $product, 'variants' => $variants]);
    }

    /** Every available variant for a country, across all suppliers, each carrying its own product. */
    private function mergedCountryVariants(string $country): EloquentCollection
    {
        $products = Product::query()
            ->where('is_active', true)
            ->where('country_code', $country)
            ->whereHas('category', fn ($q) => $q->where('slug', 'esims'))
            ->with(['variants' => fn ($q) => $q->where('is_available', true)->orderBy('cost_price')])
            ->get();

        $merged = $products->flatMap(
            fn (Product $p) => $p->variants->each(fn ($v) => $v->setRelation('product', $p))
        )->values();

        return new EloquentCollection($merged->all());
    }

    private function isLocalCountry(string $cc): bool
    {
        return strlen($cc) === 2 && $cc !== 'WW';
    }
}
