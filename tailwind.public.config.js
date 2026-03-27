import { plugins, theme } from './tailwind.shared.js';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/layouts/app.blade.php',
        './resources/views/layouts/guest.blade.php',
        './resources/views/layouts/partials/footer.blade.php',
        './resources/views/auth/**/*.blade.php',
        './resources/views/catalog/**/*.blade.php',
        './resources/views/cart/**/*.blade.php',
        './resources/views/orders/**/*.blade.php',
        './resources/views/product/**/*.blade.php',
        './resources/views/components/catalog/**/*.blade.php',
        './resources/views/components/ui/**/*.blade.php',
        './resources/js/core-public.js',
        './resources/js/catalog-product.js',
    ],
    theme,
    plugins,
};
