<script setup>
import { Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
</script>

<template>
    <Link :href="route('landing')">
        <AppIcon size="large" />
    </Link>
</template>
