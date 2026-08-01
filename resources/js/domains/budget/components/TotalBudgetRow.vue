<script setup lang="ts">
import { computed, toRefs } from "vue";
import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";

/**
 * TotalBudgetRow
 *
 * Sanity-check footer for the budget table. Sums Assigned / Spent /
 * Available across every visible group so the user can verify the month
 * totals without scrolling category-by-category. Renders in the same
 * column geometry as BudgetItem to keep the grid aligned.
 *
 * Non-destructive: expects the same `budgets` array Budget.vue already
 * has; nothing else in the tree needs to know it exists.
 */

const props = defineProps<{
    budgets: any[];
    isMobile?: boolean;
}>();

const { budgets } = toRefs(props);

const totals = computed(() => {
    const groups = budgets.value ?? [];
    return groups.reduce(
        (acc: { assigned: number; activity: number; available: number }, group: any) => {
            acc.assigned += Number(group.budgeted ?? 0);
            acc.activity += Number(group.activity ?? 0);
            acc.available += Number(group.available ?? 0);
            return acc;
        },
        { assigned: 0, activity: 0, available: 0 }
    );
});

const availableClass = computed(() =>
    totals.value.available < 0 ? "text-error font-semibold" : "text-body"
);
</script>

<template>
    <!-- mb-6 gives the footer breathing room from the page bottom /
         mobile bottom nav; without it the total kissed the viewport
         edge which read as unfinished. -->
    <footer
        class="px-4 py-3 mb-6 border-t border-base bg-base-lvl-2 rounded-b-md flex items-center justify-between text-sm font-semibold text-body"
    >
        <div class="flex items-center gap-2">
            <span class="text-body-1 uppercase text-xs tracking-wide">{{ $t('Total budget') }}</span>
        </div>
        <div class="flex items-center flex-nowrap">
            <div class="w-36 text-right tabular-nums" :title="$t('Assigned')">
                <MoneyPresenter :value="totals.assigned" />
            </div>
            <div
                v-if="!isMobile"
                class="w-44 h-full flex items-center justify-end tabular-nums text-body-1"
                :title="$t('Spent')"
            >
                <MoneyPresenter :value="totals.activity" />
            </div>
            <div
                class="w-28 text-right tabular-nums"
                :class="availableClass"
                :title="$t('Available')"
            >
                <MoneyPresenter :value="totals.available" />
            </div>
            <!-- Empty w-8 to align with the action column above. -->
            <div class="w-8" aria-hidden="true"></div>
        </div>
    </footer>
</template>
