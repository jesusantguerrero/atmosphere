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
<main class="space-y-4">
    <CreditCardsLedger
        :accounts="creditCards"
        :class="[]"
        v-model="selectedCard"
        class="rounded-b-md md:rounded-md min-h-min bg-base-lvl-3 mt-2"
    />

    <section class="bg-base px-4 py-3 rounded-md space-y-3">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="text-xs text-body-1/60 font-medium">Usage</span>
                <p class="text-lg font-bold text-body-1">
                    {{ currentCardUsage }} %
                </p>
            </div>
            <div>
                <span class="text-xs text-body-1/60 font-medium">Statement Day</span>
                <p class="text-lg font-bold text-body-1">
                    {{ selectedCard?.credit_closing_day }}<span class="text-xs font-normal">th</span>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="text-xs text-body-1/60 font-medium">Status</span>
                <p class="text-sm font-semibold text-green-600 capitalize">
                    {{ $t('active') }}
                </p>
            </div>
            <div>
                <span class="text-xs text-body-1/60 font-medium">Credit Limit</span>
                <p class="text-sm font-semibold text-body-1">
                    {{ formatMoney(selectedCard?.credit_limit, selectedCard?.currency_code) }}
                </p>
            </div>
        </div>
    </section>

    <div class="w-full py-1">
        <header class="flex justify-between items-center mb-3 px-2">
            <h4 class="font-bold text-lg"> Credit Cards Pending </h4>
            <button class="px-2 text-primary text-sm hover:underline"> See all </button>
        </header>

        <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
            <button
                v-for="item in creditCards"
                :key="item.id"
                @click="selectedCard = item"
                class="w-full group"
            >
                <div class="flex items-center justify-between p-3 rounded-lg border-2 transition-all"
                    :class="selectedCard?.id === item.id
                        ? 'border-primary bg-primary/5 shadow-md'
                        : 'border-base-lvl-3 bg-base-lvl-2 hover:border-primary/50 hover:bg-base-lvl-3'"
                >
                    <div class="flex items-center gap-3 flex-1 text-left">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg"
                            :class="selectedCard?.id === item.id
                                ? 'bg-primary/20 text-primary'
                                : 'bg-base-lvl-3 text-body-1/60 group-hover:bg-base-lvl-4'"
                        >
                            <IMdiCreditCard class="w-5 h-5" />
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-body-1">{{ item.name }}</p>
                            <p class="text-sm text-body-1/60">{{ item.currency_code }} • Due: {{ item.credit_closing_day }}th</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-error">{{ formatMoney(item?.balance) }}</p>
                        <p class="text-xs text-body-1/60">to pay</p>
                    </div>
                </div>
            </button>
        </div>
    </div>
</main>
</template>
