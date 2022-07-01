<template>
    <div class="px-5 py-3 transition border divide-y rounded-lg shadow-md divide-slate-700 border-slate-700 bg-slate-600">
        <div class="items-center pb-2 md:justify-between md:flex">
            <h1 class="font-bold text-gray-200">
                Welcome to Loger <span class="text-pink-400">{{ username }}</span>
            </h1>
            <div class="space-x-2">
                <AtButton class="text-sm text-white bg-pink-400" rounded @click="isTransferModalOpen=true">
                    <i class="fa fa-exchange-alt"></i>
                    Add transaction
                </AtButton>
                <AtButton class="text-sm text-white bg-pink-400" rounded @click="$inertia.visit(route('budgets.index'))">
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
                <h4 class="text-gray-200">{{ section.label }}</h4>
                <SectionTitle class="mt-2">
                    {{ formatMoney(section.value) }}
                </SectionTitle>
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
import { AtButton } from "atmosphere-ui";
import { computed, ref } from "vue";
import SectionTitle from "../atoms/SectionTitle.vue";
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
