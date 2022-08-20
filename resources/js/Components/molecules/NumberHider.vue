<template>
<div class="absolute rounded-md h-full w-full bg-white/10 border-white/20 border backdrop-blur-xl" v-if="hasHiddenValues" />
</template>

<script setup>
    import { inject } from 'vue';
    const hasHiddenValues = inject('hasHiddenValues')
</script>
