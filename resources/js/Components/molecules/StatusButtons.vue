<template>
    <section class="flex rounded-md border  bg-primary/10 font-bold text-primary border-primary  min-w-max overflow-hidden">
        <button
            v-for="(item, statusName) in statuses"
            class="px-2 py-1.5 flex items-center border border-transparent hover:bg-primary/5"
            :class="{'text-white bg-primary border border-primary hover:text-primary': isSelected(statusName)}"
            :key="statusName"
            @click="$emit('change', item.value || statusName)">
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
