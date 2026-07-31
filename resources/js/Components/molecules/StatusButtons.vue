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
    <!-- Shared segmented filter — same visual as the register's
         All/Debits/Credits control, so every mutually-exclusive view filter
         reads the same across Finance. -->
    <section class="inline-flex p-0.5 text-xs rounded-lg bg-base-lvl-1 min-w-max">
        <button
            v-for="(item, statusName) in statuses"
            class="px-3 py-1.5 flex items-center rounded-md transition-colors"
            :class="isSelected(statusName)
                ? 'bg-base-lvl-3 text-body font-semibold shadow-sm'
                : 'text-body-1/60 hover:text-body'"
            :key="statusName"
            @click="emitChange(item.value || statusName)">
                {{ item.label }}
        </button>
    </section>
</template>
