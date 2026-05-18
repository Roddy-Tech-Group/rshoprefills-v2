/**
 * Entrance + scroll-reveal animations + Roddy Custom Hero animation with GSAP.
 *
 * GSAP is loaded via dynamic import so the page still works if the package
 * isn't installed yet. Run `npm i gsap` to enable animations.
 */
async function initAnimations() {
    let gsap, ScrollTrigger, InertiaPlugin;
    try {
        ({ default: gsap } = await import('gsap'));
        ({ ScrollTrigger } = await import('gsap/ScrollTrigger'));
        gsap.registerPlugin(ScrollTrigger);
    } catch (err) {
        console.warn('[animations] gsap not available — skipping animations. Run `npm i gsap`.');
        return;
    }

    // InertiaPlugin is optional — only needed for the Roddy Custom Hero dots inertia.
    try {
        ({ InertiaPlugin } = await import('gsap/InertiaPlugin'));
        gsap.registerPlugin(InertiaPlugin);
    } catch (err) {
        InertiaPlugin = null;
        console.warn('[animations] InertiaPlugin not available — hero dots will fall back to simple physics.');
    }

    // Tear down any existing scroll triggers so they don't pile up after navigation.
    ScrollTrigger.getAll().forEach((t) => t.kill());

    // ----- Hero entrance timeline (on load) -----
    // Pure slide-up; no opacity fade so the hero content is always readable
    // even if the user hits the page mid-animation.
    if (document.querySelector('[data-anim="hero-headline"]')) {
        const heroTL = gsap.timeline({ defaults: { ease: 'power3.out', duration: 0.8 } });
        heroTL
            .from('[data-anim="hero-headline"]', { y: 24 })
            .from('[data-anim="hero-subtitle"]', { y: 18 }, '-=0.55')
            .from('[data-anim="hero-ctas"]',     { y: 18 }, '-=0.55')
            .from('[data-anim="hero-banner"]',   { y: 24 }, '-=0.5');
    }

    // ----- Scroll-triggered staggered groups (rows of cards / tiles) -----
    gsap.utils.toArray('[data-reveal-group]').forEach((group) => {
        const items = group.querySelectorAll('[data-reveal-item]');
        if (!items.length) return;

        gsap.fromTo(
            items,
            { y: 24, autoAlpha: 0 },
            {
                y: 0,
                autoAlpha: 1,
                duration: 0.7,
                stagger: 0.07,
                ease: 'power2.out',
                immediateRender: false,
                scrollTrigger: {
                    trigger: group,
                    start: 'top 85%',
                    toggleActions: 'play none none none',
                },
            }
        );
    });

    // ----- Plain single-element fade-up reveals -----
    gsap.utils.toArray('[data-reveal]').forEach((el) => {
        gsap.fromTo(
            el,
            { y: 20, autoAlpha: 0 },
            {
                y: 0,
                autoAlpha: 1,
                duration: 0.7,
                ease: 'power2.out',
                immediateRender: false,
                scrollTrigger: {
                    trigger: el,
                    start: 'top 85%',
                    toggleActions: 'play none none none',
                },
            }
        );
    });

    // ----- Roddy Custom Hero animation with GSAP: interactive dots grid -----
    initRoddyCustomHeroDots(gsap, !!InertiaPlugin);

    // Recalculate positions after the page settles.
    requestAnimationFrame(() => ScrollTrigger.refresh());
}

/**
 * Roddy Custom Hero animation with GSAP.
 * Builds a grid of dots inside any [data-dots-container-init] element.
 * Dots glow toward an active colour as the cursor approaches, get pushed
 * outward on fast motion, and respond to clicks with a radial shockwave.
 */
