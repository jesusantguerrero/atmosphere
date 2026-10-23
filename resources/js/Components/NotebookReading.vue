<script setup lang="ts">
import NotebookInline from "./NotebookInline.vue";
import { computed } from "vue";
import {
    markdownBlocks,
    markdownInline,
} from "@/domains/logerProfile/notebookMarkdown";
const props = defineProps<{ content: string; pages?: { title: string }[] }>();
defineEmits<{ (event: "open-page", title: string): void }>();
const blocks = computed(() => markdownBlocks(props.content));
</script>

<template>
    <div
        class="notebook-reading max-w-prose break-words text-base leading-8 text-body-1/85"
    >
        <component
            v-for="(block, index) in blocks"
            :key="index"
            :is="block.tag"
            :start="block.start"
        >
            <template v-if="block.items">
                <li v-for="(item, itemIndex) in block.items" :key="itemIndex">
                    <NotebookInline
                        :text="item"
                        :pages="pages ?? []"
                        @open-page="$emit('open-page', $event)"
                    />
                </li>
            </template>
            <template v-else>
                <NotebookInline
                    :text="block.text ?? ''"
                    :pages="pages ?? []"
                    @open-page="$emit('open-page', $event)"
                />
            </template>
        </component>
    </div>
</template>

<style scoped>
.notebook-reading > * + * {
    margin-top: 1rem;
}
.notebook-reading :is(h1, h2, h3, h4, h5, h6) {
    font-weight: 600;
    line-height: 1.4;
    color: inherit;
    margin-top: 2rem;
}
.notebook-reading > :first-child {
    margin-top: 0;
}
.notebook-reading h1 {
    font-size: 1.5rem;
}
.notebook-reading h2 {
    font-size: 1.25rem;
}
.notebook-reading :is(h3, h4, h5, h6) {
    font-size: 1.125rem;
}
.notebook-reading ul {
    list-style: disc;
    padding-left: 1.5rem;
}
.notebook-reading ol {
    list-style: decimal;
    padding-left: 1.5rem;
}
.notebook-reading li + li {
    margin-top: 0.5rem;
}
.notebook-reading code {
    overflow-wrap: anywhere;
    font-size: 0.875em;
}
</style>
