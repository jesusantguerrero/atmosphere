<script setup lang="ts">
defineProps<{
    modelValue: string;
}>();

defineEmits(['update:modelValue']);

// Each direction gets a semantic active color so the user knows what mode they're in
// at a glance: green = money coming in, red = money going out, indigo = neutral movement.
const transactionTypes = [
    { value: 'DEPOSIT', label: 'Income', activeClass: 'bg-success hover:bg-success text-white' },
    { value: 'WITHDRAW', label: 'Expense', activeClass: 'bg-error hover:bg-error text-white' },
    { value: 'TRANSFER', label: 'Transfer', activeClass: 'bg-secondary hover:bg-secondary text-white' },
];
</script>

<template>
    <div class="grid grid-cols-3 gap-1 p-1 text-lg rounded-lg bg-base-lvl-1 border border-base" role="tablist">
        <button
            v-for="type in transactionTypes"
            :key="type.value"
            type="button"
            role="tab"
            :aria-selected="modelValue == type.value"
            class="py-1.5 font-bold text-center rounded-md cursor-pointer transition"
            :class="modelValue == type.value
                ? type.activeClass
                : 'bg-transparent text-body-1 hover:text-body hover:bg-base-lvl-2/60'"
            @click="$emit('update:modelValue', type.value)"
        >
            {{ $t(type.label) }}
        </button>
    </div>
</template>