function initRoddyCustomHeroDots(gsap, hasInertia) {
    document.querySelectorAll('[data-dots-container-init]').forEach((container) => {
        // Roddy Custom Hero animation with GSAP: configuration
        const colors         = { base: 'rgba(113, 113, 122, 0.2)', active: '#3B82F6' };
        const threshold      = 150;
        const speedThreshold = 100;
        const shockRadius    = 250;
        const shockPower     = 5;
        const maxSpeed       = 5000;
        const centerHole     = true;

        let dots = [];
        let dotCenters = [];

        function buildGrid() {
            container.innerHTML = '';
            dots = [];
            dotCenters = [];

            const style = getComputedStyle(container);
            const dotPx = parseFloat(style.fontSize);
            const gapPx = dotPx * 2;
            const contW = container.clientWidth;
            const contH = container.clientHeight;

            const cols  = Math.floor((contW + gapPx) / (dotPx + gapPx));
            const rows  = Math.floor((contH + gapPx) / (dotPx + gapPx));
            const total = cols * rows;

            const holeCols = centerHole ? (cols % 2 === 0 ? 4 : 5) : 0;
            const holeRows = centerHole ? (rows % 2 === 0 ? 4 : 5) : 0;
            const startCol = (cols - holeCols) / 2;
            const startRow = (rows - holeRows) / 2;

            for (let i = 0; i < total; i++) {
                const row    = Math.floor(i / cols);
                const col    = i % cols;
                const isHole = centerHole &&
                    row >= startRow && row < startRow + holeRows &&
                    col >= startCol && col < startCol + holeCols;

                const d = document.createElement('div');
                d.classList.add('roddy-dot');

                if (isHole) {
                    d.style.visibility = 'hidden';
                    d._isHole = true;
                } else {
                    gsap.set(d, { x: 0, y: 0, backgroundColor: colors.base });
                    d._inertiaApplied = false;
                }

                container.appendChild(d);
                dots.push(d);
            }

            requestAnimationFrame(() => {
                dotCenters = dots
                    .filter((d) => !d._isHole)
                    .map((d) => {
                        const r = d.getBoundingClientRect();
                        return {
                            el: d,
                            x:  r.left + window.scrollX + r.width  / 2,
                            y:  r.top  + window.scrollY + r.height / 2,
                        };
                    });
            });
        }

        window.addEventListener('resize', buildGrid);
        buildGrid();

        // Roddy Custom Hero animation with GSAP: hover glow + inertia push
        let lastTime = 0, lastX = 0, lastY = 0;

        window.addEventListener('mousemove', (e) => {
            const now = performance.now();
            const dt  = now - lastTime || 16;
            let   dx  = e.pageX - lastX;
            let   dy  = e.pageY - lastY;
            let   vx  = dx / dt * 1000;
            let   vy  = dy / dt * 1000;
            let speed = Math.hypot(vx, vy);

            if (speed > maxSpeed) {
                const scale = maxSpeed / speed;
                vx *= scale; vy *= scale; speed = maxSpeed;
            }

            lastTime = now;
            lastX    = e.pageX;
            lastY    = e.pageY;

            requestAnimationFrame(() => {
                dotCenters.forEach(({ el, x, y }) => {
                    const dist = Math.hypot(x - e.pageX, y - e.pageY);
                    const t    = Math.max(0, 1 - dist / threshold);
                    const col  = gsap.utils.interpolate(colors.base, colors.active, t);
                    gsap.set(el, { backgroundColor: col });

                    if (speed > speedThreshold && dist < threshold && !el._inertiaApplied) {
                        el._inertiaApplied = true;
                        const pushX = (x - e.pageX) + vx * 0.005;
                        const pushY = (y - e.pageY) + vy * 0.005;

                        pushDot(el, pushX, pushY, hasInertia);
                    }
                });
            });
        });

        // Roddy Custom Hero animation with GSAP: click shockwave
        window.addEventListener('click', (e) => {
            dotCenters.forEach(({ el, x, y }) => {
                const dist = Math.hypot(x - e.pageX, y - e.pageY);
                if (dist < shockRadius && !el._inertiaApplied) {
                    el._inertiaApplied = true;
                    const falloff = Math.max(0, 1 - dist / shockRadius);
                    const pushX   = (x - e.pageX) * shockPower * falloff;
                    const pushY   = (y - e.pageY) * shockPower * falloff;

                    pushDot(el, pushX, pushY, hasInertia);
                }
            });
        });

        function pushDot(el, pushX, pushY, useInertia) {
            const settle = () => {
                gsap.to(el, {
                    x: 0,
                    y: 0,
                    duration: 1.5,
                    ease: 'elastic.out(1, 0.75)',
                });
                el._inertiaApplied = false;
            };

            if (useInertia) {
                gsap.to(el, {
                    inertia: { x: pushX, y: pushY, resistance: 750 },
                    onComplete: settle,
                });
            } else {
                // Fallback when InertiaPlugin isn't available — quick push, then settle.
                gsap.to(el, {
                    x: pushX,
                    y: pushY,
                    duration: 0.3,
                    ease: 'power2.out',
                    onComplete: settle,
                });
            }
        }
    });
}

