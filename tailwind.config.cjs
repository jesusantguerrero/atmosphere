/** @type {import('tailwindcss').Config} */
const defaultTheme = require('tailwindcss/defaultTheme');
const themes = require('./tailwindTheme/index.cjs');

module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
        './node_modules/atmosphere-ui/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                brand: ['Pacifico', 'cursive'],
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ...themes.defaultLight
            },
            keyframes: {
                ripple: {
                    '0%': { width: '0px', height: '0px', opacity: 0.5},
                    '100%': { width: '500px', height: '500px', opacity: 0}
                }
            },
            animation: {
                ripple: 'ripple 1s linear infinite'
            }
        },
    },

    plugins: [
        require('@tailwindcss/typography'),
        require('@mertasan/tailwindcss-variables')
    ],
};