// Flatpickr — modern date picker used by admin forms (e.g. coupon expiry).
// Exposed on window so Alpine x-init expressions can call flatpickr() directly.
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';

window.flatpickr = flatpickr;

/**
 * Build a custom dropdown (trigger button + styled options panel) to replace one
 * of Flatpickr's native month/year selectors so they match the admin's other
 * custom dropdowns. The panel is appended to <body> and fixed-positioned so
 * Flatpickr's `overflow:hidden` month strip can't clip it.
 *
 * @param {Array<{value:number,label:string}>} items
 * @param {number} current  currently selected value
 * @param {(value:number)=>void} onPick
 * @returns {{btn:HTMLElement, setValue:(v:number)=>void, cleanup:()=>void}}
 */
function buildFlatpickrDropdown(items, current, onPick) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'flatpickr-modernSelect-btn';
    const label = document.createElement('span');
    label.textContent = items.find((i) => i.value === current)?.label ?? '';
    btn.appendChild(label);
    btn.insertAdjacentHTML('beforeend', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flatpickr-modernSelect-chev"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>');

    const panel = document.createElement('div');
    panel.className = 'flatpickr-modernSelect-panel';
    const mark = (v) => panel.querySelectorAll('.flatpickr-modernSelect-opt').forEach((o) => o.classList.toggle('is-selected', Number(o.dataset.value) === v));
    const hide = () => panel.classList.remove('is-open');

    items.forEach((it) => {
        const opt = document.createElement('button');
        opt.type = 'button';
        opt.className = 'flatpickr-modernSelect-opt';
        opt.dataset.value = String(it.value);
        opt.textContent = it.label;
        if (it.value === current) opt.classList.add('is-selected');
        opt.addEventListener('click', (e) => {
            e.stopPropagation();
            onPick(it.value);
            label.textContent = it.label;
            mark(it.value);
            hide();
        });
        panel.appendChild(opt);
    });
    document.body.appendChild(panel);

    const show = () => {
        const r = btn.getBoundingClientRect();
        panel.style.top = `${r.bottom + 4}px`;
        panel.style.left = `${r.left + r.width / 2}px`;
        panel.classList.add('is-open');
        panel.querySelector('.is-selected')?.scrollIntoView({ block: 'nearest' });
    };
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        panel.classList.contains('is-open') ? hide() : show();
    });
    document.addEventListener('click', hide);

    return {
        btn,
        setValue: (v) => {
            const it = items.find((i) => i.value === v);
            if (it) label.textContent = it.label;
            mark(v);
        },
        cleanup: () => {
            document.removeEventListener('click', hide);
            panel.remove();
        },
    };
}

/** Swap Flatpickr's native month <select> and numeric year stepper for custom dropdowns. */
function mountFlatpickrDropdowns(fp, range = 10) {
    if (fp.__modernMounted) return;
    fp.__modernMounted = true;
    const controllers = [];

    const monthSelect = fp.monthsDropdownContainer || fp.calendarContainer.querySelector('.flatpickr-monthDropdown-months');
    if (monthSelect) {
        monthSelect.style.display = 'none';
        const months = fp.l10n.months.longhand.map((label, value) => ({ value, label }));
        const monthCtl = buildFlatpickrDropdown(months, fp.currentMonth, (m) => fp.changeMonth(m - fp.currentMonth));
        monthSelect.parentNode.insertBefore(monthCtl.btn, monthSelect.nextSibling);
        fp.__monthCtl = monthCtl;
        controllers.push(monthCtl);
    }

    const yearWrapper = fp.currentYearElement?.closest('.numInputWrapper');
    if (yearWrapper) {
        yearWrapper.style.display = 'none';
        const base = new Date().getFullYear();
        const years = [];
        for (let y = base; y <= base + range; y++) years.push({ value: y, label: String(y) });
        const yearCtl = buildFlatpickrDropdown(years, fp.currentYear, (y) => fp.changeYear(y));
        yearWrapper.parentNode.insertBefore(yearCtl.btn, yearWrapper.nextSibling);
        fp.__yearCtl = yearCtl;
        controllers.push(yearCtl);
    }

    fp.__cleanupDropdowns = () => controllers.forEach((c) => c.cleanup());
}

