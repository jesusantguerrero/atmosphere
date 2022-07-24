<template>
<div class="flex justify-between w-full pr-8">
    <SubmenuTab v-for="section in sections" @click="$inertia.visit(section.url)" :is-selected="isSelected(section)">
        {{ section.label }}
    </SubmenuTab>
    <div class="space-x-2 flex py-1 justify-end items-center ml-auto">
       <slot name="actions" />
    </div>
</div>
</template>


<script setup>
import SubmenuTab from '@/Components/atoms/SubmenuTab.vue';
import { computed } from "vue"

defineProps({
    sections: {
        type: Array,
        default: () => ([])
    }
})

const currentPath = computed(() => {
    return document?.location?.pathname
})

const isSelected = (section) => {
    return currentPath.value == section.url
}

</script>