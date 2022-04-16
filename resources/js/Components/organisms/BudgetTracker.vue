<template>
    <div class="px-5 py-3 transition bg-white border divide-y rounded-lg shadow-md">
        <div class="pb-2 md:justify-between md:flex">
            <h1 class="font-bold text-gray-500">
                Welcome to Loger <span class="text-sm text-pink-500">{{ username }}</span>
            </h1>
            <div class="space-x-2">
                <AtButton class="text-sm text-white bg-pink-500" @click="isTransferModalOpen=true">
                    <i class="fa fa-exchange-alt"></i>
                    Add transaction
                </AtButton>
                <AtButton class="text-sm text-white bg-pink-500" @click="$inertia.visit(route('budgets.index'))">
                    <i class="fa fa-wallet"></i>
                    Edit budget
                </AtButton>
            </div>
        </div>
        <div class="flex py-3">
            <div class="w-full transition cursor-pointer hover:opacity-75"
                @click="$emit('section-click', sectionName)"
                :key="sectionName"
                v-for="(section, sectionName) in sections"
            >
                <h4 class="text-gray-500">{{ section.label }}</h4>
                <SectionTitle>{{ formatMoney(section.value) }}</SectionTitle>
            </div>
        </div>
        <slot></slot>
        <transaction-modal
            @close="isTransferModalOpen=false"
            v-model:show="isTransferModalOpen"
        />
    </div>
</template>

<script setup>
import SectionTitle from "../atoms/SectionTitle";
import { AtButton } from "atmosphere-ui";
import { computed, ref } from "@vue/runtime-core";
import TransactionModal from "../TransactionModal.vue"
import formatMoney from "../../utils/formatMoney"

const props = defineProps({
    username: {
        type: String,
        required: true
    },
    budget: {
        type: Number
    },
    expenses: {
        type: Number
    }
})

const isTransferModalOpen = ref(false);
const openedTransaction = ref(null);

const sections = computed(() => ({
    expenses: {
        label: 'Current Expenses',
        value: props.expenses
    },
    budget: {
        label: 'Monthly Budget',
        value: props.budget
    }
}));

const setTransaction = (transaction) => {
    openedTransaction.value = transaction;
    isTransferModalOpen.value = true;
}

// defineExpose({
//     setTransaction
// })
</script>
