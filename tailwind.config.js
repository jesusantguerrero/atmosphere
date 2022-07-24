/** @type {import('tailwindcss').Config} */
const defaultTheme = require('tailwindcss/defaultTheme');
const themes = require('./tailwindTheme/index');

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
                ...themes.defaultDark
            }
        },
    },

    plugins: [require('@tailwindcss/typography')],
};
