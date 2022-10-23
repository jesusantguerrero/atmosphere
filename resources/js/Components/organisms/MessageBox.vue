<template>
    <article class="border border-primary rounded-md bg-white py-4 px-8" v-if="isOpen">
        <header class="flex justify-between">
            <h4 class="text-primary font-bold text-lg"> {{ title }}</h4>
            <button v-if="isClosable" @click="isOpen=false"><IconClose /></button>
        </header>
        <p class="text-body-1/80">{{ content }}</p>
      </article>
</template>

<script setup>
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

const isOpen = useLocalStorage(props.title || props.storageKey, true);
</script>