/**
 * Lottie loader. Mounts any [data-lottie="/path.json"] container as an animation.
 *
 * Usage in blade:
 *   <div data-lottie="{{ asset('lottie/empty-cart.json') }}"
 *        data-lottie-loop="true"
 *        data-lottie-autoplay="true"
 *        class="h-40 w-40"></div>
 *
 * Drops the lottie-web import as a dynamic import so the page still works if
 * the package isn't installed yet. Run `npm i lottie-web` to enable.
 */
async function initLottie() {
    const targets = document.querySelectorAll('[data-lottie]:not([data-lottie-mounted])');
    if (!targets.length) return;

    let lottie;
    try {
        ({ default: lottie } = await import('lottie-web'));
    } catch (err) {
        console.warn('[lottie] lottie-web not available — skipping. Run `npm i lottie-web`.');
        return;
    }

    targets.forEach((el) => {
        try {
            lottie.loadAnimation({
                container: el,
                renderer: 'svg',
                loop: el.dataset.lottieLoop !== 'false',
                autoplay: el.dataset.lottieAutoplay !== 'false',
                path: el.dataset.lottie,
            });
            el.setAttribute('data-lottie-mounted', 'true');
        } catch (err) {
            console.warn('[lottie] failed to mount', el.dataset.lottie, err);
        }
    });
}

// First load (handle both pre- and post-DOMContentLoaded states)
function bootAll() {
    initAnimations();
    initLottie();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAll);
} else {
    bootAll();
}

// Re-run on Livewire SPA navigation so animations replay on the new page.
document.addEventListener('livewire:navigated', bootAll);

/**
 * Shared locale Alpine component used by both the storefront and customer-dashboard
 * layouts. Owns: locale modal open state, country (name + flag + ISO code), language,
 * and currency (code + symbol). Persists to localStorage so the user's preferences
 * survive page navigation. When the user changes country or currency AND they're on
 * a shop page that supports those URL filters (currently /gift-cards), it pushes
 * `?country=` and `?currency=` and reloads via Livewire's wire:navigate so the page
 * re-renders with the filter applied.
 */
/**
 * Global cart store. Talks to the session-authenticated web cart routes
 * (/cart, /cart/items) which wrap the backend CartManager. The nav cart popup
 * and the product page both read/drive this single store.
 */
