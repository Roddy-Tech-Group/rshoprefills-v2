{{--
    Global keyboard shortcuts - customer-facing, site-wide (storefront + dashboard
    + auth pages). Excluded from /admin, which runs its own chrome. The keydown
    listener is registered once on full page load and survives wire:navigate (the
    <head> is not re-executed on SPA swaps), so it never double-binds.

    Mapping (Windows Ctrl / macOS Cmd):
      Ctrl+J  Chat on WhatsApp     - the original ask was Ctrl+T, but browsers
                                     reserve Ctrl+T for "new tab" and a page cannot
                                     intercept it, so WhatsApp lives on Ctrl+J.
      Ctrl+F  Create support ticket (contact page) - overrides browser Find.
      Ctrl+M  Quick light / dark theme toggle.
      Ctrl+P  Profile settings (login when signed out) - overrides browser Print.
      Ctrl+K  Search - owned by the nav search box, intentionally NOT handled here.

    Overriding Find + Print site-wide is a deliberate product decision (confirmed).

    NOTE: never write an at-sign-prefixed Blade directive name inside this script
    body, even in a comment - Blade still expands directives inside script blocks.
--}}
@unless (request()->is('admin*'))
<script>
    (function () {
        const WHATSAPP = 'https://wa.me/19402386229?text=Hello%20Rshoprefill%20can%20i%20get%20help%3F';
        const TICKET   = @json(route('shop.contact'));
        const PROFILE  = @json(auth('web')->check() ? route('dashboard.profile') : route('login'));

        // SPA-navigate when Livewire is present so in-app jumps stay instant;
        // hard-load otherwise (and for cross-area hops).
        function go(url) {
            if (window.Livewire && typeof window.Livewire.navigate === 'function') {
                window.Livewire.navigate(url);
            } else {
                window.location.assign(url);
            }
        }

        document.addEventListener('keydown', function (e) {
            // Require exactly Ctrl (or Cmd on macOS); bail when Alt or Shift are
            // also held so we never clash with browser / devtools combos.
            if (! (e.ctrlKey || e.metaKey) || e.altKey || e.shiftKey) {
                return;
            }

            switch ((e.key || '').toLowerCase()) {
                case 'j': // Chat on WhatsApp
                    e.preventDefault();
                    window.open(WHATSAPP, '_blank', 'noopener');
                    break;
                case 'f': // Create a support ticket
                    e.preventDefault();
                    go(TICKET);
                    break;
                case 'm': // Quick light / dark toggle
                    e.preventDefault();
                    if (typeof window.setTheme === 'function') {
                        window.setTheme(window.themeIsDark && window.themeIsDark() ? 'light' : 'dark');
                    }
                    break;
                case 'p': // Profile settings
                    e.preventDefault();
                    go(PROFILE);
                    break;
            }
        });
    })();
</script>
@endunless
