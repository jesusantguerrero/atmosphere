<script setup lang="ts">
withDefaults(defineProps<{ name?: string }>(), { name: "book" });
const paths: Record<string, string> = {
    folder: "M3 7V4h6l2 3h10v13H3V7Z",
    book: "M4 3h12a2 2 0 0 1 2 2v14H6a2 2 0 0 1-2-2V3Zm0 12h14M8 3v12",
    calendar: "M4 5h16v15H4zM7 2v6m10-6v6M4 10h16",
    check: "m5 12 4 4L19 6",
    wallet: "M3 5h16v15H3zM3 8h16m-5 4h7v5h-7z",
    basket: "M3 9h18l-2 11H5L3 9Zm4 0 5-7 5 7M9 12v5m6-5v5",
    link: "m9 15-2 2a4 4 0 0 1-6-6l4-4a4 4 0 0 1 6 0m2 2 2-2a4 4 0 0 1 6 6l-4 4a4 4 0 0 1-6 0m-3-3 6-6",
    plus: "M12 4v16M4 12h16",
    clock: "M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm0 4v5l3 2",
    arrow: "m9 5 7 7-7 7",
};
</script>
<template>
    <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.6"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        class="h-5 w-5 shrink-0"
    >
        <path :d="paths[name] || paths.book" />
    </svg>
</template>
