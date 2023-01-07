<template>
<div>
    <img src="/logo.png" :style="{height: sizeClass }" class="mx-auto"/>
    <small class="text-xs text-body-1/80 font-brand">The Digital Home</small>
</div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    size: {
        type: String,
        validator(value) {
            const sizes = ['small', 'medium', 'large']
            return sizes.includes(value)
        },
        default: 'large'
    },
});

const sizes = {
    small: '14px',
    medium: '24px',
    large: '32px'
}

const sizeClass = computed(() => {
    return sizes[props.size]
})
</script>
