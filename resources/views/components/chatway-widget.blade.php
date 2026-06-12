@php
    // Chatway live chat. Renders nothing until CHATWAY_WIDGET_ID is set in
    // the environment, so local/dev and any deploy without the key stay clean.
    $chatwayWidgetId = config('services.chatway.widget_id');
@endphp

@if ($chatwayWidgetId)
    <script id="chatway" async="true" src="https://cdn.chatway.app/widget.js?id={{ $chatwayWidgetId }}"></script>
    <script>
        // wire:navigate swaps the <body>, which deletes the chat bubble DOM the
        // widget injected and can re-run the loader, leaving the chat flickering,
        // duplicated or gone. After each SPA navigation: drop any duplicate
        // mounts, and remount once if the bubble vanished entirely.
        (function () {
            if (window.__chatwayNavigateGuard) {
                return;
            }
            window.__chatwayNavigateGuard = true;

            const mounts = () => document.querySelectorAll('iframe[src*="chatway"], [id^="chatway-container"], [class*="chatway-widget"]');

            document.addEventListener('livewire:navigated', () => {
                // Give the swapped body a beat to settle before inspecting.
                setTimeout(() => {
                    const found = mounts();

                    if (found.length > 1) {
                        for (let i = 1; i < found.length; i++) {
                            found[i].remove();
                        }
                        return;
                    }

                    if (found.length === 0) {
                        document.getElementById('chatway')?.remove();
                        const s = document.createElement('script');
                        s.id = 'chatway';
                        s.async = true;
                        s.src = 'https://cdn.chatway.app/widget.js?id={{ $chatwayWidgetId }}';
                        document.body.appendChild(s);
                    }
                }, 300);
            });
        })();
    </script>
@endif