function syncFlatpickrDropdowns(fp) {
    fp.__monthCtl?.setValue(fp.currentMonth);
    fp.__yearCtl?.setValue(fp.currentYear);
}

/**
 * Flatpickr for an expiry date field: date-only, future dates only, with custom
 * month + year dropdowns. `onSelect` receives the chosen Date (or null cleared).
 */
window.initExpiryFlatpickr = function (el, onSelect) {
    return window.flatpickr(el, {
        minDate: 'today',
        dateFormat: 'M j, Y',
        monthSelectorType: 'dropdown',
        disableMobile: true,
        onChange: (dates) => onSelect(dates[0] || null),
        onReady: (_s, _d, fp) => mountFlatpickrDropdowns(fp),
        onYearChange: (_s, _d, fp) => syncFlatpickrDropdowns(fp),
        onMonthChange: (_s, _d, fp) => syncFlatpickrDropdowns(fp),
        onDestroy: (_s, _d, fp) => fp.__cleanupDropdowns?.(),
    });
};

/**
 * Looping typewriter for the dashboard greeting: types a phrase, holds, deletes,
 * then moves to the next phrase and repeats. Used via:
 *   x-data="typewriterGreeting(['Hi Roddy', 'Welcome', 'Your shop is ready'])"
 * with <span x-text="display"></span>.
 */
window.typewriterGreeting = function (phrases, { typeMs = 80, deleteMs = 40, holdMs = 10000, pauseMs = 400 } = {}) {
    return {
        phrases,
        display: '',
        _i: 0,
        _c: 0,
        _timer: null,
        init() {
            this._type();
        },
        _type() {
            const word = this.phrases[this._i] ?? '';
            if (this._c <= word.length) {
                this.display = word.slice(0, this._c);
                this._c++;
                this._timer = setTimeout(() => this._type(), typeMs);
            } else {
                this._timer = setTimeout(() => this._delete(), holdMs);
            }
        },
        _delete() {
            const word = this.phrases[this._i] ?? '';
            if (this._c > 0) {
                this._c--;
                this.display = word.slice(0, this._c);
                this._timer = setTimeout(() => this._delete(), deleteMs);
            } else {
                this._i = (this._i + 1) % this.phrases.length;
                this._timer = setTimeout(() => this._type(), pauseMs);
            }
        },
        destroy() {
            clearTimeout(this._timer);
        },
    };
};

/**
 * Entrance + scroll-reveal animations with GSAP.
 *
 * GSAP is loaded via dynamic import so the page still works if the package
 * isn't installed yet. Run `npm i gsap` to enable animations.
 */
