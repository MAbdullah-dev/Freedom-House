import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { nodePolyfills } from 'vite-plugin-node-polyfills';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/app.scss',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        nodePolyfills({
            globals: {
                crypto: true, // Polyfill the `crypto` module
            },
        }),
    ],
});
