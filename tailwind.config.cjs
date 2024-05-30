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
            },
            gridTemplateColumns: {
                24: 'repeat(24, minmax(0, 1fr))',
                30: 'repeat(30, minmax(0, 1fr))',
                60: 'repeat(60, minmax(0, 1fr))',
                90: 'repeat(90, minmax(0, 1fr))',
            }
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
        require('@mertasan/tailwindcss-variables')
    ],
};
