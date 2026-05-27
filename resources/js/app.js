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
/**
 * Global popup auto-close on page scroll.
 *
 * Most dropdowns in the project already listen to `keydown.escape.window` to
 * close — by dispatching a synthetic Escape on window scroll, we close every
 * dropdown / search panel / form select that uses the standard pattern with a
 * single 8-line snippet, no per-component changes needed. Only fires for
 * window scroll (page-level) — element-level scrolls inside dropdowns don't
 * bubble to window, so users can still scroll long result lists freely.
 *
 * Also dispatches a custom `app-page-scroll` event for components that don't
 * use the Escape pattern but still want to react.
 */
(function autoCloseOnScroll() {
    let t;
    const escape = new KeyboardEvent('keydown', { key: 'Escape', bubbles: true });
    window.addEventListener('scroll', () => {
        clearTimeout(t);
        t = setTimeout(() => {
            window.dispatchEvent(escape);
            window.dispatchEvent(new CustomEvent('app-page-scroll'));
        }, 60);
    }, { passive: true });
})();

document.addEventListener('alpine:init', () => {
    // Active wallet index — shared so the desktop wallet card and the mobile
    // wallet carousel always show the same wallet (synced live, no page reload).
    window.Alpine.store('wallet', { active: 0 });

    // Admin sidebar collapse state — persisted to localStorage so the layout
    // remembers between page loads. The CSS rules that respond to the class
    // live next to the sidebar markup in resources/views/components/layouts/admin.blade.php.
    window.Alpine.store('adminSidebar', {
        collapsed: false,
        init() {
            try { this.collapsed = localStorage.getItem('admin.sidebar.collapsed') === '1'; } catch (e) {}
            this._sync();
        },
        toggle() {
            this.collapsed = ! this.collapsed;
            try { localStorage.setItem('admin.sidebar.collapsed', this.collapsed ? '1' : '0'); } catch (e) {}
            this._sync();
        },
        _sync() {
            document.documentElement.classList.toggle('admin-sidebar-collapsed', this.collapsed);
        },
    });
    window.Alpine.store('adminSidebar').init();

    // Customer dashboard sidebar collapse — mirrors the adminSidebar store
    // so both shells get the same UX (glass toggle, icon-only rail, hover
    // popups). Separate localStorage key so the two layouts remember
    // independently. CSS rules live in resources/views/components/layouts/dashboard.blade.php.
    window.Alpine.store('dashboardSidebar', {
        collapsed: false,
        init() {
            try { this.collapsed = localStorage.getItem('dashboard.sidebar.collapsed') === '1'; } catch (e) {}
            this._sync();
        },
        toggle() {
            this.collapsed = ! this.collapsed;
            try { localStorage.setItem('dashboard.sidebar.collapsed', this.collapsed ? '1' : '0'); } catch (e) {}
            this._sync();
        },
        _sync() {
            document.documentElement.classList.toggle('dashboard-sidebar-collapsed', this.collapsed);
        },
    });
    window.Alpine.store('dashboardSidebar').init();

    /**
     * Customer-dashboard preferences. Persisted in localStorage so each customer's
     * choice survives navigation and reloads. Hooked up via the Customize button's
     * dropdown (overview.blade.php) — readers throughout the dashboard check
     * `$store.dashPrefs.hideBalance` and the `.compact` class on <html>.
     */
    window.Alpine.store('dashPrefs', {
        hideBalance: false,
        compactMode: false,

        init() {
            try {
                this.hideBalance = localStorage.getItem('dash.hideBalance') === 'true';
                this.compactMode = localStorage.getItem('dash.compactMode') === 'true';
            } catch (_) { /* localStorage blocked — defaults stand */ }
            // Apply compact-mode class to <html> before first paint of the
            // dashboard so the layout doesn't flicker from comfy → compact.
            document.documentElement.classList.toggle('compact', this.compactMode);
        },

        setHideBalance(value) {
            this.hideBalance = !! value;
            try { localStorage.setItem('dash.hideBalance', this.hideBalance ? 'true' : 'false'); } catch (_) {}
        },

        setCompactMode(value) {
            this.compactMode = !! value;
            try { localStorage.setItem('dash.compactMode', this.compactMode ? 'true' : 'false'); } catch (_) {}
            document.documentElement.classList.toggle('compact', this.compactMode);
        },
    });
    // Stores aren't auto-init'd in Alpine 3 — call it ourselves.
    window.Alpine.store('dashPrefs').init();

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
            this.estimated_rcoin_reward = data.estimated_rcoin_reward || 0;
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
        async add(variantId, quantity = 1, requestedValue = null, metadata = null) {
            this.loading = true;
            try {
                const body = { product_variant_id: variantId, quantity };
                if (requestedValue) {
                    body.requested_value = requestedValue;
                }
                // Free-form per-item context (recipient phone for top-ups,
                // delivery email for gift cards, etc.) — server stores it
                // on cart_items.metadata_snapshot and copies it onto the
                // order item at checkout so fulfilment can act on it.
                if (metadata && typeof metadata === 'object') {
                    body.metadata = metadata;
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
 * Dashboard command palette (CTRL/CMD + K). Combines the static "Most used"
 * account links with live product search — typing 2+ characters hits the same
 * /api/search/brands?q= endpoint the storefront nav search uses, so the palette
 * searches the whole catalogue, not just dashboard navigation.
 */
window.dashboardSearch = function () {
    return {
        open: false,
        query: '',
        results: [],
        loading: false,
        _debounce: null,
        _aborter: null,
        activeTab: 'Overview',
        tabs: ['Overview', 'Orders', 'Wallet', 'Transactions', 'Profile', 'Settings'],

        /** True once the user has typed enough to trigger a product search. */
        get searching() {
            return this.query.trim().length >= 2;
        },

        onInput() {
            const q = this.query.trim();
            if (q.length < 2) {
                this.results = [];
                this.loading = false;
                if (this._aborter) this._aborter.abort();
                return;
            }
            this.loading = true;
            clearTimeout(this._debounce);
            this._debounce = setTimeout(() => this.fetchResults(q), 200);
        },

        async fetchResults(q) {
            // Abort any in-flight request so stale results never overwrite fresh ones.
            if (this._aborter) this._aborter.abort();
            this._aborter = new AbortController();

            try {
                const res = await fetch('/api/search/brands?q=' + encodeURIComponent(q), {
                    signal: this._aborter.signal,
                    headers: { Accept: 'application/json' },
                });
                this.results = res.ok ? await res.json() : [];
            } catch (err) {
                if (err.name !== 'AbortError') this.results = [];
            } finally {
                this.loading = false;
            }
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

            // Tell the translate engine (partials/translate-engine.blade.php) to
            // re-translate the page when the language changes from the locale modal.
            this.$watch('language', (val) => window.dispatchEvent(new CustomEvent('language-changed', { detail: val })));
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

/**
 * Admin dashboard Trends chart — smooth sales/cost area chart powered by
 * ApexCharts, lazy-imported so the bundle stays light on pages that don't
 * use it. Series accepts `[{ date, sales, cost }, …]`. The card header has a
 * Sales / Cost / Both dropdown driving client-side series visibility.
 *
 *   x-data="salesCostChart(@js($salesCostSeries))"
 */
/**
 * Admin dashboard Best Selling Countries world map — shades countries by
 * sales volume. Lazy-imports jsvectormap + its world-merc data file on
 * mount; persists its own dropdown state. Accepts:
 *   - countries: { ISO2: usdTotal, … } (Country mode)
 *   - regions:   { Continent: usdTotal, … } (Region mode)
 *   - codeToContinent: { ISO2: 'Africa' | 'Asia' | ... }
 *
 *   x-data="bestSellingCountriesMap({
 *     countries: @js($countriesByCode),
 *     regions: @js($salesByRegion),
 *     codeToContinent: @js($codeToContinent),
 *   })"
 */
window.bestSellingCountriesMap = function (payload) {
    const COUNTRIES = payload.countries || {};
    const REGIONS = payload.regions || {};
    const CODE_TO_CONTINENT = payload.codeToContinent || {};

    return {
        map: null,
        view: 'country',  // 'country' | 'region'
        continent: 'all', // 'all' | 'Africa' | 'Asia' | 'Europe' | 'North America' | 'South America' | 'Oceania'

        async init() {
            // jsvectormap's map data files are side-effect scripts that call
            // `jsVectorMap.addMap(...)` against a GLOBAL constructor — they
            // don't import it. So we have to publish the constructor first,
            // THEN load the map data, or the map silently never registers
            // and the canvas paints empty. Order matters here.
            const { default: JsVectorMap } = await import('jsvectormap');
            await import('jsvectormap/dist/jsvectormap.css');
            window.jsVectorMap = JsVectorMap;
            await import('jsvectormap/dist/maps/world-merc.js');

            const el = this.$refs.map;
            if (!el) { return; }
            el.innerHTML = '';

            const dark = document.documentElement.classList.contains('dark');
            const bg = dark ? '#26416b' : '#e5e7eb';
            const stroke = dark ? '#34507a' : '#cbd5e1';

            // Cursor affordance: grab on idle, grabbing during a drag pan.
            el.style.cursor = 'grab';
            el.addEventListener('mousedown', () => { el.style.cursor = 'grabbing'; });
            window.addEventListener('mouseup', () => { el.style.cursor = 'grab'; });

            this.map = new JsVectorMap({
                selector: el,
                map: 'world_merc',
                backgroundColor: 'transparent',
                // Interactive zoom + pan — wheel zoom, +/- buttons, click-drag pan.
                zoomOnScroll: true,
                zoomOnScrollSpeed: 3,
                zoomButtons: true,
                zoomMax: 8,
                zoomMin: 1,
                draggable: true,
                regionStyle: {
                    initial: { fill: bg, fillOpacity: 1, stroke, strokeWidth: 0.5 },
                    // Light-green hover highlight (Tailwind green-300) so the
                    // pointer-tracked country reads clearly against the blue
                    // shading of buyer regions and the grey of non-buyers.
                    hover:   { fill: '#86efac', fillOpacity: 1, cursor: 'pointer' },
                },
                series: {
                    regions: [{
                        scale: this._scale(),
                        values: this._values(),
                        // Linear normalisation — polynomial breaks on small
                        // datasets (single-buyer = NaN = black fill).
                        normalizeFunction: 'linear',
                        attribute: 'fill',
                    }],
                },
                onRegionTooltipShow: (event, tooltip, code) => {
                    const value = this._tooltipValue(code);
                    const name  = tooltip.text();
                    tooltip.text(value !== null
                        ? `${name}: USD ${value.toFixed(2)}`
                        : name, true);
                },
            });

            this.$watch('view', () => this._refresh());
            this.$watch('continent', () => this._refresh());
        },

        destroy() {
            if (this.map && typeof this.map.destroy === 'function') { this.map.destroy(); }
            this.map = null;
        },

        setView(v) { this.view = v; },
        setContinent(c) { this.continent = c; },

        // True when the country code belongs to the currently-selected
        // continent scope. Global ('all') passes everything.
        _inScope(code) {
            if (this.continent === 'all') { return true; }
            return CODE_TO_CONTINENT[code] === this.continent;
        },

        _scale() {
            // Two near-identical brand blues — jsvectormap collapses to black
            // when both ends of the scale are exactly equal, so we use a
            // hairline gradient (#1d4ed8 → #0044FF) that reads as uniform blue
            // but keeps the interpolator happy.
            return ['#1d4ed8', '#0044FF'];
        },

        _values() {
            // Country mode: shade per-country, filtered by continent scope.
            if (this.view === 'country') {
                const out = {};
                Object.keys(COUNTRIES).forEach((cc) => {
                    if (this._inScope(cc)) { out[cc] = COUNTRIES[cc]; }
                });
                return out;
            }
            // Region mode: every country in the same continent gets the
            // continent's aggregate value. When scoped to one continent,
            // only paint countries within it.
            const out = {};
            Object.keys(CODE_TO_CONTINENT).forEach((cc) => {
                if (!this._inScope(cc)) { return; }
                const region = CODE_TO_CONTINENT[cc];
                if (REGIONS[region] !== undefined) { out[cc] = REGIONS[region]; }
            });
            return out;
        },

        _tooltipValue(code) {
            if (!this._inScope(code)) { return null; }
            if (this.view === 'country') { return COUNTRIES[code] ?? null; }
            const region = CODE_TO_CONTINENT[code];
            return region ? (REGIONS[region] ?? null) : null;
        },

        _refresh() {
            if (!this.map) { return; }
            this.map.series.regions[0].setValues(this._values());
        },
    };
};

/**
 * Admin dashboard New Users bar chart — monthly registrations rendered as
 * a smooth rounded-bar chart by ApexCharts. Same lazy-import pattern as
 * salesCostChart. Series accepts `[{ label, value }, …]`.
 *
 *   x-data="newUsersChart(@js($newUsersSeries))"
 */
window.newUsersChart = function (series) {
    return {
        chart: null,
        series: series || [],

        async init() {
            const { default: ApexCharts } = await import('apexcharts');
            if (this.chart) { this.chart.destroy(); this.chart = null; }
            if (this.$refs.canvas) { this.$refs.canvas.innerHTML = ''; }
            this.chart = new ApexCharts(this.$refs.canvas, this._options());
            await this.chart.render();
        },

        destroy() {
            if (this.chart) { this.chart.destroy(); this.chart = null; }
            if (this.$refs.canvas) { this.$refs.canvas.innerHTML = ''; }
        },

        _options() {
            const dark = document.documentElement.classList.contains('dark');
            const gridColor = dark ? 'rgba(255,255,255,0.08)' : '#e5e7eb';
            const textColor = dark ? '#94a3b8' : '#52525b';
            const tooltipBg = dark ? 'rgba(38, 65, 107, 0.95)' : 'rgba(255,255,255,0.96)';
            const tooltipText = dark ? '#ffffff' : '#0f172a';

            return {
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                    background: 'transparent',
                    animations: { speed: 450, easing: 'easeout' },
                    parentHeightOffset: 0,
                },
                series: [{ name: 'New users', data: this.series.map((p) => p.value) }],
                colors: ['#2563eb'], // brand blue-600
                plotOptions: {
                    bar: {
                        borderRadius: 10,
                        borderRadiusApplication: 'end',
                        columnWidth: '38%',
                        distributed: false,
                    },
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        type: 'vertical',
                        shadeIntensity: 1,
                        gradientToColors: ['#3b82f6'],
                        opacityFrom: 1,
                        opacityTo: 0.85,
                        stops: [0, 100],
                    },
                },
                stroke: { show: false, width: 0 },
                xaxis: {
                    categories: this.series.map((p) => p.label),
                    labels: { style: { colors: textColor, fontSize: '11px', fontWeight: 500 } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: textColor, fontSize: '11px', fontWeight: 500 },
                        formatter: (v) => Number(v).toFixed(0),
                    },
                },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 4,
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } },
                    padding: { left: 0, right: 8 },
                },
                dataLabels: { enabled: false },
                legend: { show: false },
                tooltip: {
                    theme: dark ? 'dark' : 'light',
                    custom: ({ series, dataPointIndex, w }) => {
                        const label = w.globals.labels?.[dataPointIndex] ?? '';
                        const value = series[0]?.[dataPointIndex] ?? 0;
                        return `
                            <div style="padding:10px 14px;border-radius:12px;background:${tooltipBg};backdrop-filter:blur(8px);box-shadow:0 8px 24px -8px rgba(0,0,0,0.35);color:${tooltipText};">
                                <div style="font-size:11px;font-weight:500;opacity:0.7;margin-bottom:4px;">${label}</div>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="height:8px;width:8px;border-radius:9999px;background:#2563eb;display:inline-block;"></span>
                                    <span style="font-size:12px;font-weight:600;">${value} new user${value === 1 ? '' : 's'}</span>
                                </div>
                            </div>`;
                    },
                },
            };
        },
    };
};

/**
 * Glassmorphism tooltip renderer shared by the admin dashboard's Trends chart
 * (salesCostChart) and the /admin/reports chart (reportChart). Builds an
 * ApexCharts `tooltip.custom` HTML string that uses backdrop-blur + low-alpha
 * background + an inset white border for the "frosted glass" look.
 *
 * Returns a closure ApexCharts can call directly. Theme-aware: pass the
 * `dark` boolean (typically `document.documentElement.classList.contains('dark')`)
 * so colours adapt.
 */
function buildGlassTooltipRenderer(dark) {
    // Inverted glass: dark mode shows a WHITE-tinted frosted panel; light mode
    // shows a DARK-tinted frosted panel. Both keep light text so legibility
    // stays consistent regardless of the chart area behind the tooltip.
    const panelBg = dark ? 'rgba(255, 255, 255, 0.10)' : 'rgba(15, 23, 42, 0.22)';
    const panelBorder = dark ? 'rgba(255, 255, 255, 0.22)' : 'rgba(15, 23, 42, 0.28)';
    const insetHighlight = dark ? 'rgba(255, 255, 255, 0.25)' : 'rgba(255, 255, 255, 0.18)';
    const labelColor = 'rgba(255, 255, 255, 0.72)';
    const valueColor = '#ffffff';
    const dateColor = 'rgba(255, 255, 255, 0.55)';

    return ({ series, dataPointIndex, w }) => {
        // Apex stores datetime-xaxis labels as raw ms-since-epoch strings (e.g.
        // "1779753600000"). Try categoryLabels / labels first, then detect a
        // numeric timestamp and humanise it, then fall back to seriesX.
        let dateLabel = w.globals.categoryLabels?.[dataPointIndex]
            ?? w.globals.labels?.[dataPointIndex]
            ?? '';

        if (dateLabel && /^\d{10,}$/.test(String(dateLabel))) {
            try {
                dateLabel = new Date(Number(dateLabel)).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
            } catch (e) { /* keep raw label */ }
        }

        if (!dateLabel && w.globals.seriesX?.[0]?.[dataPointIndex]) {
            const ts = w.globals.seriesX[0][dataPointIndex];
            try {
                dateLabel = new Date(ts).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
            } catch (e) { /* swallow — header just won't show */ }
        }

        const rows = w.globals.seriesNames.map((name, idx) => {
            const colour = w.globals.colors[idx];
            const value = series[idx]?.[dataPointIndex];
            if (value === undefined || value === null) { return ''; }
            // Halo behind the dot via `box-shadow` so we can recolour per-series
            // without an extra wrapping element. `${colour}33` = colour @ 20% alpha.
            return `
                <div style="display:flex;align-items:center;gap:7px;padding:1px 0;">
                    <span style="height:6px;width:6px;border-radius:9999px;background:${colour};box-shadow:0 0 0 2px ${colour}30;display:inline-block;flex-shrink:0;"></span>
                    <span style="font-size:10px;font-weight:500;color:${labelColor};letter-spacing:0.01em;">${name}</span>
                    <span style="font-size:11px;font-weight:700;color:${valueColor};margin-left:10px;font-variant-numeric:tabular-nums;letter-spacing:-0.01em;">USD ${Number(value).toFixed(2)}</span>
                </div>`;
        }).join('');

        const header = dateLabel
            ? `<p style="margin:0 0 4px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:${dateColor};">${dateLabel}</p>`
            : '';

        return `<div style="
            padding:7px 9px;
            border-radius:10px;
            background:${panelBg};
            backdrop-filter:blur(16px) saturate(180%);
            -webkit-backdrop-filter:blur(16px) saturate(180%);
            border:1px solid ${panelBorder};
            box-shadow:
                0 8px 20px rgba(0, 0, 0, 0.32),
                inset 0 1px 0 ${insetHighlight};
        ">${header}${rows}</div>`;
    };
}

window.salesCostChart = function (series) {
    const SALES_COLOR = '#34d399'; // emerald-400
    const COST_COLOR  = '#60a5fa'; // blue-400

    return {
        chart: null,
        mode: 'both',            // 'both' | 'sales' | 'cost'
        series: series || [],
        salesColor: SALES_COLOR,
        costColor: COST_COLOR,

        async init() {
            const { default: ApexCharts } = await import('apexcharts');
            // Defensive: if Livewire's wire:navigate re-mounts this card an
            // older ApexCharts SVG may still be sitting inside $refs.canvas.
            // Clear it before mounting a new one so we never stack charts.
            if (this.chart) { this.chart.destroy(); this.chart = null; }
            if (this.$refs.canvas) { this.$refs.canvas.innerHTML = ''; }
            this.chart = new ApexCharts(this.$refs.canvas, this._options());
            await this.chart.render();
            this.$watch('mode', () => this._update());
        },

        destroy() {
            if (this.chart) { this.chart.destroy(); this.chart = null; }
            if (this.$refs.canvas) { this.$refs.canvas.innerHTML = ''; }
        },

        setMode(mode) { this.mode = mode; },

        modeLabel() {
            return this.mode === 'sales' ? 'Sales' : (this.mode === 'cost' ? 'Cost' : 'Sales / Cost');
        },

        _update() {
            const wanted = this.mode === 'both' ? ['Sales', 'Cost'] : (this.mode === 'sales' ? ['Sales'] : ['Cost']);
            ['Sales', 'Cost'].forEach((name) => {
                if (wanted.includes(name)) { this.chart.showSeries(name); }
                else                        { this.chart.hideSeries(name); }
            });
        },

        _options() {
            const dark = document.documentElement.classList.contains('dark');
            const gridColor = dark ? 'rgba(255,255,255,0.08)' : '#e5e7eb';
            const textColor = dark ? '#94a3b8' : '#52525b';
            const renderGlassTooltip = buildGlassTooltipRenderer(dark);

            return {
                chart: {
                    type: 'area',
                    height: 320,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    fontFamily: 'inherit',
                    background: 'transparent',
                    animations: { speed: 450, easing: 'easeout' },
                    parentHeightOffset: 0,
                },
                series: [
                    { name: 'Sales', data: this.series.map((p) => [new Date(p.date).getTime(), p.sales]) },
                    { name: 'Cost',  data: this.series.map((p) => [new Date(p.date).getTime(), p.cost])  },
                ],
                colors: [SALES_COLOR, COST_COLOR],
                stroke: { curve: 'smooth', width: 2.5, lineCap: 'round' },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.30, opacityTo: 0.0, stops: [0, 90, 100] },
                },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        style: { colors: textColor, fontSize: '11px', fontWeight: 500 },
                        datetimeFormatter: { day: 'MMM dd, ddd' },
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    crosshairs: {
                        show: true,
                        width: 1,
                        position: 'back',
                        stroke: { color: dark ? '#475569' : '#94a3b8', width: 1, dashArray: 0 },
                    },
                    tooltip: { enabled: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: textColor, fontSize: '11px', fontWeight: 500 },
                        formatter: (v) => 'USD ' + Number(v).toFixed(2),
                    },
                },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 4,
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } },
                    padding: { left: 0, right: 12, top: 0, bottom: 0 },
                },
                dataLabels: { enabled: false },
                legend: { show: false },
                tooltip: {
                    theme: dark ? 'dark' : 'light',
                    shared: true,
                    intersect: false,
                    followCursor: false,
                    fixed: { enabled: false },
                    marker: { show: true },
                    style: { fontSize: '12px' },
                    x: { format: 'MMM dd, yyyy' },
                    y: { formatter: (v) => 'USD ' + Number(v).toFixed(2) },
                    custom: renderGlassTooltip,
                },
                markers: {
                    size: 0,
                    strokeWidth: 2,
                    strokeColors: dark ? '#1d3252' : '#ffffff',
                    hover: { size: 6, sizeOffset: 2 },
                },
            };
        },
    };
};

