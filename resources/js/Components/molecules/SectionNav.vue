<script setup lang="ts">
import SubmenuTab from '@/Components/atoms/SubmenuTab.vue';
import { router } from '@inertiajs/vue3';
import { computed } from "vue"
import { useBreakpoints, breakpointsTailwind } from "@vueuse/core"
import { NDropdown } from 'naive-ui';


interface NavSection {
    url?: string;
    to?: string;
    action?: string;
    value: string;
    label: string;
    isActiveFunction: Function,
    as: any
}

const props = withDefaults(defineProps<{
    sections: NavSection[];
    modelValue?: string;
}>(), {
    selectedClass: "border-primary text-primary",
});

const emit = defineEmits(['update:modelValue', 'action']);

const currentPath = computed(() => {
    return document?.location?.pathname
})

const isSelected = (section: NavSection) => {
    const sectionName = section.url || section.value
    const value = props.modelValue || currentPath.value
    return sectionName == value
}

const handleClick = (section: NavSection) => {
    if (section.as == "a") return;
    if (section?.url || section?.to) {
        router.visit(section.url || section.to)
    } else if (section?.action) {
        emit('action', section.action)
    } else {
        emit('update:modelValue', section?.value)
    }
}

const isMobile = useBreakpoints(breakpointsTailwind).isSmaller('sm');
const visibleTabs = computed(() => {
    return isMobile ? props.sections.slice(0, 2) : props.sections
})

const options = computed(() => {
    return props.sections.slice(2).map(section => ({
        name: section.url,
        ...section
    }))
})

const handleOptionClick = (url: string) => {
    if (url.includes('/')) {
        router.visit(url)
    } else {
        emit('update:modelValue', url)
    }
}
</script>


<template>
<div class="flex justify-between w-full h-12 pr-8 overflow-auto md:overflow-hidden">
    <SubmenuTab
        v-for="section in visibleTabs"
        @click="handleClick(section)"
        :is-selected="isSelected(section)"
        :current-value="modelValue"
        :key="section.url ?? section.value ?? sectionName"
        :value="section.url ?? section.to ?? section.value ?? sectionName"
        :is-active-function="section.isActiveFunction"
        :selected-class="selectedClass"
        :label="section.label"
        :as="section.as"
        class="w-full text-xs text-center md:text-md md:w-auto"
    >
        {{ section.label }}
    </SubmenuTab>
    <NDropdown
        trigger="click"
        key-field="name"
        class="w-full"
        v-if="isMobile"
        :options="options"
        :on-select="handleOptionClick"
    >
        <SubmenuTab
            key="more"
            class="w-full text-xs text-center md:text-md md:w-auto"
        >
            More
        </SubmenuTab>
    </NDropdown>
    <div class="items-center justify-end hidden py-1 ml-auto space-x-2 text-xs md:flex">
       <slot name="actions" />
    </div>
</div>
</template>
