<template>
    <button class="w-32 flex items-center justify-center flex-col group">
        <section class="rounded-full  h-14 w-14 border" :class="colorClass">
            <i :class="iconClass" v-if="iconClass" />
        </section>
        <p class="mt-2"> {{ label }}</p>
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
    iconClass: {
        type: String,
    },
    colorClass: {
        default: 'group-hover:bg-base-lvl-2 bg-white'
    }
})
</script>