/**
 * Global confirm-modal wiring. Two pieces:
 *
 * 1. `confirmModal()` — Alpine factory bound to <x-confirm-modal />, owns the
 *    open/cancel/confirm state and replays the original action when confirmed.
 * 2. A capture-phase listener for forms / buttons carrying `data-confirm="..."`
 *    that preempts the action and fires a `confirm-show` event the modal picks
 *    up. Marking the element with `data-confirmed="true"` lets the replayed
 *    submit/click pass straight through without re-triggering the modal.
 *
 * Why capture-phase and `stopImmediatePropagation`: third-party listeners (e.g.
 * Livewire/Volt) already attach to submit/click in the bubble phase. We must
 * intercept BEFORE them and silence the chain so the network request never
 * fires until the admin/user actually confirms.
 *
 *   <form data-confirm="Delete row?" data-confirm-tone="danger" ...>
 *   <button data-confirm="Continue?" @click="..."> ...
 */
window.confirmModal = function () {
    return {
        isOpen: false,
        title: 'Are you sure?',
        message: '',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        tone: 'danger',       // 'danger' | 'warning' | 'primary' | 'success'
        target: null,         // element to submit/click on confirm

        open(detail) {
            this.title = detail.title || 'Are you sure?';
            this.message = detail.message || '';
            this.confirmText = detail.confirmText || 'Confirm';
            this.cancelText = detail.cancelText || 'Cancel';
            this.tone = detail.tone || 'danger';
            this.target = detail.target || null;
            this.isOpen = true;

            // Auto-focus the confirm button so Enter completes the action and
            // Escape (bound on the panel) cancels — keyboard-only flow stays cheap.
            this.$nextTick(() => this.$refs.confirmBtn?.focus());
        },

        cancel() {
            this.isOpen = false;
            this.target = null;
        },

        confirm() {
            const el = this.target;
            this.isOpen = false;
            this.target = null;

            if (!el) { return; }

            // Replay the original action. `data-confirmed` shortcircuits our own
            // listener so the submit/click goes through this time.
            el.dataset.confirmed = 'true';

            if (el.tagName === 'FORM') {
                // .submit() bypasses any onsubmit handler — that's fine because
                // we already gated it; the form's normal action runs.
                el.submit();
            } else {
                el.click();
            }
        },
    };
};

