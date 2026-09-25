import './bootstrap';
import Alpine from 'alpinejs';

// Kitchen Display System real-time updates. Only wired up when Echo/Pusher
// env vars are configured (see .env.example) — resources/views/kds/board.blade.php
// falls back to polling when window.Echo is undefined, so this is optional.
if (import.meta.env.VITE_PUSHER_APP_KEY) {
    const Echo = (await import('laravel-echo')).default;
    const Pusher = (await import('pusher-js')).default;
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        wsHost: import.meta.env.VITE_PUSHER_HOST,
        wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
        wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
        enabledTransports: ['ws', 'wss'],
    });
}

window.Alpine = Alpine;
Alpine.start();
