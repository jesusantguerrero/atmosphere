<template>
<SectionNav :sections="[
    {
        label: 'Profile',
        url: '/user/profile'
    },
    {
        label: 'Api Tokens',
        url: '/user/api-tokens'
    },
    {
        label: 'Teams',
        url: '/teams/1'
    },
    {
        label: 'Integrations',
        url: '/integrations'
    },
    ]">
    <template #actions>
        <slot name="actions"> <div class="w-1 py-4"></div> </slot>
    </template>
</SectionNav>
</template>


<script setup>
import SectionNav from "@/Components/molecules/SectionNav.vue";
</script>