/**
 * /admin/reports chart. Reuses the `series` shape coming out of
 * DashboardMetricsQuery::getReportSeries — one row per bucket carrying
 * `date / sales_usd / cost_usd`. Supports two chart types selected by the
 * page's Bar / Line toggle:
 *
 *   - line: smooth area chart (same look as the dashboard's Trends widget)
 *   - bar:  vertical columns, sales + cost side by side
 *
 *   x-data="reportChart(@js($this->series), @js($chartType))"
 *
 * The Volt component re-mounts the wrapper via `wire:key` whenever the
 * chart type / granularity / category changes, so init() always runs against
 * a fresh DOM node — no need for an in-place rebuild hook.
 */
window.reportChart = function (series, chartType) {
    const SALES_COLOR = '#34d399'; // emerald-400
    const COST_COLOR = '#60a5fa';  // blue-400

    return {
        chart: null,
        series: series || [],
        chartType: chartType || 'line',

        async init() {
            if (!this.series.length || !this.$refs.canvas) { return; }
            const { default: ApexCharts } = await import('apexcharts');
            this.$refs.canvas.innerHTML = '';
            this.chart = new ApexCharts(this.$refs.canvas, this._options());
            await this.chart.render();
        },

        destroy() {
            if (this.chart) { this.chart.destroy(); this.chart = null; }
            if (this.$refs.canvas) { this.$refs.canvas.innerHTML = ''; }
        },

        _options() {
            const dark = document.documentElement.classList.contains('dark');
            const gridColor = dark ? 'rgba(255,255,255,0.08)' : '#e5e7eb';
            const textColor = dark ? '#94a3b8' : '#52525b';
            const isBar = this.chartType === 'bar';
            const renderGlassTooltip = buildGlassTooltipRenderer(dark);

            const base = {
                chart: {
                    type: isBar ? 'bar' : 'area',
                    height: 320,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    fontFamily: 'inherit',
                    background: 'transparent',
                    animations: { speed: 450, easing: 'easeout' },
                    parentHeightOffset: 0,
                },
                series: [
                    { name: 'Sales', data: this.series.map((p) => [new Date(p.date).getTime(), p.sales_usd]) },
                    { name: 'Cost', data: this.series.map((p) => [new Date(p.date).getTime(), p.cost_usd]) },
                ],
                colors: [SALES_COLOR, COST_COLOR],
                xaxis: {
                    type: 'datetime',
                    labels: {
                        style: { colors: textColor, fontSize: '11px', fontWeight: 500 },
                        datetimeFormatter: { day: 'MMM dd, ddd' },
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    tooltip: { enabled: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: textColor, fontSize: '11px', fontWeight: 500 },
                        formatter: (v) => 'USD ' + Number(v).toFixed(2),
                    },
                },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 4,
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } },
                    padding: { left: 0, right: 12, top: 0, bottom: 0 },
                },
                dataLabels: { enabled: false },
                legend: { show: false },
                tooltip: {
                    theme: dark ? 'dark' : 'light',
                    shared: true,
                    intersect: false,
                    x: { format: 'MMM dd, yyyy' },
                    y: { formatter: (v) => 'USD ' + Number(v).toFixed(2) },
                    custom: renderGlassTooltip,
                },
            };

            if (isBar) {
                return {
                    ...base,
                    plotOptions: {
                        bar: { borderRadius: 6, borderRadiusApplication: 'end', columnWidth: '55%' },
                    },
                    stroke: { show: false, width: 0 },
                };
            }

            return {
                ...base,
                stroke: { curve: 'smooth', width: 2.5, lineCap: 'round' },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.30, opacityTo: 0.0, stops: [0, 90, 100] },
                },
                markers: {
                    size: 0,
                    strokeWidth: 2,
                    strokeColors: dark ? '#1d3252' : '#ffffff',
                    hover: { size: 6, sizeOffset: 2 },
                },
            };
        },
    };
};

