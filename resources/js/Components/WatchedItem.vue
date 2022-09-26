<template>
  <article class="overflow-hidden bg-white rounded-lg shadow-md">
    <h4 class="p-4 font-bold">
      <span class="text-primary">{{ item.name }}</span>
      <p>
        <small>{{ item.description }}</small>
      </p>
    </h4>
    <section class="p-4">
      <div class="rounded-md bg-body-1/5 px-4 py-2">
        <h5 class="text-body-1/80">This Month</h5>
        <h4>{{ formatMoney(item.data.month.total) }}</h4>
      </div>
      <div class="flex justify-between text-xs space-x-2">
        <div class="px-5 py-2 mt-4 bg-body-1/5 w-full bg-opacity-25 rounded-md">
          Last month:
          <span class="font-bold">{{ lastMonthVariance }}%</span>
        </div>
        <div class="px-5 py-2 mt-4 bg-body-1/5 w-full bg-opacity-25 rounded-md">
          Target:
          <span class="font-bold">{{ formatMoney(item.data.month.target || 0) }}</span>
        </div>
      </div>
    </section>
    <section class="grid grid-cols-2 p-4 pb-2 bg-primary text-white">
      <div class="text-center">
        <h4>{{ formatDate(item.data.month.lastTransactionDate, '--')}}</h4>
        <span>Last transaction date</span>
      </div>
      <div class="text-center">
        <h4>{{ item.data.month.transactionsCount || 0 }}</h4>
        <span>Transactions</span>
      </div>
    </section>
    <footer class="text-primary px-4 pt-2 pb-4">
      <button>Show more</button>
    </footer>
  </article>
</template>

<script setup>
import { computed } from "vue";

import LogerButton from "./atoms/LogerButton.vue";

import { formatMoney, formatDate } from "@/utils";
import { getVariances } from "@/domains/transactions";

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
});

const lastMonthVariance = computed(() => {
  return getVariances(props.item.data.month.total, props.item.data.prevMonth.total) || 0;
});
</script>
