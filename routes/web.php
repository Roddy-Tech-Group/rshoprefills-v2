<?php

use App\Domain\Cart\Services\CartManager;
use App\Domain\Cart\Services\CartPricingService;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartWebController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EmailPreviewController;
use App\Http\Controllers\EsimStoreController;
use App\Http\Controllers\EsimTopupController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\PressController;
use App\Http\Controllers\RcoinConvertController;
use App\Http\Controllers\RcoinWithdrawalController;
use App\Http\Controllers\SuspensionController;
use App\Http\Controllers\ThemeController;
use App\Models\CurrencyRate;
use App\Models\Product;
use App\Support\TaggedCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Storefront catalog - accessible to guests AND authed users.
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
// locked to ONE country - whichever the user selected in the locale modal, passed
// through as `?country=XX`. If the brand isn't sold in that country the page 404s
// (the listing already only links to countries that have stock).
Route::get('gift-cards/{brandSlug}', function (string $brandSlug) {
    $brandSlug = strtolower($brandSlug);

    $brandKey = TaggedCache::for(['catalog'])->remember("brand_slug_{$brandSlug}", 3600, function () use ($brandSlug) {
        return Product::query()
            ->whereNotNull('brand_key')
            ->where('is_active', true)
            ->distinct()
            ->pluck('brand_key')
            ->first(fn ($key) => Str::kebab($key) === $brandSlug);
    });

    abort_if(! $brandKey, 404);

    $requested = strtoupper((string) (request()->attributes->get('region') ?: 'US'));

    $product = TaggedCache::for(['catalog'])->remember("brand_product_{$brandKey}_{$requested}", 3600, function () use ($brandKey, $requested) {
        $p = Product::query()
            ->where('brand_key', $brandKey)
            ->where('country_code', $requested)
            ->where('is_active', true)
            ->with([
                'subcategory:id,name,slug',
                'category:id,name,slug',
                'variants' => fn ($q) => $q->where('is_available', true)->orderBy('face_value'),
            ])
            ->first();

        if (! $p) {
            $p = Product::query()
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

        return $p;
    });

    abort_if(! $product, 404);

    return view('shop.product', ['product' => $product, 'brandKey' => $brandKey]);
})->name('shop.brand');

// eSIM storefront - a single store page per country. Each Product in the `esims`
// category is one supplier's coverage for a region; the controller MERGES all
// suppliers' plans for a country so data + voice show together. The `esims` entry
// point resolves a default country; the slug route opens a specific region.
Route::get('esims', [EsimStoreController::class, 'index'])->name('shop.esims');
Route::get('esims/country/{code}', [EsimStoreController::class, 'country'])->name('shop.esim.country');
Route::get('esims/{slug}', [EsimStoreController::class, 'show'])->name('shop.esim');

// Mobile top-up - mirrors the gift-card flow exactly: a brand listing + a
// brand-level detail page. Operators are the Products in the `mobile-airtime`
// category; their variants are the airtime amounts. The detail page reuses the
// shared `shop.product` view.
Route::view('topups', 'shop.topups')->name('shop.topups');

Route::get('topups/{brandSlug}', function (string $brandSlug) {
    $brandSlug = strtolower($brandSlug);

    // Resolve the kebab-cased URL slug back to the actual brand_key, scoped to
    // the mobile-airtime category so it never collides with a gift-card brand.
    $brandKey = TaggedCache::for(['catalog'])->remember("topup_brand_{$brandSlug}", 3600, function () use ($brandSlug) {
        return Product::query()
            ->whereNotNull('brand_key')
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('slug', 'mobile-airtime'))
            ->distinct()
            ->pluck('brand_key')
            ->first(fn ($key) => Str::kebab($key) === $brandSlug);
    });

    abort_if(! $brandKey, 404);

    $requested = strtoupper((string) (request()->attributes->get('region') ?: 'US'));

    $product = TaggedCache::for(['catalog'])->remember("topup_product_{$brandKey}_{$requested}", 3600, function () use ($brandKey, $requested) {
        $base = fn () => Product::query()
            ->where('brand_key', $brandKey)
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('slug', 'mobile-airtime'))
            ->with([
                'subcategory:id,name,slug',
                'category:id,name,slug',
                'variants' => fn ($q) => $q->where('is_available', true)->orderBy('face_value'),
            ]);

        return $base()->where('country_code', $requested)->first()
            ?: $base()->orderByRaw("country_code = 'US' DESC")->first();
    });

    abort_if(! $product, 404);

    return view('shop.product', ['product' => $product, 'brandKey' => $brandKey]);
})->name('shop.topup');

// Bill payments - prepaid utilities (electricity, water, etc.). These ride
// Zendit's /vouchers/offers feed and are split into the `bill-payments`
// category by ZenditNormalizer. Mirrors the gift-card flow: a brand listing +
// a brand-level detail page reusing the shared `shop.product` view.
Route::view('bills', 'shop.bills')->name('shop.bills');

