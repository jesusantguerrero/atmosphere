<template>
    <div :class="classes">
        <div class="text-xl font-bold text-blue-700">{{ value }}</div>
        <div class="ml-5 text-gray-600">
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
         }
    })
    const classes = 'flex bg-gray-100 items-center rounded-md px-5 py-4'
</script>
