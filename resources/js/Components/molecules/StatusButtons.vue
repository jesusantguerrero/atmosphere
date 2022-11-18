<template>
    <section class="flex text-white rounded-md bg-primary border border-primary min-w-max overflow-hidden">
        <button
            v-for="(item, statusName) in statuses"
            class="px-2 py-1.5 flex items-center border border-transparent hover:bg-accent"
            :class="{'bg-white text-primary border-primary hover:text-white': isSelected(statusName)}"
            :key="statusName"
            @click="$emit('change', item.value)">
                {{ item.label }}
        </button>
    </section>
</template>

<script setup>
const props = defineProps({
    statuses: {
        type: Object,
        required: true
    },
    modelValue: {
        type: String
    }
})


const isSelected = (itemValue) => {
    return itemValue == props.modelValue
}
</script>
