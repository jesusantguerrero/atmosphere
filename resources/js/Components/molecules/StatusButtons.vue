<script setup lang="ts">
const props = defineProps({
    statuses: {
        type: Object,
        required: true
    },
    modelValue: {
        type: String
    }
})

const isSelected = (itemValue: string) => {
    return itemValue == props.modelValue
}
</script>

<template>
    <section class="flex overflow-hidden font-bold border rounded-md bg-primary/10 text-primary border-primary min-w-max">
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
