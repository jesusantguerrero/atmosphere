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
    <div class="grid grid-cols-3 overflow-hidden text-lg rounded-lg" role="tablist">
        <button
            v-for="type in transactionTypes"
            :key="type.value"
            type="button"
            role="tab"
            :aria-selected="modelValue == type.value"
            class="py-1 font-bold text-center cursor-pointer transition"
            :class="modelValue == type.value
                ? type.activeClass
                : 'hover:bg-base-lvl-3 text-body bg-base-lvl-2'"
            @click="$emit('update:modelValue', type.value)"
        >
            {{ $t(type.label) }}
        </button>
    </div>
</template>
