import { plugins, theme } from './tailwind.shared.js';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/layouts/app.blade.php',
        './resources/views/layouts/partials/footer.blade.php',
        './resources/views/admin/**/*.blade.php',
        './resources/views/company/**/*.blade.php',
        './resources/views/dashboard.blade.php',
        './resources/views/empresa/**/*.blade.php',
        './resources/views/profile/**/*.blade.php',
        './resources/views/components/ui/**/*.blade.php',
        './resources/js/core-public.js',
        './resources/js/backoffice.js',
    ],
    theme,
    plugins,
};
