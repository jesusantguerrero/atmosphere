<template>
<div class="flex justify-between w-full pr-8">
    <SubmenuTab
        v-for="section in sections"
        @click="handleClick(section)"
        :is-selected="isSelected(section)"
        :key="section.url"
    >
        {{ section.label }}
    </SubmenuTab>
    <div class="space-x-2 flex py-1 justify-end items-center ml-auto">
       <slot name="actions" />
    </div>
</div>
</template>


<script setup>
import SubmenuTab from '@/Components/atoms/SubmenuTab.vue';
import { Inertia } from '@inertiajs/inertia';
import { computed } from "vue"

const props = defineProps({
    sections: {
        type: Array,
        default: () => ([])
    },
    modelValue: {
        type: String
    }
})
const emit = defineEmits(['update:modelValue']);

const currentPath = computed(() => {
    return document?.location?.pathname
})

const isSelected = (section) => {
    const sectionName = section.url || section.value
    const value = props.modelValue || currentPath.value
    return sectionName == value
}

const handleClick = (section) => {
    if (section.url) {
        Inertia.visit(section.url)
    } else {
        emit('update:modelValue', section.value)
    }
}
</script>
