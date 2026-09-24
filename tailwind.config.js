import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Outfit', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // School green, built around the logo's #384820 (brand-800).
                brand: {
                    50: '#f5f8ef',
                    100: '#e7eed9',
                    200: '#cfdcb4',
                    300: '#afc485',
                    400: '#8ca85b',
                    500: '#6d8b3f',
                    600: '#566f30',
                    700: '#455a28',
                    800: '#384820',
                    900: '#2e3b1c',
                    950: '#172010',
                },
                // Seal gold, built around the logo's #a88800 (gold-600).
                gold: {
                    50: '#fdf9e7',
                    100: '#faf0c2',
                    200: '#f4df85',
                    300: '#ecc845',
                    400: '#dcae17',
                    500: '#c29a06',
                    600: '#a88800',
                    700: '#86690a',
                    800: '#6f5510',
                    900: '#5e4713',
                },
            },
        },
    },

    plugins: [forms],
};
