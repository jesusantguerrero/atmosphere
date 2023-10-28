<script setup lang="ts">
    import LogerButtonTab from './LogerButtonTab.vue';
</script>


<template>
<LogerButtonTab
    rounded
    type="button"
    class="relative transition bg-transparent rounded-full text-body-1/80 hover:bg-primary/10 border-base-lvl-1 hover:border-primary"
>
    <slot />
</LogerButtonTab>
</template>

