<script lang="ts" setup>
import { computed, ref } from 'vue';
import exactMathNode from '@/plugins/exactMath';

import CreditCardsLedger from '@/domains/transactions/components/CreditCardsLedger.vue';

import { IAccount } from '@/domains/transactions/models';
import { formatMoney } from '@/utils';

const props = defineProps<{
    accounts: IAccount[];
}>()


const creditCards = computed(() => {
    return props.accounts.filter((account) => account.credit_closing_day)
    .toSorted((a, b) => a.credit_closing_day - b.credit_closing_day)
    .toReversed()
})

const selectedCard = ref<IAccount>();

const currentCardUsage = computed(() => {
    return Math.abs((selectedCard.value?.balance / selectedCard.value?.credit_limit * 100).toFixed(2))
})

const budgetAccountsTotal =  computed(() => {
    return props.accounts.reduce((total, account) => {
        return exactMathNode.add(total, account?.balance)
    }, 0)
})
</script>

<template>
<main class="space-y-2">
    <!-- Card Visual Display - Compact -->
    <CreditCardsLedger
        :accounts="creditCards"
        v-model="selectedCard"
        class="rounded-md bg-base-lvl-3 max-h-32"
    />

    <!-- Card Details Section - Compact inline view -->
    <section v-if="selectedCard" class="bg-base px-3 py-2 rounded-md">
        <div class="grid grid-cols-4 gap-2 text-center text-xs">
            <div>
                <span class="text-body-1/60 font-medium block">Usage</span>
                <p class="font-bold text-sm text-body-1">{{ currentCardUsage }}%</p>
            </div>
            <div>
                <span class="text-body-1/60 font-medium block">Due</span>
                <p class="font-bold text-sm text-body-1">{{ selectedCard.credit_closing_day }}<span class="text-xs font-normal">th</span></p>
            </div>
            <div>
                <span class="text-body-1/60 font-medium block">Limit</span>
                <p class="font-bold text-sm text-body-1">{{ formatMoney(selectedCard.credit_limit, selectedCard.currency_code) }}</p>
            </div>
            <div>
                <span class="text-body-1/60 font-medium block">Status</span>
                <p class="font-bold text-xs text-green-600">Active</p>
            </div>
        </div>
    </section>

    <!-- Cards List - Compact with reduced max-height -->
    <div class="w-full">
        <header class="flex justify-between items-center mb-2 px-2">
            <h4 class="font-semibold text-sm">Cards</h4>
            <button class="text-primary text-xs hover:underline">All</button>
        </header>

        <div class="space-y-1 max-h-48 overflow-y-auto pr-1">
            <button
                v-for="item in creditCards"
                :key="item.id"
                @click="selectedCard = item"
                class="w-full group"
            >
                <div class="flex items-center justify-between p-2 rounded-lg border transition-all text-sm"
                    :class="selectedCard?.id === item.id
                        ? 'border-primary bg-primary/5 shadow-sm'
                        : 'border-base-lvl-3 bg-base-lvl-2 hover:border-primary/30'"
                >
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <div class="flex items-center justify-center w-8 h-8 rounded flex-shrink-0"
                            :class="selectedCard?.id === item.id
                                ? 'bg-primary/20 text-primary'
                                : 'bg-base-lvl-3 text-body-1/60'"
                        >
                            <IMdiCreditCard class="w-4 h-4" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-body-1 truncate text-xs">{{ item.name }}</p>
                            <p class="text-xs text-body-1/60">{{ item.credit_closing_day }}th</p>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0 ml-2">
                        <p class="font-bold text-error text-xs">{{ formatMoney(item.balance) }}</p>
                    </div>
                </div>
            </button>
        </div>
    </div>
</main>
</template>