document.addEventListener('alpine:init', () => {
    // Active wallet index — shared so the desktop wallet card and the mobile
    // wallet carousel always show the same wallet (synced live, no page reload).
    window.Alpine.store('wallet', { active: 0 });

    window.Alpine.store('cart', {
        items: [],
        count: 0,
        subtotal: 0,         // cart subtotal in the customer's display currency
        subtotalUsd: 0,      // same subtotal in USD — the settlement base
        currency: 'USD',
        currencySymbol: '$',
        rate: 1,
        open: false,         // nav popup visibility
        loading: false,
        hydrated: false,     // true once the first /cart/data fetch resolves

        _csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        // The customer's chosen display currency, written to localStorage by the
        // locale modal. Sent to the cart endpoints so the server converts prices.
        _currency() {
            try {
                return localStorage.getItem('locale.currency') || 'USD';
            } catch (_) {
                return 'USD';
            }
        },

        _url(path) {
            const sep = path.includes('?') ? '&' : '?';
            return path + sep + 'currency=' + encodeURIComponent(this._currency());
        },

        _apply(data) {
            this.items = data.items || [];
            this.count = data.count || 0;
            this.subtotal = data.subtotal || 0;
            this.subtotalUsd = data.subtotal_usd || 0;
            this.currency = data.currency || 'USD';
            this.currencySymbol = data.currency_symbol || '$';
            this.rate = data.rate || 1;
        },

        // True when the display currency differs from USD — only then does the UI
        // show the USD figure as a secondary anchor.
        get showUsd() {
            return this.currency !== 'USD';
        },

        // Format an amount already in the customer's display currency.
        pay(value) {
            const n = Number(value || 0);
            if (this.currency === 'USD') {
                return this.usd(n);
            }
            return this.currencySymbol + ' ' + n.toLocaleString('en-US', { maximumFractionDigits: 2 });
        },

        // Format a USD amount.
        usd(value) {
            return '$' + Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        // Pull current cart state (called once on page load).
        async refresh() {
            try {
                const res = await fetch(this._url('/cart/data'), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (res.ok) {
                    this._apply(await res.json());
                }
            } catch (_) { /* offline / ignore */ } finally {
                this.hydrated = true;
            }
        },

        // Add a variant to the cart, then drop the nav popup open.
        async add(variantId, quantity = 1, requestedValue = null) {
            this.loading = true;
            try {
                const body = { product_variant_id: variantId, quantity };
                if (requestedValue) {
                    body.requested_value = requestedValue;
                }
                const res = await fetch(this._url('/cart/items'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this._csrf(),
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(body),
                });
                if (res.ok) {
                    this._apply(await res.json());
                    this.open = true;
                    return true;
                }
                return false;
            } catch (_) {
                return false;
            } finally {
                this.loading = false;
            }
        },

        async setQty(itemId, quantity) {
            try {
                const res = await fetch(this._url('/cart/items/' + itemId), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this._csrf(),
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ quantity }),
                });
                if (res.ok) {
                    this._apply(await res.json());
                }
            } catch (_) { /* ignore */ }
        },

        async remove(itemId) {
            try {
                const res = await fetch(this._url('/cart/items/' + itemId), {
                    method: 'DELETE',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this._csrf() },
                    credentials: 'same-origin',
                });
                if (res.ok) {
                    this._apply(await res.json());
                }
            } catch (_) { /* ignore */ }
        },
    });

    // Hydrate once on load.
    window.Alpine.store('cart').refresh();

    // Re-price the cart when the customer switches currency in the locale modal.
    window.addEventListener('currency-changed', () => window.Alpine.store('cart').refresh());
});

/**
 * Live brand search dropdown for the storefront nav. Hits /api/search/brands?q=
 * with a small debounce, renders a dropdown of matching brands while the user
 * types. Pressing Enter submits the form to /gift-cards?q= for the full
 * results page.
 */
window.navBrandSearch = function () {
    return {
        query: '',
        results: [],
        open: false,
        loading: false,
        _debounce: null,
        _aborter: null,

        onInput() {
            const q = this.query.trim();
            if (q.length < 2) {
                this.results = [];
                this.open = false;
                this.loading = false;
                if (this._aborter) this._aborter.abort();
                return;
            }
            this.open = true;
            this.loading = true;
            clearTimeout(this._debounce);
            this._debounce = setTimeout(() => this.fetchResults(q), 200);
        },

        async fetchResults(q) {
            // Abort any in-flight request so we don't race-condition stale results.
            if (this._aborter) this._aborter.abort();
            this._aborter = new AbortController();

            try {
                const res = await fetch('/api/search/brands?q=' + encodeURIComponent(q), {
                    signal: this._aborter.signal,
                    headers: { 'Accept': 'application/json' },
                });
                if (! res.ok) {
                    this.results = [];
                    this.loading = false;
                    return;
                }
                this.results = await res.json();
            } catch (err) {
                if (err.name === 'AbortError') return; // expected when typing fast
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        clear() {
            this.query = '';
            this.results = [];
            this.open = false;
            this.loading = false;
            this.$refs.search.focus();
        },
    };
};

/**
 * Customer-reviews carousel. The header arrow advances the horizontal scroll
 * by one screenful of whole cards using a custom requestAnimationFrame easing
 * pass (cubic ease-in-out), so the motion is consistently smooth across every
 * browser, and loops back to the start once the end is reached. It also tracks
 * SPA navigation so the section can hold a skeleton while the next page loads.
 *
 *   x-data="customerReviewsCarousel()"  with x-ref="track" on the scroll row.
 */
window.customerReviewsCarousel = function () {
    return {
        navigating: false,
        animating: false,

        // Cubic ease-in-out — slow start, quick middle, gentle finish.
        _ease(t) {
            return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
        },

        // Animate the scroll position to `target` over ~620ms with rAF.
        _scrollTo(target) {
            const track = this.$refs.track;
            if (! track) return;

            const max = track.scrollWidth - track.clientWidth;
            const start = track.scrollLeft;
            const end = Math.max(0, Math.min(target, max));
            const distance = end - start;
            if (Math.abs(distance) < 1) return;

            // Respect reduced-motion — jump straight there, no animation.
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                track.scrollLeft = end;
                return;
            }

            const duration = 620;
            const startedAt = performance.now();
            this.animating = true;

            const step = (now) => {
                const progress = Math.min(1, (now - startedAt) / duration);
                track.scrollLeft = start + distance * this._ease(progress);
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    this.animating = false;
                }
            };
            requestAnimationFrame(step);
        },

        // Advance one screenful; loop back to the start once the end is reached.
        next() {
            if (this.animating) return;
            const track = this.$refs.track;
            if (! track) return;

            const card = track.querySelector('article');
            const cardWidth = (card ? card.offsetWidth : 288) + 20; // card + gap
            const max = track.scrollWidth - track.clientWidth;

            if (track.scrollLeft >= max - 8) {
                this._scrollTo(0);
                return;
            }

            const perView = Math.max(1, Math.floor(track.clientWidth / cardWidth));
            this._scrollTo(track.scrollLeft + perView * cardWidth);
        },
    };
};