Route::get('bills/{brandSlug}', function (string $brandSlug) {
    $brandSlug = strtolower($brandSlug);

    $brandKey = TaggedCache::for(['catalog'])->remember("bill_brand_{$brandSlug}", 3600, function () use ($brandSlug) {
        return Product::query()
            ->whereNotNull('brand_key')
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('slug', 'bill-payments'))
            ->distinct()
            ->pluck('brand_key')
            ->first(fn ($key) => Str::kebab($key) === $brandSlug);
    });

    abort_if(! $brandKey, 404);

    $requested = strtoupper((string) (request()->attributes->get('region') ?: 'US'));

    $product = TaggedCache::for(['catalog'])->remember("bill_product_{$brandKey}_{$requested}", 3600, function () use ($brandKey, $requested) {
        $base = fn () => Product::query()
            ->where('brand_key', $brandKey)
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('slug', 'bill-payments'))
            ->with([
                'subcategory:id,name,slug',
                'category:id,name,slug',
                'variants' => fn ($q) => $q->where('is_available', true)->orderBy('face_value'),
            ]);

        return $base()->where('country_code', $requested)->first()
            ?: $base()->orderByRaw("country_code = 'US' DESC")->first();
    });

    abort_if(! $product, 404);

    return view('shop.product', ['product' => $product, 'brandKey' => $brandKey]);
})->name('shop.bill');

// Flights & Stays - branded "coming soon" pages until the booking catalog ships.
// One shared view, the `service` data flag drives the per-service copy + art.
Route::view('flights', 'shop.coming-soon', ['service' => 'flights'])->name('shop.flights');
Route::view('stays', 'shop.coming-soon', ['service' => 'stays'])->name('shop.stays');

// Help Center - static FAQ, topic filters and support contact details.
Route::view('help', 'shop.help')->name('shop.help');

// How It Works - marketing walkthrough of the buying flow.
Route::view('how-it-works', 'shop.how-it-works')->name('shop.how-it-works');

// Contact - storefront contact page + message submission (stored + admin-notified).
Route::get('contact', [ContactController::class, 'index'])->name('shop.contact');
Route::post('contact', [ContactController::class, 'store'])->name('contact.send');

// Refund and Cancellation Policy.
Route::view('refund-policy', 'shop.refund-policy')->name('shop.refund-policy');

// Privacy Policy.
Route::view('privacy', 'shop.privacy')->name('shop.privacy');

// Cookie Policy.
Route::view('cookie-policy', 'shop.cookie-policy')->name('shop.cookie-policy');

// Compliance and Regulatory Framework.
Route::view('compliance', 'shop.compliance')->name('shop.compliance');

// About.
Route::view('about', 'shop.about')->name('shop.about');

// Mobile app - "in development" landing page.
Route::view('mobile-app', 'shop.mobile-app')->name('shop.mobile-app');

// Earn Points - Rcoin rewards programme.
Route::view('earn-points', 'shop.earn-points')->name('shop.earn-points');

// FAQ - comprehensive frequently asked questions.
Route::view('faq', 'shop.faq')->name('shop.faq');

// Press and Media - newsroom grid + single post view.
Route::get('press', [PressController::class, 'index'])->name('shop.press');
Route::get('press/{slug}', [PressController::class, 'show'])->name('shop.press.show');

// Blog - articles grid + single post view.
Route::get('blog', [BlogController::class, 'index'])->name('shop.blog');
Route::get('blog/{slug}', [BlogController::class, 'show'])->name('shop.blog.show');

// Reviews - curated customer reviews + Trustpilot review-collector CTA.
Route::view('reviews', 'shop.reviews')->name('shop.reviews');

// Terms of Service.
Route::view('terms', 'shop.terms')->name('shop.terms');

// Accessibility statement.
Route::view('accessibility', 'shop.accessibility')->name('shop.accessibility');

// HTML sitemap - a human-friendly index of every section.
Route::view('sitemap', 'shop.sitemap')->name('shop.sitemap');

