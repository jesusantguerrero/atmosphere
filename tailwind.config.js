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
                "secondary": "#95b3f9",
                "accent": "#7c5bbf",
                "neutral": "#232130",
                base: customColors.rhino,                 
                info: "#3D68F5",
                success: "#79E7AE",
                warning: "#D39E17",
                error: "#F61909",
            }
        },
    },

    plugins: [require('@tailwindcss/typography')],
};