/**
 * Pointer-tracked 3D tilt for gift-card tiles. On mousemove the card rotates
 * toward the pointer, lifts, and shows a soft light glare; on mouseleave it
 * eases flat. Reduced-motion users get no tilt. Pair with the .card-3d /
 * .card-3d-glare CSS and a .card-3d-scene ancestor (which supplies perspective).
 *
 *   x-data="cardTilt()"  on the .card-3d element, with @mousemove / @mouseleave.
 */
window.cardTilt = function (max = 9) {
    return {
        _reduced() {
            return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        },

        tilt(e) {
            if (this._reduced()) return;
            const el = this.$el;
            const r = el.getBoundingClientRect();
            const px = (e.clientX - r.left) / r.width;   // 0..1 across
            const py = (e.clientY - r.top) / r.height;   // 0..1 down

            el.style.setProperty('--tilt-ry', ((px - 0.5) * 2 * max).toFixed(2) + 'deg');
            el.style.setProperty('--tilt-rx', (-(py - 0.5) * 2 * max).toFixed(2) + 'deg');
            el.style.setProperty('--tilt-lift', '-6px');
            el.style.setProperty('--tilt-scale', '1.03');
            el.style.setProperty('--glare-x', (px * 100).toFixed(1) + '%');
            el.style.setProperty('--glare-y', (py * 100).toFixed(1) + '%');
            el.classList.add('is-tilting');
        },

        reset() {
            const el = this.$el;
            el.classList.remove('is-tilting');
            el.style.setProperty('--tilt-rx', '0deg');
            el.style.setProperty('--tilt-ry', '0deg');
            el.style.setProperty('--tilt-lift', '0px');
            el.style.setProperty('--tilt-scale', '1');
        },
    };
};

/**
 * Dynamic value flipper — re-fires a quick roll-and-fade (.value-flip / the
 * valueFlip keyframe in app.css) whenever a displayed number changes. Used on
 * the product + checkout readouts: denomination, quantity, estimated price,
 * points, totals. `ready` is a closure flag (NOT reactive Alpine data, so
 * reading it inside x-effect can't re-trigger the effect) — it skips the
 * initial render so values only animate on a real change.
 *
 *   x-data="valueFlip()"  x-effect="<deps>; flash()"  on the element with x-text.
 */
window.valueFlip = function () {
    let ready = false;
    return {
        flash() {
            if (! ready) {
                ready = true;
                return;
            }
            const el = this.$el;
            el.classList.remove('value-flip');
            void el.offsetWidth; // force a reflow so the animation restarts
            el.classList.add('value-flip');
        },
    };
};

