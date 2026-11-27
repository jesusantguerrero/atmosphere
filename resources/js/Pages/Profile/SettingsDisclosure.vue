<script setup lang="ts">
    // A rarely used form (change password, edit profile) folded into one row so it
    // stays out of the way until asked for. `v-model:open` lets a tile or notice
    // open it from elsewhere on the page.
    import { nextTick, ref, watch } from 'vue'

    defineProps<{
        title: string;
        hint?: string;
    }>();

    const open = defineModel<boolean>('open', { default: false });
    const root = ref<HTMLElement | null>(null);

    watch(open, async (isOpen) => {
        if (isOpen) {
            await nextTick();
            root.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
</script>

<template>
    <div ref="root" class="flex flex-col gap-3 scroll-mt-24">
        <button
            type="button"
            class="flex items-center w-full gap-3 px-5 py-4 text-left border rounded-xl border-base bg-base-lvl-3 hover:bg-base-lvl-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"
            :aria-expanded="open"
            @click="open = !open"
        >
            <span class="text-lg text-body-1/70"><slot name="icon" /></span>
            <span class="flex-1 font-bold text-body">{{ title }}</span>
            <span v-if="hint && !open" class="hidden text-sm text-body-1/70 sm:inline">{{ hint }}</span>
            <IMdiChevronDown class="transition-transform text-body-1/70" :class="{ 'rotate-180': open }" />
        </button>
        <div v-if="open">
            <slot />
        </div>
    </div>
</template>
