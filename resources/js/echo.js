import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Get Reverb config from window object (set by server) or Vite env (fallback)
const reverbConfig = window.__reverbConfig || {
    key: import.meta.env.VITE_REVERB_APP_KEY,
    host: import.meta.env.VITE_REVERB_HOST,
    port: import.meta.env.VITE_REVERB_PORT ?? 443,
    scheme: import.meta.env.VITE_REVERB_SCHEME ?? 'https',
};

// WebSocket Best Practices Configuration
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: reverbConfig.key,
    wsHost: reverbConfig.host,
    wsPort: reverbConfig.port,
    wssPort: reverbConfig.port,
    forceTLS: reverbConfig.scheme === 'https',
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
