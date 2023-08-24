<script setup lang="ts">
import { useCollapse } from '@/composables/useCollapse';
import { ref } from 'vue';

defineProps({
    title: {
        type: String
    },
    titleClass: {
        type: String
    },
    gap: {
        type: Boolean,
        default: true
    },
    contentClass: {
        type: String
    },
});

const incomeRef = ref();
const { isCollapsed, icon, toggleCollapse } = useCollapse(incomeRef);
</script>

<template>
<article ref="incomeRef">
    <header class="cursor-pointer" :class="titleClass" @click="toggleCollapse()">
        <slot name="header" :icon="icon" :is-collapsed="isCollapsed">
            <i :class="icon" class="mr-2"/>
            <slot name="title">
                {{ title }}
            </slot>
        </slot>
    </header>
    <section v-if="!isCollapsed" :class="[gap && 'pl-4', contentClass]">
        <slot name="content" />
    </section>
</article>
</template>