async function initAnimations() {
    let gsap, ScrollTrigger;
    try {
        ({ default: gsap } = await import('gsap'));
        ({ ScrollTrigger } = await import('gsap/ScrollTrigger'));
        gsap.registerPlugin(ScrollTrigger);
    } catch (err) {
        console.warn('[animations] gsap not available — skipping animations. Run `npm i gsap`.');
        return;
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

    // Recalculate positions after the page settles.
    requestAnimationFrame(() => ScrollTrigger.refresh());
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
    // On mobile, focusing an input opens the on-screen keyboard, which fires a
    // scroll event. We must NOT dispatch the synthetic Escape in that case -
    // it would close the auth modal (and any open dialog) the instant the user
    // taps a field. Only auto-close dropdowns on genuine page scrolls.
    const isEditing = () => {
        const el = document.activeElement;
        return !! el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT' || el.isContentEditable);
    };
    window.addEventListener('scroll', () => {
        clearTimeout(t);
        t = setTimeout(() => {
            // Skip the close-Escape when:
            //  1. a field is focused (mobile keyboard opening fires a scroll), or
            //  2. a modal has locked body scroll (rshopScrollLock sets
            //     body.position:fixed) - while locked the page isn't really
            //     scrolling, so any scroll event is spurious (rubber-band /
            //     keyboard) and would otherwise close the open modal.
            if (isEditing() || document.body.style.position === 'fixed') { return; }
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
 * Open Google OAuth in a centred popup window instead of full-page redirect.
 * The popup lands on /auth/popup-complete after sign-in (success or failure);
 * that page postMessages back here and closes itself. We listen for the
 * message — on success we navigate to the dashboard, on cancel we just stay
 * on the current page. Popup-blocker fallback: if window.open returns null,
 * we navigate to the URL in the current tab so the user can still sign in.
 */
window.rshopOpenGoogleOAuth = function (href) {
    const w = 500, h = 650;
    const dualLeft  = (window.screenLeft !== undefined ? window.screenLeft : window.screenX) || 0;
    const dualTop   = (window.screenTop  !== undefined ? window.screenTop  : window.screenY) || 0;
    const winWidth  = window.innerWidth  || document.documentElement.clientWidth  || screen.width;
    const winHeight = window.innerHeight || document.documentElement.clientHeight || screen.height;
    const left = dualLeft + (winWidth  - w) / 2;
    const top  = dualTop  + (winHeight - h) / 2;
    const popup = window.open(href, 'rshop-google-oauth', `width=${w},height=${h},left=${left},top=${top},resizable=yes,scrollbars=yes`);
    if (! popup) {
        // Popup blocked - fall back to a normal redirect so the user can still sign in.
        window.location.href = href;
        return;
    }
    popup.focus();
    const onMessage = (event) => {
        if (event.origin !== window.location.origin) { return; }
        const data = event.data;
        if (! data || data.source !== 'rshop-google-oauth') { return; }
        window.removeEventListener('message', onMessage);
        if (data.status === 'success') {
            // Match the original full-redirect destination.
            window.location.href = '/dashboard';
        }
        // status === 'cancelled' leaves the user on the current page.
    };
    window.addEventListener('message', onMessage);
};

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
        tx: 0,
        maxTx: 0,
        cardW: 288,
        gap: 20,
        padLeft: 0,
        savedDuration: '0.6s',
        dragArmed: false,
        dragging: false,
        dragTarget: null,
        dragPointerId: null,
        dragStartX: 0,
        dragStartTx: 0,

        // Wire up the transform-based carousel: measure the content column
        // padding, compute scroll bounds, snap to start. Matches the brand-row
        // factory so both rows feel identical.
        setup() {
            const viewport = this.$refs.track;
            const list     = this.$refs.list;
            if (! viewport || ! list) return;

            const card   = list.querySelector('article');
            const header = this.$refs.header;
            this.cardW   = card ? card.offsetWidth : 288;
            this.gap     = window.innerWidth >= 640 ? 20 : 16;
            // Measure the header content column (not the full-bleed section)
            // so the first review card lines up with the "What our customers
            // say" title on every viewport, including mobile.
            this.padLeft = header
                ? Math.round(header.getBoundingClientRect().left)
                : Math.round(this.$el.getBoundingClientRect().left);
            list.style.paddingLeft = this.padLeft + 'px';
            this.tx = 0;
            this.maxTx = Math.min(0, viewport.clientWidth - list.scrollWidth);
            list.style.transform = 'translate3d(0, 0, 0)';

            // Slow the slide down on long rows so a 20-card carousel doesn't
            // feel hurried. Base 0.6s, +0.04s per item beyond 5, capped at 1.2s.
            const itemCount = list.children.length;
            const duration  = Math.min(1.2, 0.6 + 0.04 * Math.max(0, itemCount - 5));
            this.savedDuration = duration.toFixed(2) + 's';
            list.style.transitionDuration = this.savedDuration;
        },

        // Advance 2 cards per click on desktop / tablet, 1 on mobile. Loops back
        // to the start once the end is reached (the original reviews-carousel
        // contract; lets the row run forever from a single Next button).
        next() {
            const list = this.$refs.list;
            if (! list) return;

            if (this.tx <= this.maxTx + 1) {
                this.tx = 0;
                list.style.transform = 'translate3d(0, 0, 0)';
                return;
            }

            const cardsPerStep = window.innerWidth >= 640 ? 2 : 1;
            const step = cardsPerStep * (this.cardW + this.gap);
            this.tx = Math.max(this.maxTx, this.tx - step);
            list.style.transform = `translate3d(${this.tx}px, 0, 0)`;
        },

        // Touch / pointer drag for mobile + trackpad. Matches the brand-row
        // implementation so both carousels feel identical when swiped.
        //
        // 8px movement threshold gates the drag commitment. Below it, the
        // gesture is treated as a click - we do not capture the pointer,
        // do not move the list, and do not snap on release - so reviews
        // (and any clickable card inside the row) receive a clean click.
        onDragStart(e) {
            const list = this.$refs.list;
            if (! list) return;
            if (e.button !== undefined && e.button !== 0) return;
            this.dragArmed = true;
            this.dragging = false;
            this.dragTarget = e.currentTarget;
            this.dragPointerId = e.pointerId;
            this.dragStartX = e.clientX;
            this.dragStartTx = this.tx;
        },
        onDragMove(e) {
            if (! this.dragArmed) return;
            const list = this.$refs.list;
            if (! list) return;
            const delta = e.clientX - this.dragStartX;

            if (! this.dragging && Math.abs(delta) < 8) return;

            if (! this.dragging) {
                this.dragging = true;
                list.style.transitionDuration = '0s';
                try { this.dragTarget?.setPointerCapture(this.dragPointerId); } catch (_) {}
            }

            const raw = this.dragStartTx + delta;
            const next = raw > 0
                ? raw * 0.35
                : (raw < this.maxTx ? this.maxTx + (raw - this.maxTx) * 0.35 : raw);
            list.style.transform = `translate3d(${next}px, 0, 0)`;
        },
        onDragEnd(e) {
            if (! this.dragArmed) return;
            const wasDragging = this.dragging;
            this.dragArmed = false;
            this.dragging = false;

            if (! wasDragging) return;

            const list = this.$refs.list;
            if (! list) return;
            list.style.transitionDuration = this.savedDuration;
            const delta = e.clientX - this.dragStartX;
            const step  = this.cardW + this.gap;
            const projected = this.dragStartTx + delta;
            const snapped   = Math.round(projected / step) * step;
            this.tx = Math.max(this.maxTx, Math.min(0, snapped));
            list.style.transform = `translate3d(${this.tx}px, 0, 0)`;
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

            // NOTE: the locale modal dispatches `language-changed` itself when a
            // language is picked (works regardless of which Alpine scope the modal
            // is mounted in - storefront vs dashboard). We intentionally do NOT
            // dispatch it from this watcher to avoid a double trigger / double reload.
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
    // codeToContinent is reference data (ISO -> continent); never changes per
    // request, so it stays as a const captured at init. The countries/regions
    // payloads DO change when the Period or Product filter is clicked - they
    // live on `this` so updateData() can mutate them without re-initializing
    // the map (the rendered SVG would be lost on a re-init).
    const CODE_TO_CONTINENT = payload.codeToContinent || {};

    return {
        map: null,
        countries: payload.countries || {},
        regions: payload.regions || {},
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

        // Called from the dashboard's Livewire layer (via the
        // map-data-updated browser event) when the Period or Product filter
        // changes. Swaps in the new aggregates and re-shades the SVG in
        // place - the map stays interactive throughout.
        //
        // Livewire 3 wraps named params in different detail shapes depending
        // on how the event is consumed: sometimes `{ params: [{ ... }] }`,
        // sometimes the params spread directly onto detail. We accept both.
        updateData(detail) {
            if (! detail) return;
            const payload = Array.isArray(detail?.params)
                ? (detail.params[0] || {})
                : detail;
            this.countries = payload.countries || {};
            this.regions   = payload.regions   || {};
            this._refresh();
        },

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
                Object.keys(this.countries).forEach((cc) => {
                    if (this._inScope(cc)) { out[cc] = this.countries[cc]; }
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
                if (this.regions[region] !== undefined) { out[cc] = this.regions[region]; }
            });
            return out;
        },

        _tooltipValue(code) {
            if (!this._inScope(code)) { return null; }
            if (this.view === 'country') { return this.countries[code] ?? null; }
            const region = CODE_TO_CONTINENT[code];
            return region ? (this.regions[region] ?? null) : null;
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