window.storefrontLocale = function () {
    // Pages that read country/currency from the URL. The listing and every brand-level
    // detail page (e.g. /gift-cards/apple) both honour ?country=XX so flipping the locale
    // modal reloads either one with the new filters applied.
    const SHOP_PATH_PREFIXES = ['/gift-cards'];

    // Storefront paths where switching country must reload, so the region lock
    // re-applies: the homepage and the whole gift-cards section (listing + brand).
    const isShopPath = (path) => {
        return path === '/' || SHOP_PATH_PREFIXES.some((p) => path === p || path.startsWith(p + '/'));
    };

    const read = (key, fallback) => {
        try {
            const v = localStorage.getItem('locale.' + key);
            return v === null ? fallback : v;
        } catch (_) {
            return fallback;
        }
    };

    return {
        localeModalOpen: false,

        country:        read('country',        'United States'),
        countryFlag:    read('countryFlag',    '🇺🇸'),
        countryCode:    read('countryCode',    'US'),
        language:       read('language',       'English'),
        currency:       read('currency',       'USD'),
        currencySymbol: read('currencySymbol', '$'),

        activeCategory: 'Gift Cards',

        init() {
            const save = (key) => (val) => {
                try { localStorage.setItem('locale.' + key, val ?? ''); } catch (_) {}
            };

            // Persist each piece independently.
            this.$watch('country',        save('country'));
            this.$watch('countryFlag',    save('countryFlag'));
            this.$watch('countryCode',    save('countryCode'));
            this.$watch('language',       save('language'));
            this.$watch('currency',       save('currency'));
            this.$watch('currencySymbol', save('currencySymbol'));

            // When country or currency change AND the user is on a filterable shop page,
            // navigate to the same path with the updated URL params so the catalog reloads.
            const reloadIfShop = () => {
                const path = window.location.pathname;
                if (!isShopPath(path)) return;

                const url = new URL(window.location.href);
                if (this.countryCode) url.searchParams.set('country', this.countryCode);
                else url.searchParams.delete('country');

                if (this.currency) url.searchParams.set('currency', this.currency);
                else url.searchParams.delete('currency');

                // Livewire's SPA navigation if available, otherwise hard reload.
                if (window.Livewire && typeof window.Livewire.navigate === 'function') {
                    window.Livewire.navigate(url.toString());
                } else {
                    window.location.href = url.toString();
                }
            };

            this.$watch('countryCode', reloadIfShop);
            this.$watch('currency',    reloadIfShop);

            // Tell the cart store to re-price when the display currency changes
            // (covers non-shop pages, where reloadIfShop does not navigate).
            this.$watch('currency', () => window.dispatchEvent(new CustomEvent('currency-changed')));
        },
    };
};

/**
 * Modern dropdown select used by the KYC form (and reusable elsewhere). Replaces a
 * native <select> with a styled button + panel. Carries its value on a sibling
 * hidden <input> so it still submits with the form. Pass `searchable: true` to add
 * a filter box (used for the long country list).
 *
 *   x-data="kycSelect(options, initialValue, searchable)"
 *   where options is [{ value, label }, ...]
 */
window.kycSelect = function (options = [], initial = '', searchable = false) {
    return {
        open: false,
        value: initial,
        query: '',
        options: options,
        searchable: searchable,

        get filtered() {
            if (!this.searchable || !this.query.trim()) {
                return this.options;
            }
            const q = this.query.trim().toLowerCase();
            return this.options.filter((o) => o.label.toLowerCase().includes(q));
        },

        get selectedLabel() {
            const match = this.options.find((o) => o.value === this.value);
            return match ? match.label : '';
        },

        toggle() {
            this.open = !this.open;
            if (this.open && this.searchable) {
                this.$nextTick(() => this.$refs.search && this.$refs.search.focus());
            }
        },

        pick(value) {
            this.value = value;
            this.open = false;
            this.query = '';
        },
    };
};

/**
 * Calendar date picker used by the KYC form for date of birth. Renders a month grid
 * with month + year stepping (single chevron steps a month, double steps a year).
 * Future dates are disabled. The chosen date is written as an ISO yyyy-mm-dd string
 * to a sibling hidden <input> so it submits with the form.
 *
 *   x-data="kycDatePicker(initialIsoDate)"
 */
