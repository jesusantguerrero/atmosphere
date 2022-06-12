<template>
    <div :class="classes">
        <div class="relative text-xl font-bold text-blue-400">
            <div class="absolute w-full h-full text-xl rounded-sm bg-slate-400" v-if="hidden" />
            {{ value }}
        </div>
        <div class="ml-5 text-gray-100">
            <p class="font-bold">{{ title }}</p>
            <span>{{ subtitle }}</span>
        </div>
    </div>
</template>

<script setup>
    defineProps({
         value: {
             type: String,
         },
         title: {
             type: String,
         },
         subtitle: {
             type: String,
         },
         hidden: {
             type: Boolean,
         },
    })
    const classes = 'flex bg-gray-100 items-center rounded-md px-5 py-4'
</script>
