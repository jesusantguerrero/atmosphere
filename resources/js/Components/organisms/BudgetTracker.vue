<template>
    <div class="px-5 py-3 transition bg-white border divide-y rounded-lg shadow-md">
        <div class="flex justify-between pb-2">
            <h1 class="font-bold text-gray-500">
                Welcome to Loger <span class="text-pink-500">{{ username }}</span>
            </h1>
            <AtButton class="text-white bg-pink-500"> Edit budget </AtButton>
        </div>
        <div class="flex py-3">
            <div class="w-full transition cursor-pointer hover:opacity-75"
                @click="$emit('section-click', sectionName)"
                :key="sectionName"
                v-for="(section, sectionName) in sections"
            >
                <h4 class="text-gray-500">{{ section.label }}</h4>
                <SectionTitle>{{ section.value }}</SectionTitle>
            </div>
        </div>
        <slot></slot>
    </div>
</template>

<script setup>
import SectionTitle from "../atoms/SectionTitle";
import { AtButton } from "atmosphere-ui/dist/atmosphere-ui.es.js";

const props = defineProps({
    username: {
        type: String,
        required: true
    }
})

const sections = {
expenses: {
    label: 'Current Expenses',
    value: '15,000.00'
},
budget: {
    label: 'Monthly Budget',
    value: '30,000.00'
}
};
</script>
