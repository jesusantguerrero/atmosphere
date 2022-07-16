<template>
    <div :class="classes">
        <div class="relative text-sm font-bold text-blue-400">
            <NumberHider />
            {{ value }}
        </div>
        <div class="ml-5 text-gray-100">
            <p class="font-bold">{{ title }}</p>
            <span class="text-sm overflow-ellipsis flex flex-nowrap overflow-hidden"> {{ subtitle }}</span>
        </div>
    </div>
</template>

<script setup>
    import NumberHider from '@/Components/molecules/NumberHider.vue';
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
