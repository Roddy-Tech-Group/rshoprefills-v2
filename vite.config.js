import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: [`resources/views/**/*`],
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
    },
    build: {
        // apexcharts is a single ~560 kB library. We already load it lazily (only
        // on chart pages, via dynamic import) so it never blocks first paint - it
        // just trips Vite's 500 kB default. Lift the threshold above that one
        // intentional on-demand chunk so the build output stays clean.
        chunkSizeWarningLimit: 700,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (! id.includes('node_modules')) {
                        return;
                    }
                    // Heavy, on-demand libraries keep their own dynamic-import
                    // chunks (Rollup already code-splits them), so they're only
                    // fetched when a chart / animation / map actually renders.
                    if (/[\\/]node_modules[\\/](apexcharts|lottie-web|jsvectormap|gsap)[\\/]/.test(id)) {
                        return;
                    }
                    // Everything else (axios, flatpickr, …) is small and eagerly
                    // loaded - bundle it into one cacheable vendor chunk separate
                    // from app code, so a CSS/markup tweak doesn't bust its hash.
                    return 'vendor';
                },
            },
        },
    },
});
