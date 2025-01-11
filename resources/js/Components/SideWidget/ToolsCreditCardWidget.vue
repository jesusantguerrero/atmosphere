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

    <section class="bg-base px-4 py-2 rounded-md">
        <header class="flex justify-between">
            <section>
                <span>Your balance</span>
                <p>
                    {{  selectedCard?.balance }}
                </p>
            </section>
            <section>
                <span>Usage %</span>
                <p>
                    {{   currentCardUsage }} %
                </p>
            </section>
        </header>
        <footer class="flex justify-between mt-3">
            <section>
                <span>Currency</span>
                <p>
                    {{  selectedCard?.currency_code }}
                </p>
            </section>
            <section>
                <span>Status</span>
                <p class="text-center capitalize">
                    {{  $t('active') }}
                </p>
            </section>
            <section>
                <span>Statement Date</span>
                <p class="text-center">
                    {{  selectedCard?.credit_closing_day }}
                </p>
            </section>
        </footer>
    </section>

    <div class="w-full py-1">
        <header class="flex justify-between">
            <h4 class="px-2 text-body-1/80"> Tarjetas en corte </h4>
            <button class="px-2 text-primary"> See all </button>
        </header>

        <LogerButtonTab class="flex items-center justify-between w-full font-bold pt-4" v-for="item in creditCards">
            <span class="flex items-center">
                <IMdiStar class="mr-2 text-md text-secondary" />
                {{  item.name  }}

            </span>
            <span class="text-secondary">
                Pay {{  formatMoney(item?.balance) }}
            </span>
        </LogerButtonTab>
    </div>
</main>
</template>
