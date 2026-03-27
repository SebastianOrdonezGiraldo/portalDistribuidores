import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/public.css',
                'resources/css/backoffice.css',
                'resources/js/core-public.js',
                'resources/js/catalog-product.js',
                'resources/js/backoffice.js',
            ],
            refresh: true,
        }),
    ],
});
