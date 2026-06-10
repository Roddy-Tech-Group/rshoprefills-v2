import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/**
 * Laravel Echo over Reverb (self-hosted websockets) for real-time updates.
 *
 * Guarded: Echo only connects when VITE_REVERB_APP_KEY is present at build time,
 * so the app behaves exactly as before when the websocket server isn't running
 * or configured yet. Livewire auto-detects window.Echo for its broadcast
 * listeners, so this must load before Livewire boots (imported first in app.js).
 */
if (import.meta.env.VITE_REVERB_APP_KEY) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
