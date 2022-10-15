<template>
    <div :class="classes">
        <div class="relative text-sm font-bold text-blue-400">
            <NumberHider />
            {{ value }}
        </div>
        <div class="w-full ml-5 text-right">
            <p class="font-bold">{{ title }}</p>
            <span class="w-full overflow-hidden text-sm text-right block overflow-ellipsis min-w-full"> {{ subtitle }}</span>
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
