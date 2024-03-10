<script setup lang="ts">
    import { NPopover } from "naive-ui"
    import { computed } from "vue";

    const props = defineProps<{
        modelValue: string;
        type: boolean;
    }>();

    const colors = [
        '#037F4C',
        '#00C875',
        '#9CD326',
        '#CAB641',
        '#FFCB00',
        '#784BD1',
        '#A25DDC',
        '#0086C0',
        '#579BFC',
        '#66CCFF',
        '#BB3354',
        '#E2445C',
        '#FF158A',
        '#FF5AC4',
        '#FF642E',
        '#7F5347',
        '#C4C4C4',
        '#808080'
    ]

    const styleColor = computed(() => {
        return { 
            backgroundColor:  props.modelValue || colors[0] 
        }
    })
</script>

<template>
<div>
    <NPopover trigger="click" :disabled="disabled">
        <template #trigger>
            <div class="w-4 h-4 rounded-sm cursor-pointer" :style="styleColor" />
        </template>
        <div class="grid grid-cols-5 gap-1">
            <div
                v-for="color in colors"
                class="w-4 h-4 border border-transparent rounded-sm cursor-pointer hover:border-slate-400"
                :style="{backgroundColor: color }"
                :key="color"
                @click="$emit('update:modelValue', color)"
            />
        </div>
    </NPopover>
</div>
</template>
