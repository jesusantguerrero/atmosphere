<script setup lang="ts">
import { ref, computed, nextTick, watch } from "vue";
import NotebookReading from "./NotebookReading.vue";
const props = defineProps<{
    modelValue: string;
    disabled?: boolean;
    pages?: { title: string }[];
}>();
const emit = defineEmits<{
    (event: "update:modelValue", value: string): void;
}>();
const pieces = ref(props.modelValue.split(/(\n[ \t]*\n)/));
const active = ref<number | null>(null);
const editor = ref<HTMLTextAreaElement | null>(null);
const blocks = computed(() =>
    pieces.value
        .map((text, index) => ({ text, index }))
        .filter(({ index }) => index % 2 === 0),
);
watch(
    () => props.modelValue,
    (value) => {
        if (value !== pieces.value.join("")) {
            pieces.value = value.split(/(\n[ \t]*\n)/);
            active.value = null;
        }
    },
);
const resize = () => {
    if (editor.value) {
        editor.value.style.height = "auto";
        editor.value.style.height = `${editor.value.scrollHeight}px`;
    }
};
const editBlock = async (index: number) => {
    if (props.disabled) return;
    active.value = index;
    await nextTick();
    editor.value?.focus();
    resize();
};
const update = (event: Event) => {
    if (active.value === null) return;
    pieces.value[active.value] = (event.target as HTMLTextAreaElement).value;
    emit("update:modelValue", pieces.value.join(""));
    resize();
};
const finishBlock = () => {
    pieces.value = pieces.value.join("").split(/(\n[ \t]*\n)/);
    active.value = null;
};
const addParagraph = async () => {
    if (props.disabled) return;
    if (pieces.value[pieces.value.length - 1] !== "")
        pieces.value.push("\n\n", "");
    await editBlock(pieces.value.length - 1);
};
const blockClass = (text: string) =>
    /^# /.test(text)
        ? "text-2xl font-semibold leading-snug"
        : /^## /.test(text)
          ? "text-xl font-semibold leading-snug"
          : /^#{3,6} /.test(text)
            ? "text-lg font-semibold leading-snug"
            : "text-base leading-8";
</script>
<template>
    <div aria-label="Editor Markdown" class="mt-8 max-w-prose text-body-1/85">
        <div v-for="block in blocks" :key="block.index" class="mb-4">
            <textarea
                v-if="active === block.index"
                :ref="(element) => (editor = element as HTMLTextAreaElement)"
                :value="block.text"
                :disabled="disabled"
                aria-label="Contenido del bloque"
                placeholder="Empieza a escribir…"
                rows="1"
                :class="blockClass(block.text)"
                class="block min-h-[44px] w-full resize-none overflow-hidden border-0 bg-transparent p-0 font-sans text-body-1 caret-primary shadow-none focus:ring-0 focus:outline-none"
                @input="update"
                @blur="finishBlock"
                @keydown.esc.prevent="finishBlock"
            />
            <div
                v-else
                role="button"
                tabindex="0"
                :aria-disabled="disabled"
                aria-label="Editar este bloque"
                class="min-h-[32px] cursor-text rounded-sm outline-none focus-visible:ring-1 focus-visible:ring-primary/60"
                @click="editBlock(block.index)"
                @keydown.enter.prevent="editBlock(block.index)"
                @keydown.space.prevent="editBlock(block.index)"
            >
                <NotebookReading
                    v-if="block.text.trim()"
                    :content="block.text"
                    :pages="pages"
                />
                <span v-else class="text-body-1/60">Empieza a escribir…</span>
            </div>
        </div>
        <button
            type="button"
            :disabled="disabled"
            aria-label="Añadir párrafo"
            class="block min-h-[64px] w-full cursor-text rounded-sm text-left text-sm text-body-1/40 outline-none focus-visible:ring-1 focus-visible:ring-primary/60"
            @click="addParagraph"
        >
            Continuar escribiendo…
        </button>
    </div>
</template>
