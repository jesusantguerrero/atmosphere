<template>
    <div :class="col.class">
        {{col.render ? col.render(value) : value }}
    </div>
</template>

<script setup>
defineProps({
    col: {
        type: Object,
        required: true
    },
    value: {
        type: [String, Number, Date],
    }
})
</script>
