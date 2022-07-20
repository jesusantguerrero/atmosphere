<template>
<SectionNav :sections="[
    {
        label: 'Planner',
        url: '/meal-planner'
    }, {
        label: 'Recipes',
        url: '/meals'
    }, 
    {
        label: 'Ingredients',
        url: '/meals/ingredients'
    }, {
        label: 'Menus',
        url: '/meals/menus'
    }]">
    <template #actions>
        <slot name="actions" />
    </template>
</SectionNav>
</template>


<script setup>
import SectionNav from "@/Components/molecules/SectionNav.vue";

</script>