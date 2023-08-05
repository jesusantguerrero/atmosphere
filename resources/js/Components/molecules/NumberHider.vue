<script setup lang="ts">
    import { inject } from 'vue';
    const hasHiddenValues = inject('hasHiddenValues')
</script>

<template>
<div class="absolute w-full h-full border rounded-md bg-white/10 border-white/20 backdrop-blur-xl" v-if="hasHiddenValues" />
</template>


