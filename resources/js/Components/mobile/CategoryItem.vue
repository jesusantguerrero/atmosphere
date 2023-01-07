<template>
    <button class="w-32 flex items-center justify-center flex-col group">
        <section class="rounded-full overflow-hidden relative h-14 w-14 border-2" :class="colorClass" :style="{backgroundColor: color}">
            <div class="h-full w-1/2 bg-white opacity-30" />
            <i :class="iconClass" v-if="iconClass" class="text-white font-bold" />
        </section>
        <p class="mt-2 flex flex-wrap w-full text-center justify-center"> {{ label }}</p>
    </button>
</template>

<script setup>
defineProps({
    label: {
        type: String,
        default: 'Choose Category'
    },
    color: {

    },
    icon: {
        type: Object,
    },
    iconClass: {
        type: String,
        default: 'fas fa-tags'
    },
    colorClass: {
        default: 'group-hover:bg-base-lvl-2 bg-gray-400'
    }
})
</script>
