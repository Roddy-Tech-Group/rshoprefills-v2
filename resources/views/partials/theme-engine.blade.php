{{-- Theme engine — light / dark / system. Shared by every layout's <head>.
     Resolves the theme choice (default 'system') and toggles the `.dark` class
     on <html> BEFORE first paint, so there is no light/dark flash.
     Re-applies after every wire:navigate DOM swap, and follows the OS in real
     time while the choice is 'system'. `window.setTheme(choice)` is called by
     the settings theme picker and the <x-theme-toggle> buttons.

     Per-account: the signed-in account's saved theme (users.theme / admins.theme)
     is rendered below and is authoritative — it primes localStorage so the
     preference follows the account onto any device, and every toggle change is
     POSTed back so it persists. Guests fall back to localStorage + the OS.

     The admin panel keeps its own theme independent of the customer side: pages
     under /admin use the 'admin.theme' storage key + the admin endpoint;
     everything else uses 'theme' + the customer endpoint. The key is resolved
     per-call from the current path so it stays correct across wire:navigate.
     Read the current choice via window.themeChoice(). --}}
@php
    // Resolve the account + persistence endpoint for the area being rendered.
    // Path-based (not just guard-based) so it matches the JS storage key even
    // when an admin and a customer are signed in on the same browser.
    $themeIsAdminArea = request()->is('admin*');
    $themeAccount = $themeIsAdminArea ? auth('admin')->user() : auth('web')->user();
    $themeServerChoice = $themeAccount?->theme;
    $themeEndpoint = $themeAccount
        ? ($themeIsAdminArea ? route('admin.theme') : route('preferences.theme'))
        : null;
@endphp
<script>
    (function () {
        const root = document.documentElement;

        // Account theme + endpoint for this area (null for guests → local only).
        const cfg = {
            server: @json($themeServerChoice),
            endpoint: @json($themeEndpoint),
            csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        };

        // Admin and customer areas share an origin (and thus localStorage), so
        // each gets its own key — toggling one must never affect the other.
        function themeKey() {
            return location.pathname.startsWith('/admin') ? 'admin.theme' : 'theme';
        }
        function themeChoice() {
            return localStorage.getItem(themeKey()) || 'system';
        }

        function prefersDark() {
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        function resolveDark() {
            const choice = themeChoice();
            if (choice === 'dark') return true;
            if (choice === 'light') return false;
            return prefersDark();
        }
        function applyTheme() {
            root.classList.toggle('dark', resolveDark());
        }

        // Persist the choice to the signed-in account so it follows them across
        // devices. Guests have no endpoint and stay local-only.
        function persist(choice) {
            if (! cfg.endpoint || ! cfg.csrf) {
                return;
            }
            fetch(cfg.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrf,
                },
                body: JSON.stringify({ theme: choice }),
                keepalive: true,
            }).catch(function () {});
        }

        // Theme picker / toggles call this to persist + apply a new choice.
        window.setTheme = function (choice) {
            try { localStorage.setItem(themeKey(), choice); } catch (_) {}
            applyTheme();
            // Let any on-page toggle re-sync its icon.
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark: resolveDark() } }));
            persist(choice);
        };
        window.themeIsDark = resolveDark;
        window.themeChoice = themeChoice;

        // The account's saved theme wins over a stale cache from another login.
        if (cfg.server) {
            try { localStorage.setItem(themeKey(), cfg.server); } catch (_) {}
        }

        applyTheme(); // before first paint — no flash

        // Track the OS while the choice is 'system'.
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
            if (themeChoice() === 'system') {
                applyTheme();
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark: resolveDark() } }));
            }
        });

        // Page transition — the incoming page slides up on navigation.
        let firstLoad = true;
        function playPageTransition() {
            const main = document.querySelector('main');
            if (! main) return;
            main.classList.add('page-entering');
            void main.offsetWidth; // commit the offset before transitioning back
            main.classList.remove('page-entering');
        }
        document.addEventListener('livewire:navigated', function () {
            applyTheme(); // re-assert after the DOM swap
            if (firstLoad) { firstLoad = false; return; }
            playPageTransition();
        });
    })();
</script>
