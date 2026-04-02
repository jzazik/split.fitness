import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

const manifestIcons = [
    {
        src: '/pwa-192x192.png',
        sizes: '192x192',
        type: 'image/png',
    },
    {
        src: '/pwa-512x512.png',
        sizes: '512x512',
        type: 'image/png',
        purpose: 'any',
    },
    {
        src: '/pwa-512x512.png',
        sizes: '512x512',
        type: 'image/png',
        purpose: 'maskable',
    },
];

const publicAssets = [
    { src: '/favicon.ico' },
    { src: '/favicon-16x16.png' },
    { src: '/favicon-32x32.png' },
    { src: '/apple-touch-icon.png' },
];

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'split.fitness.test',
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            buildBase: '/build/',
            scope: '/',
            base: '/',
            registerType: 'autoUpdate',
            includeAssets: [],
            devOptions: {
                enabled: false,
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,jpg,png,svg,woff,woff2}'],
                navigateFallback: '/',
                navigateFallbackDenylist: [/^\/telescope/, /^\/api/, /^\/broadcasting/],
                additionalManifestEntries: [
                    { url: '/', revision: `${Date.now()}` },
                    ...manifestIcons.map((i) => ({
                        url: i.src,
                        revision: `${Date.now()}`,
                    })),
                    ...publicAssets.map((i) => ({
                        url: i.src,
                        revision: `${Date.now()}`,
                    })),
                ],
                maximumFileSizeToCacheInBytes: 3_000_000,
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.bunny\.net\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'bunny-fonts-cache',
                            expiration: {
                                maxEntries: 30,
                                maxAgeSeconds: 60 * 60 * 24 * 365,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'google-fonts-cache',
                            expiration: {
                                maxEntries: 30,
                                maxAgeSeconds: 60 * 60 * 24 * 365,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        urlPattern: ({ url }) =>
                            url.origin === self.location.origin &&
                            !url.pathname.startsWith('/build/'),
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'pages-cache',
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 60 * 60 * 24 * 7,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                ],
            },
            manifest: {
                name: 'Split Fitness',
                short_name: 'Split',
                description: 'Найди тренировки рядом с тобой',
                theme_color: '#f04e23',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait',
                scope: '/',
                start_url: '/',
                id: '/',
                icons: [...manifestIcons],
            },
        }),
    ],
});
