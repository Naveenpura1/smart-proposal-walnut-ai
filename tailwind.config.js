import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
<<<<<<< HEAD
=======
    darkMode: false,
>>>>>>> 9ad783d (Initial commit)
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
<<<<<<< HEAD
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
=======
                sans: ['Figtree', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50:  '#f5f3ff',
                    100: '#ede9fe',
                    200: '#ddd6fe',
                    300: '#c4b5fd',
                    400: '#a78bfa',
                    500: '#8b5cf6',
                    600: '#7c3aed',
                    700: '#6d28d9',
                    800: '#5b21b6',
                    900: '#4c1d95',
                },
            },
            boxShadow: {
                'card': '0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.04)',
                'card-md': '0 4px 12px -2px rgba(0,0,0,0.08), 0 2px 4px -2px rgba(0,0,0,0.04)',
                'input': '0 1px 2px 0 rgba(0,0,0,0.04)',
                'btn': '0 1px 2px 0 rgba(0,0,0,0.08), inset 0 1px 0 0 rgba(255,255,255,0.12)',
>>>>>>> 9ad783d (Initial commit)
            },
        },
    },

    plugins: [forms],
};
