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

window.storefrontLocale = function () {
    // Pages that read country/currency from the URL. The listing and every brand-level
    // detail page (e.g. /gift-cards/apple) both honour ?country=XX so flipping the locale
    // modal reloads either one with the new filters applied.
    const SHOP_PATH_PREFIXES = ['/gift-cards'];

    const isShopPath = (path) => {
        return SHOP_PATH_PREFIXES.some((p) => path === p || path.startsWith(p + '/'));
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
                if (!SHOP_PATHS_WITH_FILTERS.includes(path)) return;

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
        },
    };
};