(function installConfirmDispatcher() {
    if (typeof window === 'undefined' || typeof document === 'undefined') { return; }

    function dispatch(target, source) {
        window.dispatchEvent(new CustomEvent('confirm-show', {
            detail: {
                title: source.dataset.confirmTitle || 'Are you sure?',
                message: source.dataset.confirm,
                confirmText: source.dataset.confirmText || 'Confirm',
                cancelText: source.dataset.confirmCancel || 'Cancel',
                tone: source.dataset.confirmTone || 'danger',
                target,
            },
        }));
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) { return; }
        if (!form.dataset.confirm) { return; }
        if (form.dataset.confirmed === 'true') {
            // Replayed by the modal — clear the flag and let the submit through.
            delete form.dataset.confirmed;
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        dispatch(form, form);
    }, true);

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-confirm]');
        if (!btn) { return; }
        // Skip submit buttons — the form's submit handler above owns those, so
        // we don't double-prompt when the button lives inside a confirmed form.
        if (btn.tagName === 'BUTTON' && btn.type === 'submit') { return; }
        if (btn.tagName === 'FORM') { return; } // handled by the submit listener
        if (btn.dataset.confirmed === 'true') {
            delete btn.dataset.confirmed;
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        dispatch(btn, btn);
    }, true);
})();
