<template>
<div class="px-5 py-5 mx-auto text-white bg-primary cursor-pointer rounded-xl" @click="$emit('click')">
    <header class="flex justify-between">
        <h4> {{ title }} </h4>
        <span><i class="fa fa-cogs"></i></span>
    </header>
    <div class="mt-2 text-2xl font-bold relative">
        <NumberHider />
        {{ value }}
    </div>
    <div class="px-5 py-2 mt-4 bg-gray-700 bg-opacity-25 rounded-3xl"> {{ varianceTitle }}:
    <span class="font-bold">{{ variance }}%</span></div>
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
         varianceTitle: {
             type: String,
         },
         variance: {
            type: String,

         }
    })
</script>
