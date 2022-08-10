<template>
<SectionNav :sections="[
    {
        label: 'Profile',
        url: '/user/profile'
    }, {
        label: 'Api Tokens',
        url: '/user/api-tokens'
    },
    {
        label: 'Teams',
        url: '/teams/1'
    }]">
    <template #actions>
        <slot name="actions"> <div class="py-4 w-1"></div> </slot>
    </template>
</SectionNav>
</template>


<script setup>
import SectionNav from "@/Components/molecules/SectionNav.vue";

</script>
