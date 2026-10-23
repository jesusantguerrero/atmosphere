<script setup lang="ts">
import { markdownInline } from "@/domains/logerProfile/notebookMarkdown";
const props = defineProps<{ text: string; pages: { title: string }[] }>();
defineEmits<{ (event: "open-page", title: string): void }>();
const exists = (title: string) =>
    props.pages.some(
        (page) => page.title.toLocaleLowerCase() === title.toLocaleLowerCase(),
    );
</script>
<template>
    <template v-for="(part, index) in markdownInline(text)" :key="index">
        <button
            v-if="part.tag === 'link' && exists(part.text)"
            type="button"
            class="text-primary underline underline-offset-4 focus-visible:outline focus-visible:outline-primary"
            @click="$emit('open-page', part.text)"
        >
            {{ part.text }}
        </button>
        <span
            v-else-if="part.tag === 'link'"
            class="text-body-1/60"
            :title="'Página no encontrada: ' + part.text"
            >{{ part.text }} (sin enlace)</span
        >
        <component v-else :is="part.tag">{{ part.text }}</component>
    </template>
</template>
