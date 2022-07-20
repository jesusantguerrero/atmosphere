/** @type {import('tailwindcss').Config} */
const defaultTheme = require('tailwindcss/defaultTheme');
const colors = require("tailwindcss/colors");
const customColors = require('./tailwindTheme/colors');

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
                primary: colors.pink,
                base: customColors.rhino
            }
        },
    },

    plugins: [require('@tailwindcss/typography')],
};
