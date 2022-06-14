<template>
<div class="px-5 py-5 mx-auto text-white bg-pink-400 cursor-pointer rounded-xl" @click="$emit('click')">
    <h4> {{ title }} </h4>
    <div class="mt-2 text-4xl font-bold relative">
        <div class="absolute rounded-md bg-pink-500 h-full w-full" v-if="hasHiddenValues" />
        {{ value }}
    </div>
    <div class="px-5 py-2 mt-4 bg-gray-700 bg-opacity-25 rounded-3xl"> {{ varianceTitle }}:
    <span class="font-bold">{{ variance }}%</span></div>
</div>
</template>

<script setup>
    import { inject } from 'vue';

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

    const hasHiddenValues = inject('hasHiddenValues')
</script>
