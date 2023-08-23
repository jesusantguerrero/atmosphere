<script setup lang="ts">
import { computed } from "vue";

import { formatMoney, formatDate } from "@/utils";
import { getVariances } from "@/Domains/transactions";

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

<template>
  <article class="overflow-hidden bg-white rounded-lg shadow-md">
    <h4 class="p-4 font-bold">
      <span class="text-primary">{{ item.name }}</span>
      <p>
        <small>{{ item.description }}</small>
      </p>
    </h4>
    <section class="p-4">
      <div class="px-4 py-2 rounded-md bg-secondary/10">
        <h5 class="text-body-1/80">This Month</h5>
        <h4 class="font-bold text-secondary">{{ formatMoney(item.data.month.total) }}</h4>
      </div>
      <div class="flex justify-between space-x-2 text-xs">
        <div class="w-full px-5 py-2 mt-4 bg-opacity-25 rounded-md bg-secondary/10">
            <span class="text-body-1/80">
                Last month:
            </span>
          <span class="font-bold text-secondary">{{ lastMonthVariance }}%</span>
        </div>
        <div class="w-full px-5 py-2 mt-4 bg-opacity-25 rounded-md bg-secondary/10">
            <span class="text-body-1/80">
                Target:

            </span>
          <span class="font-bold text-secondary">{{ formatMoney(item.data.month.target || 0) }}</span>
        </div>
      </div>
    </section>
    <section class="grid grid-cols-2 p-4 pb-2 mx-4 text-white bg-primary rounded-xl">
      <div class="text-center">
        <h4>{{ formatDate(item.data.month.lastTransactionDate, '--')}}</h4>
        <span class="text-xs">Last transaction date</span>
      </div>
      <div class="text-center">
        <h4>{{ item.data.month.transactionsCount || 0 }}</h4>
        <span class="text-xs">Transactions</span>
      </div>
    </section>
    <footer class="px-4 pt-2 pb-4 text-primary">
      <button>Show more</button>
    </footer>
  </article>
</template>
