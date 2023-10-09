<script setup lang="ts">
import CardStat from "@/Components/widgets/CardStat.vue";
import { formatMoney, formatMonth, MonthTypeFormat } from "@/utils";


interface MonthStat {
    total: number,
    currency_code: string,
    lastTransactionDate: string,
    transactionsCount: number
}

interface WatchlistResource {
    month: MonthStat;
    prevMonth: MonthStat;
    transactions: Record<string, {
        data: Record<string, string>[]
    }>
}

defineProps<{
  item: WatchlistResource;
  startDate: Date;
}>()
</script>

<template>
    <article  class="rounded-md bg-base-lvl-3 cursor-pointer w-full">
        <header class="flex items-center px-4 py-2">
            <section class="rounded-md h-4 w-4 bg-primary mr-2">

            </section>
            <h4 class="font-bold">
                {{ item.name }}
            </h4>
        </header>
        <main class="py-2 px-4 flex space-x-2">
            <CardStat
                label="This month"
                :value="formatMoney(item.data?.month?.total)"
            />
            <CardStat
                label="transactions"
                :value="item.data?.month?.transactionsCount"
            />
        </main>
        <footer class="border-t px-4 py-1 space-x-2">
            <span class="bg-base-lvl-1 px-2 rounded-md">
                {{ item.type }}
            </span>
            <span class="bg-base-lvl-1 px-2 rounded-md">
                {{ formatMonth(startDate, "MMMM") }}
            </span>

        </footer>
    </article>
</template>


