<script setup lang="ts">
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { IBoard } from "@/domains/housing/models";

const props = defineProps<{
    board: IBoard;
    color?: string | null;
}>();

// Curated palette (matches the app's chart + Family avatar colors).
// Used as a deterministic fallback when a board has no color set.
const PALETTE = [
    "#7B77D1", "#F37EA1", "#80CDFE", "#6EE7B7",
    "#FBBF77", "#A78BFA", "#5EEAD4", "#F59E9E",
];

const accent = computed(() => {
    if (props.color) return props.color;
    const seed = String(props.board.id ?? props.board.name ?? "");
    let hash = 0;
    for (let i = 0; i < seed.length; i++) {
        hash = (hash * 31 + seed.charCodeAt(i)) >>> 0;
    }
    return PALETTE[hash % PALETTE.length];
});
</script>

<template>
    <Link
        :href="`/housing/boards/${board.id}`"
        class="flex flex-col items-center w-32 px-4 py-5 text-center transition-colors border rounded-lg bg-base-lvl-2 border-base hover:border-primary"
    >
        <div
            class="flex items-center justify-center w-16 h-16 mb-3 text-2xl font-bold rounded-full"
            :style="{ backgroundColor: accent + '1F', color: accent }"
        >
            {{ board.name?.charAt(0)?.toUpperCase() }}
        </div>
        <h3 class="max-w-full font-semibold capitalize truncate text-body">{{ board.name }}</h3>
        <p v-if="board.template" class="mt-0.5 max-w-full text-xs capitalize truncate text-body-2">
            {{ board.template }}
        </p>
    </Link>
</template>
