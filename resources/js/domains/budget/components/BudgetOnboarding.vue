<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useLocalStorage } from '@vueuse/core';

import IconClose from '@/Components/icons/IconClose.vue';

/**
 * Guided empty state for the Budget page — Mercury-style floating panel.
 *
 * Design choice (vs inline banner): a fixed bottom-right card that doesn't
 * push the budget UI around, plus a collapse-to-launcher pattern so a user
 * who dismissed it can still recall the checklist without losing their place.
 * Auto-hides entirely once the user has made their first assignment this
 * month (`monthIsEmpty=false`) — the checklist is not a promotion, it's
 * newcomer wayfinding.
 *
 * Positioning: `right-[100px]` clears the 76px right-side quick-panel rail
 * that AppLayout renders in desktop.
 *
 * The three steps track observable state:
 *   1. hasAccounts       — user has at least one Account
 *   2. readyToAssign > 0 — an income has landed in the RTA pool
 *   3. (implicit) — once complete, monthIsEmpty flips and the whole
 *      floater disappears; no need to render a checkmark for it.
 *
 * Dismiss persists in localStorage; collapse (to launcher) doesn't. This
 * lets a returning user always re-open the checklist without a permanent
 * "off" gesture, while still respecting a hard-dismiss.
 */
const props = defineProps<{
  monthIsEmpty: boolean;
  readyToAssign?: number;
  hasAccounts?: boolean;
}>();

const dismissed = useLocalStorage('loger-budget-onboarding-dismissed', false);
const collapsed = ref(false);

const shouldShow = computed(() => props.monthIsEmpty && !dismissed.value);

const readyToAssignHasFunds = computed(() => Number(props.readyToAssign ?? 0) > 0);
const step1Complete = computed(() => props.hasAccounts === true);
const step2Complete = computed(() => readyToAssignHasFunds.value);

const completedSteps = computed(() => {
  let n = 0;
  if (step1Complete.value) n++;
  if (step2Complete.value) n++;
  return n;
});

const goToAccounts = () => router.visit('/finance');
const goToNewIncome = () => router.visit('/finance/transactions?new=income');
const scrollToTable = () => {
  const el = document.querySelector('[data-budget-table]');
  el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const dismiss = () => {
  dismissed.value = true;
};
const collapse = () => {
  collapsed.value = true;
};
const expand = () => {
  collapsed.value = false;
};
</script>

<template>
  <!-- Expanded panel -->
  <aside
    v-if="shouldShow && !collapsed"
    class="fixed bottom-4 right-[100px] z-40 w-[360px] max-w-[calc(100vw-32px)] rounded-xl border border-base bg-base-lvl-3 shadow-2xl overflow-hidden"
  >
    <header class="flex items-start justify-between gap-2 px-5 pt-4 pb-3 border-b border-base">
      <div class="flex-1 min-w-0">
        <h3 class="text-base font-bold text-body">
          {{ $t('Set up your budget') }}
        </h3>
        <p class="text-xs text-body-1/70 mt-0.5">
          {{ completedSteps }} / 3 {{ $t('done') }}
        </p>
      </div>
      <button
        type="button"
        class="flex items-center justify-center h-7 w-7 rounded-md text-body-1/60 hover:bg-base-lvl-2 hover:text-body focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 transition"
        :title="$t('Collapse')"
        :aria-label="$t('Collapse')"
        @click="collapse"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9" />
        </svg>
      </button>
    </header>

    <ul class="py-2">
      <!-- Step 1: accounts -->
      <li>
        <button
          type="button"
          class="w-full flex items-center gap-3 px-5 py-3 hover:bg-base-lvl-2 transition text-left focus:outline-none focus-visible:bg-base-lvl-2"
          @click="goToAccounts"
        >
          <span
            class="flex-shrink-0 flex items-center justify-center h-9 w-9 rounded-lg text-sm font-bold"
            :class="step1Complete
              ? 'bg-emerald-500/15 text-emerald-500'
              : 'bg-primary/10 text-primary'"
          >
            <svg v-if="step1Complete" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12" />
            </svg>
            <span v-else>1</span>
          </span>
          <span class="flex-1 min-w-0">
            <span class="block text-sm font-medium text-body">{{ $t('Verify your accounts') }}</span>
            <span class="block text-xs text-body-1/60 mt-0.5 truncate">
              {{ $t('Bank, credit cards, cash') }}
            </span>
          </span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-body-1/40 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6" />
          </svg>
        </button>
      </li>

      <!-- Step 2: first income -->
      <li>
        <button
          type="button"
          class="w-full flex items-center gap-3 px-5 py-3 hover:bg-base-lvl-2 transition text-left focus:outline-none focus-visible:bg-base-lvl-2"
          @click="goToNewIncome"
        >
          <span
            class="flex-shrink-0 flex items-center justify-center h-9 w-9 rounded-lg text-sm font-bold"
            :class="step2Complete
              ? 'bg-emerald-500/15 text-emerald-500'
              : 'bg-primary/10 text-primary'"
          >
            <svg v-if="step2Complete" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12" />
            </svg>
            <span v-else>2</span>
          </span>
          <span class="flex-1 min-w-0">
            <span class="block text-sm font-medium text-body">{{ $t('Log an income') }}</span>
            <span class="block text-xs text-body-1/60 mt-0.5 truncate">
              {{ $t('So you have money to assign') }}
            </span>
          </span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-body-1/40 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6" />
          </svg>
        </button>
      </li>

      <!-- Step 3: assign -->
      <li>
        <button
          type="button"
          class="w-full flex items-center gap-3 px-5 py-3 hover:bg-base-lvl-2 transition text-left focus:outline-none focus-visible:bg-base-lvl-2"
          @click="scrollToTable"
        >
          <span class="flex-shrink-0 flex items-center justify-center h-9 w-9 rounded-lg text-sm font-bold bg-primary/10 text-primary">
            3
          </span>
          <span class="flex-1 min-w-0">
            <span class="block text-sm font-medium text-body">{{ $t('Assign to categories') }}</span>
            <span class="block text-xs text-body-1/60 mt-0.5 truncate">
              {{ $t('Give every peso a job below') }}
            </span>
          </span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-body-1/40 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6" />
          </svg>
        </button>
      </li>
    </ul>

    <footer class="px-5 py-2 border-t border-base flex items-center justify-between">
      <span class="text-[11px] text-body-1/50">{{ $t('Zero-based budgeting') }}</span>
      <button
        type="button"
        class="text-[11px] font-medium text-body-1/60 hover:text-body-1 transition"
        @click="dismiss"
      >
        {{ $t('Don\'t show again') }}
      </button>
    </footer>
  </aside>

  <!-- Collapsed launcher — small floating button when the panel is minimized
       but not permanently dismissed. Click to re-expand. -->
  <button
    v-if="shouldShow && collapsed"
    type="button"
    class="fixed bottom-4 right-[100px] z-40 flex items-center gap-2 pl-3 pr-4 py-2.5 rounded-full bg-primary text-white shadow-xl hover:shadow-2xl hover:bg-primary/90 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
    :title="$t('Show budget setup')"
    :aria-label="$t('Show budget setup')"
    @click="expand"
  >
    <span class="flex items-center justify-center h-6 w-6 rounded-full bg-white/20 text-xs font-bold">
      {{ completedSteps }}/3
    </span>
    <span class="text-sm font-medium">{{ $t('Set up budget') }}</span>
  </button>
</template>
