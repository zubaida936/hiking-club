import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/admin.css',
                'resources/js/admin.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                bunny('Jost', {
                    weights: [500, 600, 700],
                }),
                bunny('Roboto', {
                    weights: [400, 500, 700],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        // Vite listens on 0.0.0.0 inside the node container, but the browser must
        // load assets from localhost (browsers block requests to 0.0.0.0).
        host: '0.0.0.0',
        origin: 'http://localhost:5173',
        hmr: {
            host: 'localhost',
        },
        watch: {
            // File-change events from the Windows drive don't reach the Docker VM,
            // so poll; otherwise new Tailwind classes and Blade edits need a restart.
            usePolling: true,
            interval: 1000,
            ignored: ['**/storage/**', '**/vendor/**', '**/public/build/**'],
        },
    },
});
