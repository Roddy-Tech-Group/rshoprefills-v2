<?php

use App\Domain\Cart\Services\CartManager;
use App\Domain\Cart\Services\CartPricingService;
use App\Http\Controllers\CartWebController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ThemeController;
use App\Models\CurrencyRate;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Storefront catalog — accessible to guests AND authed users.
// The dashboard sidebar/menu links to these same URLs (no duplicate dashboard.gift-cards page).
Route::view('gift-cards', 'shop.gift-cards')->name('shop.gift-cards');

// Live-search JSON endpoint used by the nav search bar. Returns up to 8 brand matches
// grouped by brand_key (so "apple" returns "Everything Apple" once, not 8 country rows).
Route::get('api/search/brands', function (Request $request) {
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
        ->select('brand_key', DB::raw('MIN(id) as id'))
        ->groupBy('brand_key')
        ->limit(8)
        ->pluck('id');

    $products = Product::query()
        ->whereIn('id', $brandIds)
        ->get(['id', 'brand_key', 'country_code', 'logo_url', 'name']);

    return response()->json($products->map(fn ($p) => [
        'name' => Product::brandDisplayName($p->brand_key),
        'slug' => Product::brandSlug($p->brand_key),
        'logo' => Product::brandLogoUrl($p->brand_key, $p->logo_url),
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
        ->first(fn ($key) => Str::kebab($key) === $brandSlug);

    abort_if(! $brandKey, 404);

    // Region-locked: the country is the resolved region (ResolveRegion middleware),
    // so a brand page always opens in the customer's locked country. Falls back to
    // whichever country this brand is actually sold in if there's no stock there.
    $requested = strtoupper((string) (request()->attributes->get('region') ?: 'US'));

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

// eSIM storefront — a coverage-region listing + a per-region data-plan detail page.
// Mirrors the gift-cards pattern: a Route::view listing + a slug detail route.
// Each Product in the `esims` category is a coverage region; each variant is a data plan.
Route::view('esims', 'shop.esims')->name('shop.esims');

Route::get('esims/{slug}', function (string $slug) {
    $product = Product::query()
        ->where('slug', $slug)
        ->where('is_active', true)
        ->whereHas('category', fn ($q) => $q->where('slug', 'esims'))
        ->with([
            'category:id,name,slug',
            'subcategory:id,name,slug',
            'variants' => fn ($q) => $q->where('is_available', true)->orderBy('cost_price'),
        ])
        ->firstOrFail();

    return view('shop.esim', ['product' => $product]);
})->name('shop.esim');

// Mobile top-up — mirrors the gift-card flow exactly: a brand listing + a
// brand-level detail page. Operators are the Products in the `mobile-airtime`
// category; their variants are the airtime amounts. The detail page reuses the
// shared `shop.product` view.
Route::view('topups', 'shop.topups')->name('shop.topups');

Route::get('topups/{brandSlug}', function (string $brandSlug) {
    $brandSlug = strtolower($brandSlug);

    // Resolve the kebab-cased URL slug back to the actual brand_key, scoped to
    // the mobile-airtime category so it never collides with a gift-card brand.
    $brandKey = Product::query()
        ->whereNotNull('brand_key')
        ->where('is_active', true)
        ->whereHas('category', fn ($q) => $q->where('slug', 'mobile-airtime'))
        ->distinct()
        ->pluck('brand_key')
        ->first(fn ($key) => Str::kebab($key) === $brandSlug);

    abort_if(! $brandKey, 404);

    $requested = strtoupper((string) (request()->attributes->get('region') ?: 'US'));

    $base = fn () => Product::query()
        ->where('brand_key', $brandKey)
        ->where('is_active', true)
        ->whereHas('category', fn ($q) => $q->where('slug', 'mobile-airtime'))
        ->with([
            'subcategory:id,name,slug',
            'category:id,name,slug',
            'variants' => fn ($q) => $q->where('is_available', true)->orderBy('face_value'),
        ]);

    // Region-locked, with a fallback to any country this operator is sold in.
    $product = $base()->where('country_code', $requested)->first()
        ?: $base()->orderByRaw("country_code = 'US' DESC")->first();

    abort_if(! $product, 404);

    return view('shop.product', ['product' => $product, 'brandKey' => $brandKey]);
})->name('shop.topup');

// Bill payments — prepaid utilities (electricity, water, etc.). These ride
// Zendit's /vouchers/offers feed and are split into the `bill-payments`
// category by ZenditNormalizer. Mirrors the gift-card flow: a brand listing +
// a brand-level detail page reusing the shared `shop.product` view.
Route::view('bills', 'shop.bills')->name('shop.bills');

Route::get('bills/{brandSlug}', function (string $brandSlug) {
    $brandSlug = strtolower($brandSlug);

    $brandKey = Product::query()
        ->whereNotNull('brand_key')
        ->where('is_active', true)
        ->whereHas('category', fn ($q) => $q->where('slug', 'bill-payments'))
        ->distinct()
        ->pluck('brand_key')
        ->first(fn ($key) => Str::kebab($key) === $brandSlug);

    abort_if(! $brandKey, 404);

    $requested = strtoupper((string) (request()->attributes->get('region') ?: 'US'));

    $base = fn () => Product::query()
        ->where('brand_key', $brandKey)
        ->where('is_active', true)
        ->whereHas('category', fn ($q) => $q->where('slug', 'bill-payments'))
        ->with([
            'subcategory:id,name,slug',
            'category:id,name,slug',
            'variants' => fn ($q) => $q->where('is_available', true)->orderBy('face_value'),
        ]);

    $product = $base()->where('country_code', $requested)->first()
        ?: $base()->orderByRaw("country_code = 'US' DESC")->first();

    abort_if(! $product, 404);

    return view('shop.product', ['product' => $product, 'brandKey' => $brandKey]);
})->name('shop.bill');

// Flights & Stays — branded "coming soon" pages until the booking catalog ships.
// One shared view, the `service` data flag drives the per-service copy + art.
Route::view('flights', 'shop.coming-soon', ['service' => 'flights'])->name('shop.flights');
Route::view('stays', 'shop.coming-soon', ['service' => 'stays'])->name('shop.stays');

// Cart page (HTML). Store-driven — it hydrates from the /cart/data JSON endpoint.
Route::get('cart', [CartWebController::class, 'page'])->name('shop.cart');

// Storefront cart — web routes (session-authenticated) wrapping the backend CartManager.
// Returns compact JSON the Alpine cart store consumes. See CartWebController.
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('data', [CartWebController::class, 'show'])->name('data');
    Route::post('items', [CartWebController::class, 'add'])->name('items.add');
    Route::patch('items/{item}', [CartWebController::class, 'update'])->name('items.update');
    Route::delete('items/{item}', [CartWebController::class, 'remove'])->name('items.remove');
});

// Checkout. Resolves the active cart (CartManager — same path the global CartComposer uses)
// and renders the checkout page. Order creation + payment-gateway initiation are backend
// territory; the POST below is a validation-only stub the backend replaces with a
// CheckoutController that builds the Order/OrderItem rows and kicks off the gateway.
Route::get('checkout', function (CartManager $cartManager, CartPricingService $pricing) {
    $userId = auth()->id();
    $guestToken = request()->cookie('guest_token') ?? request()->header('X-Guest-Token');

    $cart = null;
    $totals = ['currency' => 'USD', 'subtotal' => 0, 'total' => 0];

    if ($userId || $guestToken) {
        try {
            $cart = $cartManager->resolveCart($userId, $guestToken);
            $cart->load('items.product', 'items.variant');
            $totals = $pricing->calculateCartTotals($cart->items);
        } catch (Throwable $e) {
            $cart = null;
        }
    }

    // Display currency: the customer's chosen currency, passed through as ?currency=
    // (the cart popup's Checkout link appends it). The view converts the USD totals
    // with this rate; falls back to USD when absent.
    $rate = CurrencyRate::resolve(request('currency'));

    return view('shop.checkout', ['cart' => $cart, 'totals' => $totals, 'rate' => $rate]);
})->name('shop.checkout');

// Checkout submission — creates the Order + OrderItems + a pending Payment from the
// cart. Gateway hand-off (Flutterwave / NowPayments / wallet debit) is the TODO inside
// the controller. Requires auth: an Order needs a user_id.
Route::post('checkout', [CheckoutController::class, 'process'])
    ->middleware('auth')
    ->name('checkout.process');

// Order confirmation page.
Route::get('order/{orderNumber}', [CheckoutController::class, 'order'])
    ->middleware('auth')
    ->name('shop.order');

// Customer dashboard — gated by web guard. Admin operators have their own area at /admin/* via routes/admin.php.
// NOTE: 'verified' middleware intentionally NOT applied — verification stays a SOFT requirement so users
// can use the app immediately after registration. The profile page surfaces the verification status badge
// and the sidebar has a Verify Identity entry, so users have multiple paths to verify when they choose.
// Re-add 'verified' here only if/when CTO wants to hard-gate the dashboard behind a confirmed email.
Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::view('dashboard/rewards', 'dashboard.rewards')->name('dashboard.rewards');

    Route::view('dashboard/kyc', 'dashboard.kyc')->name('dashboard.kyc');

    Volt::route('dashboard/orders', 'dashboard.orders')->name('dashboard.orders');

    Volt::route('dashboard/transactions', 'dashboard.transactions')->name('dashboard.transactions');

    Volt::route('dashboard/notifications', 'dashboard.notifications')->name('dashboard.notifications');

    Route::view('dashboard/saved-cards', 'dashboard.saved-cards')->name('dashboard.saved-cards');

    Route::view('dashboard/wallet', 'dashboard.wallet')->name('dashboard.wallet');

    Volt::route('dashboard/profile', 'settings.profile')->name('dashboard.profile');
    Volt::route('dashboard/password', 'settings.password')->name('dashboard.password');
    Volt::route('dashboard/appearance', 'settings.appearance')->name('dashboard.appearance');

    // Persists the customer's light/dark/system preference (theme engine posts here).
    Route::post('preferences/theme', [ThemeController::class, 'update'])->name('preferences.theme');
});

// Legacy /settings/* URLs redirect to the new /dashboard/* paths so old bookmarks keep working.
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'dashboard/profile');
    Route::redirect('settings/profile', 'dashboard/profile');
    Route::redirect('settings/password', 'dashboard/password');
    Route::redirect('settings/appearance', 'dashboard/appearance');
});

require __DIR__.'/auth.php';
