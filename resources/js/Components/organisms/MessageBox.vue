<script setup lang="ts">
import IconClose from '@/Components/icons/IconClose.vue';
import { useLocalStorage } from '@vueuse/core';

const props = defineProps({
    storageKey: {
        type: String
    },
    title: {
        type: String
    },
    content: {
        type: String
    },
    isClosable: {
        type: Boolean,
        default: true
    }
})

// Explicit storageKey wins over title — title can change with locale and would
// re-surface a dismissed message when the user switches language.
const isOpen = useLocalStorage(props.storageKey || props.title || "isOpen", true);
</script>


<template>
    <article class="px-8 py-4 bg-base-lvl-3 border rounded-md border-primary" v-if="isOpen">
        <header class="flex justify-between">
            <h4 class="text-lg font-bold text-primary"> {{ title }}</h4>
            <button v-if="isClosable" @click="isOpen=false"><IconClose /></button>
        </header>
        <p class="text-body-1/80">{{ content }}</p>
      </article>
</template>

