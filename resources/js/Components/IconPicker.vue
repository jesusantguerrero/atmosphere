<template>

</template>

<script setup>
import 'vanilla-icon-picker/dist/themes/default.min.css';

import IconPicker from 'vanilla-icon-picker';

const iconPicker = new IconPicker({
    // Change icon picker's theme
    theme: 'default',

    // Set icon(s) library(ies)
    iconSource: [
        'FontAwesome Brands 5',
        'FontAwesome Solid 5',
        'FontAwesome Regular 5',
    ],
    // Close icon picker modal when icon is selected
    // If is `false` save button appear
    closeOnSelect: true,

    // Set a default value, preselect for example
    // icon's value and icon's name work
    defaultValue: null,

    // Translatable text
    i18n: {
        'input:placeholder': 'Search icon…',

        'text:title': 'Select icon',
        'text:empty': 'No results found…',

        'btn:save': 'Save'
    }
})

iconPicker.on('select', instance => {
    console.log('Select:', instance);
});
</script>
