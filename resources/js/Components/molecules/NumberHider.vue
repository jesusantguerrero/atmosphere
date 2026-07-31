<script setup lang="ts">
    import { inject } from 'vue';
    const hasHiddenValues = inject('hasHiddenValues')
</script>

<!-- Privacy-mode blur overlay. The DIRECT PARENT must have `relative`, or this
     absolute overlay sizes against the nearest positioned ancestor — in fixed
     panels (e.g. the right-rail widgets) that meant giant blurred stripes
     covering the whole panel. inset-0 keeps it glued to the parent's box. -->
<template>
<div class="absolute inset-0 border rounded-md bg-base-lvl-3/10 border-white/20 backdrop-blur-xl" v-if="hasHiddenValues" />
</template>


