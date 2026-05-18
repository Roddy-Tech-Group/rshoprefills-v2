{{-- Theme engine — light / dark / system. Shared by every layout's <head>.
     Resolves localStorage['theme'] (default 'system') and toggles the `.dark`
     class on <html> BEFORE first paint, so there is no light/dark flash.
     Re-applies after every wire:navigate DOM swap, and follows the OS in real
     time while the choice is 'system'. `window.setTheme(choice)` is called by
     the settings theme picker and the <x-theme-toggle> buttons. --}}
<script>
    (function () {
        const root = document.documentElement;

        function prefersDark() {
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        function resolveDark() {
            const choice = localStorage.getItem('theme') || 'system';
            if (choice === 'dark') return true;
            if (choice === 'light') return false;
            return prefersDark();
        }
        function applyTheme() {
            root.classList.toggle('dark', resolveDark());
        }

        // Theme picker / toggles call this to persist + apply a new choice.
        window.setTheme = function (choice) {
            try { localStorage.setItem('theme', choice); } catch (_) {}
            applyTheme();
            // Let any on-page toggle re-sync its icon.
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark: resolveDark() } }));
        };
        window.themeIsDark = resolveDark;

        applyTheme(); // before first paint — no flash

        // Track the OS while the choice is 'system'.
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
            if ((localStorage.getItem('theme') || 'system') === 'system') {
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
