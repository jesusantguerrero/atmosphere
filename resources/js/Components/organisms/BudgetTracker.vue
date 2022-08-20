<template>
    <div class="px-5 py-3 transition border divide-y rounded-lg shadow-xl divide-base border-base bg-base-lvl-3">
        <div class="items-center pb-2 md:justify-between md:flex">
            <h1 class="font-bold text-body">
                Welcome to Loger <span class="text-primary">{{ username }}</span>
            </h1>
            <div class="space-x-2">
                <AtButton class="text-sm text-primary" rounded @click="$inertia.visit(route('budgets.index'))">
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
                <h4 class="text-body">{{ section.label }}</h4>
                <SectionTitle class="flex flex-col mt-2 space-y-2" v-if="isMultiple(section.value)">
                    <span class="relative w-72" v-for="currency in section.value">
                        <NumberHider />
                        {{ formatMoney(currency.total, currency.currency_code) }}
                    </span>
                </SectionTitle>
                <SectionTitle class="mt-2" v-else>
                    <span class="relative w-72">
                        <NumberHider />
                        {{ formatMoney(section.value) }}
                    </span>
                </SectionTitle>
            </div>
        </div>
        <slot></slot>
        <transaction-modal
            v-model:show="isTransferModalOpen"
        />
    </div>
</template>

<script setup>
import { AtButton } from "atmosphere-ui";
import { computed, ref } from "vue";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import TransactionModal from "@/Components/TransactionModal.vue"
import NumberHider from "@/Components/molecules/NumberHider.vue";
import formatMoney from "@/utils/formatMoney"
import { useTransferModal } from "@/utils/useTransferModal";

const props = defineProps({
    username: {
        type: String,
        required: true
    },
    budget: {
        type: Number
    },
    expenses: {
        type: Array
    }
})

const { isOpen: isTransferModalOpen } = useTransferModal()

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

const isMultiple = (value) => {
    return Array.isArray(value)
}
</script>
