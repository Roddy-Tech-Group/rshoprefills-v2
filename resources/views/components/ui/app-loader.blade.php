{{--
    Dashboard preloader. Motion Code x hostinger-style: a faint static ring with a rotating
    brand-blue arc and the R icon centred. Used only by the admin and customer
    dashboard layouts (never the storefront).

    Everything here is inline (critical CSS + a tiny vanilla script) on purpose:
    the overlay must paint on the first byte of HTML, before the Vite bundles
    load, or the dashboard flashes in unstyled first. Inlining also lets it hide
    itself without waiting on app.js, and adds no dependencies.
--}}
{{-- @persist keeps this element across wire:navigate. Without it, Livewire
     morphs the body on every navigation, which would strip the JS-applied
     --hidden class and flash the loader on each page swap. --}}
@persist('app-loader')
<div id="app-loader" class="app-loader" role="status" aria-live="polite" aria-label="Loading">
    <div class="app-loader__spinner">
        <svg class="app-loader__ring" viewBox="0 0 120 120" aria-hidden="true">
            {{-- Faint full track underneath --}}
            <circle class="app-loader__track" cx="60" cy="60" r="54"></circle>
            {{-- Blue arc that spins continuously around the grey track. --}}
            <circle class="app-loader__progress" cx="60" cy="60" r="54"></circle>
        </svg>
        <img class="app-loader__logo" src="{{ asset('assets/PWAicon.webp') }}" alt="" width="48" height="48">
    </div>
</div>
@endpersist

<style>
    .app-loader {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eff6ff;
        opacity: 1;
        transition: opacity 300ms ease;
    }
    .dark .app-loader {
        background: #0c1a36;
    }
    /* Hidden state: fade out, then drop out of the layout so it can't trap
       clicks once the page is usable. */
    .app-loader.app-loader--hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    .app-loader__spinner {
        position: relative;
        width: 150px;
        height: 150px;
    }
    .app-loader__ring {
        width: 100%;
        height: 100%;
    }
    .app-loader__track,
    .app-loader__progress {
        fill: none;
        /* Thin 1.5px stroke: the ring is large (120px) but the line stays slim. */
        stroke-width: 1.5;
    }
    /* Light grey full track underneath the spinning blue arc. */
    .app-loader__track {
        stroke: #e5e7eb;
    }
    .dark .app-loader__track {
        stroke: rgba(255, 255, 255, 0.12);
    }
    /* Premium indeterminate spinner: the blue arc both rotates AND grows/shrinks
       (Material-style), so it always shows smooth motion, starts the same every
       time, and never holds a "complete" state or carries progress between pages.
       Two independent animations compose: a constant rotation + an eased dash
       cycle. circumference (2*PI*54) ~= 339.29, so 340 is used as the gap. */
    .app-loader__progress {
        stroke: #2563eb;
        stroke-linecap: round;
        transform-box: fill-box;
        transform-origin: center;
        animation:
            app-loader-rotate 1.6s linear infinite,
            app-loader-dash 1.6s ease-in-out infinite;
    }
    .dark .app-loader__progress {
        stroke: #60a5fa;
    }
    .app-loader__logo {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 60px;
        height: 60px;
        transform: translate(-50%, -50%);
        object-fit: contain;
    }
    @keyframes app-loader-rotate {
        to {
            transform: rotate(360deg);
        }
    }
    @keyframes app-loader-dash {
        0% {
            stroke-dasharray: 1, 340;
            stroke-dashoffset: 0;
        }
        50% {
            stroke-dasharray: 230, 340;
            stroke-dashoffset: -35;
        }
        100% {
            stroke-dasharray: 230, 340;
            stroke-dashoffset: -300;
        }
    }
    /* Reduced motion: a gentle steady rotation, no growing/shrinking. */
    @media (prefers-reduced-motion: reduce) {
        .app-loader__progress {
            stroke-dasharray: 230 340;
            animation: app-loader-rotate 2.6s linear infinite;
        }
    }
</style>

<script data-navigate-once>
    (function () {
        // Run-once guard (data-navigate-once also stops re-execution on SPA nav).
        if (window.__appLoaderBound) {
            return;
        }
        window.__appLoaderBound = true;

        // Minimum time the loader stays up once shown, so the spinner is always
        // visibly there even on instant loads. The spinner itself runs forever
        // in CSS, so there is nothing to restart or reset in JS.
        var MIN_VISIBLE = 500;
        var shownAt = 0;

        var get = function () { return document.getElementById('app-loader'); };

        var show = function () {
            var el = get();
            if (! el) { return; }
            el.classList.remove('app-loader--hidden');
            shownAt = Date.now();
        };

        var hide = function () {
            var el = get();
            if (! el) { return; }
            var wait = Math.max(0, MIN_VISIBLE - (Date.now() - shownAt));
            window.setTimeout(function () {
                var node = get();
                if (node) { node.classList.add('app-loader--hidden'); }
            }, wait);
        };

        // Initial load: show now, hide once the DOM is ready.
        show();
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', hide);
        } else {
            hide();
        }
        window.setTimeout(hide, 8000); // safety: never stay stuck

        // SPA navigation: show on every page change, hide once the new page lands.
        document.addEventListener('livewire:navigate', function () {
            show();
        });
        document.addEventListener('livewire:navigated', function () {
            hide();
        });
    })();
</script>
