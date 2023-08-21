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

const isOpen = useLocalStorage(props.title || props.storageKey || "isOpen", true);
</script>


<template>
    <article class="px-8 py-4 bg-white border rounded-md border-primary" v-if="isOpen">
        <header class="flex justify-between">
            <h4 class="text-lg font-bold text-primary"> {{ title }}</h4>
            <button v-if="isClosable" @click="isOpen=false"><IconClose /></button>
        </header>
        <p class="text-body-1/80">{{ content }}</p>
      </article>
</template>

