<template>
<article ref="incomeRef">
    <header class="cursor-pointer" :class="titleClass" @click="toggleCollapse()">
        <i :class="icon" class="mr-2"/>
        <slot name="title">
            {{ title }}
        </slot>
    </header>
    <section v-if="!isCollapsed" class="pl-4">
        <slot name="content" />
    </section>
</article>
</template>

<script setup>
import { useCollapse } from '@/composables/useCollapse';
import { ref } from 'vue';

defineProps({
    title: {
        type: String
    },
    titleClass: {
        type: String
    }
});

const incomeRef = ref();
const { isCollapsed, icon, toggleCollapse } = useCollapse(incomeRef);
</script>
