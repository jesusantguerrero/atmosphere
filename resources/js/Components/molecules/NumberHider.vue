<template>
<div class="absolute rounded-md h-full w-full bg-white/30 backdrop-blur-xl" v-if="hasHiddenValues" />
</template>

<script setup>
    import { inject } from 'vue';
    const hasHiddenValues = inject('hasHiddenValues')
</script>
