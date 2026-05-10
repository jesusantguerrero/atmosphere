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

const emit = defineEmits(["change", "update:model-value"]);

const emitChange = (value: string) => {
    emit('change', value)
    emit('update:model-value', value)
}
</script>

<template>
    <section class="flex overflow-hidden text-sm font-medium border border-base-lvl-2 rounded-md bg-base-lvl-3 text-body-1/70 min-w-max">
        <button
            v-for="(item, statusName) in statuses"
            class="px-3 py-1.5 flex items-center transition-colors border-r border-base-lvl-2 last:border-r-0 hover:bg-base-lvl-2 hover:text-body-1"
            :class="{'!bg-primary !text-white hover:!bg-primary-dark': isSelected(statusName)}"
            :key="statusName"
            @click="emitChange(item.value || statusName)">
                {{ item.label }}
        </button>
    </section>
</template>
