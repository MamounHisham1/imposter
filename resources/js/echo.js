import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// WebSocket Best Practices Configuration
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],

    // Connection configuration for better reliability
    auth: {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        }
    },

    // Pusher-specific options for better connection handling
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',

    // Enable activity checks to detect broken connections
    activityTimeout: 30000, // 30 seconds
    pongTimeout: 10000, // 10 seconds

    // Automatic reconnection
    enableLogging: import.meta.env.DEV ?? false,
});

// Global connection event handlers
if (window.Echo?.connector?.pusher) {
    const pusher = window.Echo.connector.pusher;

    pusher.connection.bind('connected', () => {
        console.log('✅ WebSocket connected');
    });

    pusher.connection.bind('connecting', () => {
        console.log('🔄 WebSocket connecting...');
    });

    pusher.connection.bind('unavailable', () => {
        console.warn('⚠️ WebSocket unavailable');
    });

    pusher.connection.bind('failed', () => {
        console.error('❌ WebSocket connection failed');
    });

    pusher.connection.bind('disconnected', () => {
        console.warn('⚠️ WebSocket disconnected');
    });

    pusher.connection.bind('error', (err) => {
        console.error('❌ WebSocket error:', err);
    });
}
