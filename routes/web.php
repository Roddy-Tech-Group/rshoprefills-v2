<?php

use App\Models\Product;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Storefront catalog — accessible to guests AND authed users.
// The dashboard sidebar/menu links to these same URLs (no duplicate dashboard.gift-cards page).
Route::view('gift-cards', 'shop.gift-cards')->name('shop.gift-cards');

// Live-search JSON endpoint used by the nav search bar. Returns up to 8 brand matches
// grouped by brand_key (so "apple" returns "Everything Apple" once, not 8 country rows).
Route::get('api/search/brands', function (\Illuminate\Http\Request $request) {
    $q = trim((string) $request->query('q', ''));
    if (mb_strlen($q) < 2) {
        return response()->json([]);
    }

    $brandIds = Product::query()
        ->where('is_active', true)
        ->whereNotNull('brand_key')
        ->where(function ($qq) use ($q) {
            $qq->where('name', 'like', "%{$q}%")
                ->orWhere('brand_key', 'like', "%{$q}%");
        })
        ->select('brand_key', \Illuminate\Support\Facades\DB::raw('MIN(id) as id'))
        ->groupBy('brand_key')
        ->limit(8)
        ->pluck('id');

    $products = Product::query()
        ->whereIn('id', $brandIds)
        ->get(['id', 'brand_key', 'country_code', 'logo_url', 'name']);

    return response()->json($products->map(fn ($p) => [
        'name'    => Product::brandDisplayName($p->brand_key),
        'slug'    => Product::brandSlug($p->brand_key),
        'logo'    => Product::brandLogoUrl($p->brand_key, $p->logo_url),
        'country' => $p->country_code,
    ])->values());
})->name('api.search.brands');

// Brand-level detail page. The URL slug is a kebab-cased brand_key
// ("apple" → brand_key "Apple", "mobile-legends" → "MobileLegends"). The page is
// locked to ONE country — whichever the user selected in the locale modal, passed
// through as `?country=XX`. If the brand isn't sold in that country the page 404s
// (the listing already only links to countries that have stock).
Route::get('gift-cards/{brandSlug}', function (string $brandSlug) {
    $brandSlug = strtolower($brandSlug);

    // Resolve the kebab-cased URL slug back to the actual brand_key. With ~692 unique
    // brands the in-memory match is cheap and avoids needing a new column.
    $brandKey = Product::query()
        ->whereNotNull('brand_key')
        ->where('is_active', true)
        ->distinct()
        ->pluck('brand_key')
        ->first(fn ($key) => \Illuminate\Support\Str::kebab($key) === $brandSlug);

    abort_if(! $brandKey, 404);

    // Country comes from the locale modal via query param. Default to US (the
    // largest catalog) if not set, else fall back to whichever country this brand
    // is actually sold in.
    $requested = strtoupper((string) request()->query('country', 'US'));

    $product = Product::query()
        ->where('brand_key', $brandKey)
        ->where('country_code', $requested)
        ->where('is_active', true)
        ->with([
            'subcategory:id,name,slug',
            'category:id,name,slug',
            'variants' => fn ($q) => $q->where('is_available', true)->orderBy('face_value'),
        ])
        ->first();

    // If the brand isn't sold in the requested country, fall back to ANY country it
    // IS sold in (prefer US, else the first available). Keeps the page from 404ing
    // when the user lands directly without a matching locale.
    if (! $product) {
        $product = Product::query()
            ->where('brand_key', $brandKey)
            ->where('is_active', true)
            ->orderByRaw("country_code = 'US' DESC")
            ->with([
                'subcategory:id,name,slug',
                'category:id,name,slug',
                'variants' => fn ($q) => $q->where('is_available', true)->orderBy('face_value'),
            ])
            ->first();
    }

    abort_if(! $product, 404);

    return view('shop.product', ['product' => $product, 'brandKey' => $brandKey]);
})->name('shop.brand');

// Customer dashboard — gated by web guard. Admin operators have their own area at /admin/* via routes/admin.php.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Volt::route('dashboard/profile', 'settings.profile')->name('dashboard.profile');
    Volt::route('dashboard/password', 'settings.password')->name('dashboard.password');
    Volt::route('dashboard/appearance', 'settings.appearance')->name('dashboard.appearance');
});

// Legacy /settings/* URLs redirect to the new /dashboard/* paths so old bookmarks keep working.
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'dashboard/profile');
    Route::redirect('settings/profile', 'dashboard/profile');
    Route::redirect('settings/password', 'dashboard/password');
    Route::redirect('settings/appearance', 'dashboard/appearance');
});

require __DIR__.'/auth.php';
