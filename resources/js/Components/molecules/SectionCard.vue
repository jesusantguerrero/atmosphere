
<template>
<div :class="classes" class="overflow-hidden text-white">
    <div class="flex justify-between">
        <SectionTitle type="secondary" class="w-full">{{ sectionTitle }}</SectionTitle>
        <div class="flex items-center justify-end w-full">
            <slot name="action" v-if="action || $slots.action">
                <AtButton v-if="action" class="flex items-center text-primary" @click="$emit('action')">
                    <span>
                        {{ action.label }}
                    </span>
                    <i class="ml-2" :class="action.iconClass" v-if="action.iconClass" />
                </AtButton>
            </slot>
        </div>
    </div>
    <div :class="cardClass" class="overflow-hidden border-base">
        <slot />
    </div>
</div>
</template>

<script setup>
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import { AtButton } from "atmosphere-ui";

defineProps({
    classes: {
        type: String,
        default: 'mt-1'
    },
    cardClass: {
        type: String,
        default: 'mt-2 bg-base-lvl-3 border border-base rounded-lg shadow-md'
    },
    sectionTitle: {
        type: String,
        default: ''
    },
    action: {
        type: [Object, null]
    }
})

defineEmits(['action'])
</script>
