<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useLocalStorage } from '@vueuse/core';

import IconClose from '@/Components/icons/IconClose.vue';

/**
 * Guided empty state for the Budget page.
 *
 * Shown when this month has zero assignments AND the user hasn't dismissed
 * the panel. Replaces the previous generic "This is your budget" message with
 * a 3-step walkthrough tailored to how a newcomer actually gets from
 * registration to a working budget in Loger:
 *
 *   1. Verify accounts (needed so any inflow lands in Ready to Assign)
 *   2. Log a first income transaction (populates Ready to Assign)
 *   3. Assign that income to the categories that were seeded on signup
 *
 * The dismiss is remembered in localStorage — power-users returning to a
 * fresh month don't need to see it every time. Auto-hides once the user has
 * assigned at least one peso this month (`monthIsEmpty=false`), so it never
 * competes with real budget data.
 */
const props = defineProps<{
  monthIsEmpty: boolean;
  readyToAssign?: number;
  hasAccounts?: boolean;
}>();

const dismissed = useLocalStorage('loger-budget-onboarding-dismissed', false);

const shouldShow = computed(() => props.monthIsEmpty && !dismissed.value);

const readyToAssignHasFunds = computed(() => Number(props.readyToAssign ?? 0) > 0);

const step1Complete = computed(() => props.hasAccounts === true);
const step2Complete = computed(() => readyToAssignHasFunds.value);
const step3Complete = computed(() => false); // once step 3 done, monthIsEmpty flips false and the whole banner hides

const goToAccounts = () => router.visit('/finance');
const goToNewIncome = () => {
  // Match the top-bar "+ New → Income" flow — expose a query param the
  // TransactionModal listens for on mount.
  router.visit('/finance/transactions?new=income');
};
const scrollToTable = () => {
  const el = document.querySelector('[data-budget-table]');
  el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const dismiss = () => {
  dismissed.value = true;
};
</script>

<template>
  <article
    v-if="shouldShow"
    class="relative overflow-hidden rounded-lg border border-primary/30 bg-gradient-to-br from-primary/5 to-transparent px-6 py-5 mb-3"
  >
    <button
      type="button"
      class="absolute top-3 right-3 text-body-1/60 hover:text-body focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 rounded"
      :title="$t('Dismiss')"
      :aria-label="$t('Dismiss')"
      @click="dismiss"
    >
      <IconClose />
    </button>

    <header class="pr-8">
      <h3 class="text-lg font-bold text-body">
        {{ $t('Welcome to your budget') }}
      </h3>
      <p class="mt-1 text-sm text-body-1/80 max-w-2xl">
        {{ $t('Loger uses zero-based budgeting: every peso gets a job before you spend it. Three steps to a working budget:') }}
      </p>
    </header>

    <ol class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
      <!-- Step 1: accounts -->
      <li
        class="rounded-md border p-3 flex flex-col gap-2 transition"
        :class="step1Complete
          ? 'border-emerald-500/40 bg-emerald-500/5'
          : 'border-base bg-base-lvl-3'"
      >
        <div class="flex items-center gap-2">
          <span
            class="flex items-center justify-center h-6 w-6 rounded-full text-xs font-bold"
            :class="step1Complete
              ? 'bg-emerald-500 text-white'
              : 'bg-primary/10 text-primary'"
          >
            <span v-if="step1Complete">✓</span>
            <span v-else>1</span>
          </span>
          <h4 class="text-sm font-semibold text-body">{{ $t('Verify your accounts') }}</h4>
        </div>
        <p class="text-xs text-body-1/70">
          {{ $t('Bank, credit cards, cash — Loger needs to know where your money lives.') }}
        </p>
        <button
          type="button"
          class="mt-auto text-xs font-medium text-primary hover:underline text-left"
          @click="goToAccounts"
        >
          {{ step1Complete ? $t('Manage accounts') : $t('Go to accounts') }} →
        </button>
      </li>

      <!-- Step 2: first income -->
      <li
        class="rounded-md border p-3 flex flex-col gap-2 transition"
        :class="step2Complete
          ? 'border-emerald-500/40 bg-emerald-500/5'
          : 'border-base bg-base-lvl-3'"
      >
        <div class="flex items-center gap-2">
          <span
            class="flex items-center justify-center h-6 w-6 rounded-full text-xs font-bold"
            :class="step2Complete
              ? 'bg-emerald-500 text-white'
              : 'bg-primary/10 text-primary'"
          >
            <span v-if="step2Complete">✓</span>
            <span v-else>2</span>
          </span>
          <h4 class="text-sm font-semibold text-body">{{ $t('Log an income') }}</h4>
        </div>
        <p class="text-xs text-body-1/70">
          {{ $t('A salary, a payment received — anything that fills your Ready to Assign pool.') }}
        </p>
        <button
          type="button"
          class="mt-auto text-xs font-medium text-primary hover:underline text-left"
          @click="goToNewIncome"
        >
          {{ step2Complete ? $t('Log another income') : $t('Add income') }} →
        </button>
      </li>

      <!-- Step 3: assign -->
      <li
        class="rounded-md border p-3 flex flex-col gap-2 transition"
        :class="step3Complete
          ? 'border-emerald-500/40 bg-emerald-500/5'
          : 'border-base bg-base-lvl-3'"
      >
        <div class="flex items-center gap-2">
          <span
            class="flex items-center justify-center h-6 w-6 rounded-full text-xs font-bold bg-primary/10 text-primary"
          >
            3
          </span>
          <h4 class="text-sm font-semibold text-body">{{ $t('Assign to categories') }}</h4>
        </div>
        <p class="text-xs text-body-1/70">
          {{ $t('Give every peso a job — rent, groceries, savings. Type into the ASSIGNED column below.') }}
        </p>
        <button
          type="button"
          class="mt-auto text-xs font-medium text-primary hover:underline text-left"
          @click="scrollToTable"
        >
          {{ $t('Jump to categories') }} ↓
        </button>
      </li>
    </ol>
  </article>
</template>