// XML sitemap for search engines (lists public pages only).
Route::get('sitemap.xml', function () {
    $names = [
        'home', 'shop.gift-cards', 'shop.esims', 'shop.topups', 'shop.bills',
        'shop.flights', 'shop.stays', 'shop.cart', 'shop.help', 'shop.how-it-works',
        'shop.contact', 'shop.about', 'shop.privacy', 'shop.terms', 'shop.cookie-policy',
        'shop.refund-policy', 'shop.compliance', 'shop.accessibility', 'shop.sitemap',
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
    foreach ($names as $name) {
        $xml .= '  <url><loc>'.e(route($name)).'</loc></url>'."\n";
    }
    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap.xml');

// Cart page (HTML). Store-driven - it hydrates from the /cart/data JSON endpoint.
Route::get('cart', [CartWebController::class, 'page'])->name('shop.cart');

// Storefront cart - web routes (session-authenticated) wrapping the backend CartManager.
// Returns compact JSON the Alpine cart store consumes. See CartWebController.
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('data', [CartWebController::class, 'show'])->name('data');
    // not-suspended is a no-op for guests; only kicks in for an authenticated suspended user.
    Route::post('items', [CartWebController::class, 'add'])->middleware('not-suspended')->name('items.add');
    Route::patch('items/{item}', [CartWebController::class, 'update'])->middleware('not-suspended')->name('items.update');
    Route::delete('items/{item}', [CartWebController::class, 'remove'])->middleware('not-suspended')->name('items.remove');
});

// Checkout. Resolves the active cart (CartManager - same path the global CartComposer uses)
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

// Checkout submission - creates the Order + OrderItems + a pending Payment from the
// cart. Gateway hand-off (Flutterwave / NowPayments / wallet debit) is the TODO inside
// the controller. Requires auth: an Order needs a user_id.
Route::post('checkout', [CheckoutController::class, 'process'])
    ->middleware(['auth', 'not-suspended'])
    ->name('checkout.process');

// Order confirmation page.
Route::get('order/{orderNumber}', [CheckoutController::class, 'order'])
    ->middleware('auth')
    ->name('shop.order');

// Customer dashboard - gated by web guard. Admin operators have their own area at /admin/* via routes/admin.php.
// NOTE: 'verified' middleware intentionally NOT applied - verification stays a SOFT requirement so users
// can use the app immediately after registration. The profile page surfaces the verification status badge
// and the sidebar has a Verify Identity entry, so users have multiple paths to verify when they choose.
// Re-add 'verified' here only if/when CTO wants to hard-gate the dashboard behind a confirmed email.
Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::view('dashboard/rewards', 'dashboard.rewards')->name('dashboard.rewards');

    // Rcoin withdrawal request - posts from the form on the rewards page.
    // Admins review at /admin/content/rewards-withdrawals.
    Route::post('dashboard/rewards/withdraw', [RcoinWithdrawalController::class, 'store'])->name('dashboard.rewards.withdraw');

    // Instant Rcoin → wallet (USD) conversion. No admin approval. Capped by
    // wallet_conversion_min_usd setting (default $2.00).
    Route::post('dashboard/rewards/convert-to-wallet', [RcoinConvertController::class, 'toWallet'])->name('dashboard.rewards.convert-to-wallet');

    Route::view('dashboard/kyc', 'dashboard.kyc')->name('dashboard.kyc');

    Volt::route('dashboard/orders', 'dashboard.orders')->name('dashboard.orders');

    // Native eSIM top-up - refill an existing Airalo eSIM from the dashboard
    // without leaving the site. The controller hits Airalo's /sims/{iccid}/topups
    // for the available packages, debits the wallet on purchase, and dispatches
    // the standard fulfilment job which routes to /orders/topups via the
    // parent_iccid metadata flag.
    Route::get('dashboard/esims/{orderItem}/top-up', [EsimTopupController::class, 'show'])->name('dashboard.esim.topup');
    Route::post('dashboard/esims/{orderItem}/top-up', [EsimTopupController::class, 'purchase'])->middleware('not-suspended')->name('dashboard.esim.topup.purchase');

    Volt::route('dashboard/transactions', 'dashboard.transactions')->name('dashboard.transactions');

    Volt::route('dashboard/notifications', 'dashboard.notifications')->name('dashboard.notifications');

    Route::view('dashboard/saved-cards', 'dashboard.saved-cards')->name('dashboard.saved-cards');

    Route::view('dashboard/wallet', 'dashboard.wallet')->name('dashboard.wallet');

    Volt::route('dashboard/profile', 'settings.profile')->name('dashboard.profile');
    Volt::route('dashboard/password', 'settings.password')->name('dashboard.password');
    Volt::route('dashboard/appearance', 'settings.appearance')->name('dashboard.appearance');

    // Persists the customer's light/dark/system preference (theme engine posts here).
    Route::post('preferences/theme', [ThemeController::class, 'update'])->name('preferences.theme');

    // KYC identity-verification submission (documents stored on the private disk).
    Route::post('dashboard/kyc', [KycController::class, 'store'])->name('kyc.submit');

    // Suspension review request - submitted from the banner the customer
    // sees on their dashboard when their account is suspended. Lands in the
    // shared admin notification feed.
    Route::post('dashboard/suspension/request-review', [SuspensionController::class, 'requestReview'])
        ->name('suspension.request-review');
});

// Legacy /settings/* URLs redirect to the new /dashboard/* paths so old bookmarks keep working.
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'dashboard/profile');
    Route::redirect('settings/profile', 'dashboard/profile');
    Route::redirect('settings/password', 'dashboard/password');
    Route::redirect('settings/appearance', 'dashboard/appearance');
});

// Local-only email template previews - design the transactional emails in the
// browser (no events fired, no mail sent). Never registered outside local.
if (app()->environment('local')) {
    Route::get('dev/emails', [EmailPreviewController::class, 'index'])->name('dev.emails.index');
    Route::get('dev/emails/{key}', [EmailPreviewController::class, 'show'])->name('dev.emails.show');
}

require __DIR__.'/auth.php';