window.kycDatePicker = function (initial = '') {
    const today = new Date();
    const months = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    // Default the calendar to ~20 years ago — a sensible starting point for a birth date.
    const start = initial ? new Date(initial + 'T00:00:00') : new Date(today.getFullYear() - 20, today.getMonth(), 1);

    return {
        open: false,
        value: initial,
        viewYear: start.getFullYear(),
        viewMonth: start.getMonth(),
        months: months,
        weekdays: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],

        get monthLabel() {
            return this.months[this.viewMonth] + ' ' + this.viewYear;
        },

        // Leading nulls pad the grid so day 1 lands under the right weekday.
        get cells() {
            const firstWeekday = new Date(this.viewYear, this.viewMonth, 1).getDay();
            const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
            const arr = [];
            for (let i = 0; i < firstWeekday; i++) {
                arr.push(null);
            }
            for (let d = 1; d <= daysInMonth; d++) {
                arr.push(d);
            }
            return arr;
        },

        get displayValue() {
            if (!this.value) {
                return '';
            }
            const d = new Date(this.value + 'T00:00:00');
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        shiftMonth(delta) {
            let m = this.viewMonth + delta;
            let y = this.viewYear;
            while (m < 0) { m += 12; y -= 1; }
            while (m > 11) { m -= 12; y += 1; }
            this.viewMonth = m;
            this.viewYear = y;
        },

        shiftYear(delta) {
            this.viewYear += delta;
        },

        isSelected(day) {
            if (!day || !this.value) {
                return false;
            }
            const s = new Date(this.value + 'T00:00:00');
            return s.getFullYear() === this.viewYear
                && s.getMonth() === this.viewMonth
                && s.getDate() === day;
        },

        isFuture(day) {
            if (!day) {
                return false;
            }
            const cellEnd = new Date(this.viewYear, this.viewMonth, day, 23, 59, 59);
            return cellEnd > new Date();
        },

        pick(day) {
            if (!day || this.isFuture(day)) {
                return;
            }
            const mm = String(this.viewMonth + 1).padStart(2, '0');
            const dd = String(day).padStart(2, '0');
            this.value = this.viewYear + '-' + mm + '-' + dd;
            this.open = false;
        },
    };
};

/*
 * Keyboard navigation for every custom dropdown / menu on the site.
 *
 * The whole storefront + admin use one markup convention: a `.relative`
 * wrapper holding a `.absolute` popup panel whose options are real <a>/<button>
 * elements. This single listener drives them all — no per-dropdown wiring:
 *   - Arrow Down / Up move focus between the open panel's options.
 *   - Enter activates the focused option natively (a focused <a> follows its
 *     href; a focused <button> fires its @click), so no Enter handling here.
 *   - It only acts when focus sits on a control inside an open dropdown, so it
 *     never hijacks arrow keys elsewhere on the page.
 */
(function () {
    const isVisible = (el) => !! (el && el.offsetParent !== null);

    // Resolve the open popup panel relative to the currently focused element.
    function panelFor(el) {
        // Focus is already inside an open panel (on an option or the search box).
        const within = el.closest('.absolute');
        if (within && isVisible(within) && within.querySelector('a[href],button')) {
            return within;
        }
        // Focus is on the toggle — the panel is a visible `.absolute` element
        // inside the same `.relative` wrapper. Require >= 2 options so a stray
        // absolutely-positioned badge/icon is never mistaken for a menu.
        const wrapper = el.closest('.relative');
        if (! wrapper) {
            return null;
        }
        return [...wrapper.querySelectorAll('.absolute')].find(
            (p) => isVisible(p) && p.querySelectorAll('a[href],button:not([disabled])').length >= 2
        ) || null;
    }

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') {
            return;
        }
        const active = document.activeElement;
        if (! active || ! ['A', 'BUTTON', 'INPUT'].includes(active.tagName)) {
            return;
        }
        const panel = panelFor(active);
        if (! panel) {
            return;
        }
        const items = [...panel.querySelectorAll('a[href],button:not([disabled])')].filter(isVisible);
        if (! items.length) {
            return;
        }
        e.preventDefault();
        const i = items.indexOf(active);
        const next = e.key === 'ArrowDown'
            ? (i < 0 ? 0 : (i + 1) % items.length)
            : (i <= 0 ? items.length - 1 : i - 1);
        items[next].focus();
    });
})();
