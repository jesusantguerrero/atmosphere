<template>
    <div class="grid grid-cols-3 overflow-hidden text-lg rounded-lg">
        <div
            v-for="type in transactionTypes"
            :key="type.value"
            :options="transactionTypes"
            @click="$emit('update:modelValue', type.value)"
            class="py-1 font-bold text-center cursor-pointer"
            :class="[modelValue == type.value ? 'bg-primary hover:bg-primary text-white' : 'hover:bg-base-lvl-3 text-body bg-base-lvl-2']"
        >
            {{ type.label }}
        </div>
    </div>

</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: Object
    }
});

const emit = defineEmits(['update:modelValue'])

const transactionTypes = [{
        value: 'DEPOSIT',
        label: 'Income',
    }, {
        value: 'WITHDRAW',
        label: 'Expense'
    }, {
        value: 'TRANSFER',
        label: 'Transfer'
    }
];
</script>
