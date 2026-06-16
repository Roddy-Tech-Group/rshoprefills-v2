{{-- Global scroll lock. Any modal / slide-up sheet / drawer that wants to
     freeze the background page calls window.rshopScrollLock.lock() on open
     and .unlock() on close. Uses position:fixed + restored scroll position
     (beats overflow:hidden which still permits iOS Safari momentum scroll).
     A reference counter handles nested popups: the body only unlocks when
     the last lock holder releases.

     Included by both storefront and dashboard/admin layouts via the
     partials.head / inline head sections so the utility is always ready
     before any modal boots its Alpine scope. --}}
<script>
    (function () {
        if (window.rshopScrollLock) { return; }
        let counter = 0;
        let savedY = 0;
        window.rshopScrollLock = {
            lock() {
                if (counter === 0) {
                    savedY = window.scrollY || document.documentElement.scrollTop || 0;
                    const body = document.body;
                    const html = document.documentElement;
                    body.style.position = 'fixed';
                    body.style.top = '-' + savedY + 'px';
                    body.style.left = '0';
                    body.style.right = '0';
                    body.style.width = '100%';
                    html.style.overflow = 'hidden';
                    html.style.overscrollBehavior = 'contain';
                }
                counter++;
            },
            unlock() {
                counter = Math.max(0, counter - 1);
                if (counter === 0) {
                    const body = document.body;
                    const html = document.documentElement;
                    body.style.position = '';
                    body.style.top = '';
                    body.style.left = '';
                    body.style.right = '';
                    body.style.width = '';
                    html.style.overflow = '';
                    html.style.overscrollBehavior = '';
                    window.scrollTo(0, savedY);
                }
            },
            reset() {
                counter = 0;
                const body = document.body;
                const html = document.documentElement;
                body.style.position = '';
                body.style.top = '';
                body.style.left = '';
                body.style.right = '';
                body.style.width = '';
                html.style.overflow = '';
                html.style.overscrollBehavior = '';
            },
        };

        // Safety net: if a modal/drawer was open when the user followed a
        // wire:navigate link (an SPA swap, not a full reload), its close()
        // never fired and the body would stay position:fixed - the new page
        // then can't scroll. Clear any leftover lock on every SPA navigation.
        document.addEventListener('livewire:navigated', () => window.rshopScrollLock?.reset());
    })();
</script>
