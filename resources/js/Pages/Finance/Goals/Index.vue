<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

import AppLayout from '@/Components/templates/AppLayout.vue';
import FinanceSectionNav from '@/Pages/Finance/Partials/FinanceSectionNav.vue';
import FinanceTemplate from '@/Pages/Finance/Partials/FinanceTemplate.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import BudgetProgress from '@/domains/budget/components/BudgetProgress.vue';
import MoneyPresenter from '@/Components/molecules/MoneyPresenter.vue';
import GoalFormModal from './Partials/GoalFormModal.vue';

import { formatDate } from '@/utils';

interface Goal {
    id: number;
    name: string | null;
    category_id: number;
    category_name: string | null;
    group_name: string | null;
    target_type: 'saving_balance' | 'savings_monthly' | string;
    frequency: 'MONTHLY' | 'WEEKLY' | 'DATE' | null;
    frequency_date: string | null;
    frequency_month_date: number | null;
    target_amount: number;
    current_amount: number;
    remaining_amount: number;
    progress: number;
    monthly_needed: number | null;
    months_remaining: number | null;
    days_remaining: number | null;
    is_completed: boolean;
    is_overdue: boolean;
    completed_at: string | null;
    created_at: string | null;
    color: string | null;
}

interface Counts {
    all: number;
    monthly: number;
    dated: number;
    overdue: number;
    completed: number;
}

interface CategoryOption {
    id: number;
    name: string;
    group_name: string | null;
}

const props = defineProps<{
    goals: Goal[];
    counts: Counts;
    filter: string;
    availableCategories: CategoryOption[];
}>();

const showCreateModal = ref(false);

const refreshGoals = (): void => {
    router.reload({ only: ['goals', 'counts'], preserveScroll: true });
};

const { t } = useI18n();

const tabs = computed(() => [
    { value: 'all', label: t('All'), count: props.counts.all },
    { value: 'monthly', label: t('Monthly'), count: props.counts.monthly },
    { value: 'dated', label: t('By date'), count: props.counts.dated },
    { value: 'overdue', label: t('Overdue'), count: props.counts.overdue },
    { value: 'completed', label: t('Completed'), count: props.counts.completed },
]);

const setFilter = (value: string): void => {
    router.get(route('finance.goals.index'), { filter: value }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const progressClass = (goal: Goal): string[] => {
    if (goal.is_completed) {
        return ['bg-success', 'bg-success/10'];
    }
    if (goal.is_overdue) {
        return ['bg-error', 'bg-error/10'];
    }
    return ['bg-primary', 'bg-primary/10'];
};

const frequencyLabel = (goal: Goal): string => {
    if (goal.frequency === 'DATE' && goal.frequency_date) {
        return t('By {date}', { date: formatDate(goal.frequency_date) });
    }
    if (goal.frequency === 'MONTHLY') {
        return t('Monthly');
    }
    return t('No date');
};

const dueLabel = (goal: Goal): string | null => {
    if (goal.is_completed || goal.frequency !== 'DATE' || goal.days_remaining === null) {
        return null;
    }
    if (goal.days_remaining < 0) {
        return t('{days} days overdue', { days: Math.abs(goal.days_remaining) });
    }
    if (goal.days_remaining === 0) {
        return t('Due today');
    }
    return t('{days} days left', { days: goal.days_remaining });
};
</script>

<template>
    <AppLayout :title="$t('Savings goals')">
        <template #header>
            <FinanceSectionNav />
        </template>

        <FinanceTemplate :title="$t('Goals')" hide-panel>
            <header class="flex flex-col items-start justify-between gap-4 mb-6 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-body">{{ $t('Savings goals') }}</h1>
                    <p class="text-body-1">{{ $t('Track your monthly and target-date goals') }}</p>
                </div>
                <LogerButton variant="inverse" @click="showCreateModal = true">
                    {{ $t('New goal') }}
                </LogerButton>
            </header>

            <nav class="flex flex-wrap gap-2 pb-4 border-b border-base-lvl-3">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    class="px-3 py-1.5 text-sm rounded-full transition-colors"
                    :class="filter === tab.value ? 'bg-primary text-white' : 'bg-base-lvl-3 text-body-1 hover:bg-base-lvl-2'"
                    @click="setFilter(tab.value)"
                >
                    {{ tab.label }}
                    <span class="ml-1 text-xs opacity-80">{{ tab.count }}</span>
                </button>
            </nav>

            <section v-if="goals.length" class="grid gap-4 mt-6 lg:grid-cols-2">
                <article
                    v-for="goal in goals"
                    :key="goal.id"
                    class="relative p-5 rounded-md cursor-pointer bg-base-lvl-3 border border-transparent hover:border-primary/40"
                    :class="{ 'border-success/30': goal.is_completed, 'border-error/30': goal.is_overdue && !goal.is_completed }"
                    @click="router.visit(route('budgets.show', goal.category_id))"
                >
                    <header class="flex items-start justify-between gap-2 mb-3">
                        <div>
                            <h3 class="text-lg font-bold text-body">{{ goal.name || goal.category_name }}</h3>
                            <p class="text-xs text-body-1">
                                <span v-if="goal.group_name">{{ goal.group_name }} / </span>{{ goal.category_name }}
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1 text-xs">
                            <span
                                class="px-2 py-0.5 rounded-full"
                                :class="goal.frequency === 'MONTHLY' ? 'bg-primary/10 text-primary' : 'bg-base-lvl-2 text-body-1'"
                            >
                                {{ frequencyLabel(goal) }}
                            </span>
                            <span v-if="goal.is_completed" class="px-2 py-0.5 rounded-full bg-success/10 text-success">
                                {{ $t('Completed') }}
                            </span>
                            <span v-else-if="goal.is_overdue" class="px-2 py-0.5 rounded-full bg-error/10 text-error">
                                {{ $t('Overdue') }}
                            </span>
                        </div>
                    </header>

                    <BudgetProgress
                        class="h-2 rounded-sm"
                        :goal="goal.target_amount"
                        :current="goal.current_amount"
                        :progress-class="progressClass(goal)"
                        :show-labels="false"
                    >
                        <template #after="{ progress }">
                            <div class="flex justify-between mt-1 text-xs text-body-1">
                                <span>{{ progress }}%</span>
                                <span>
                                    <MoneyPresenter :value="goal.current_amount" /> / <MoneyPresenter :value="goal.target_amount" />
                                </span>
                            </div>
                        </template>
                    </BudgetProgress>

                    <footer class="flex items-center justify-between gap-2 pt-3 mt-3 text-sm border-t border-base-lvl-2">
                        <div class="text-body-1">
                            <span v-if="goal.monthly_needed !== null && !goal.is_completed">
                                {{ $t('Need') }}
                                <MoneyPresenter :value="goal.monthly_needed" class="font-bold text-body" />
                                {{ $t('/ month') }}
                            </span>
                            <span v-else-if="goal.is_completed">
                                {{ $t('Goal reached') }}
                            </span>
                        </div>
                        <span v-if="dueLabel(goal)" class="text-xs" :class="goal.is_overdue ? 'text-error' : 'text-body-1'">
                            {{ dueLabel(goal) }}
                        </span>
                    </footer>

                </article>
            </section>

            <section v-else class="py-16 text-center text-body-1">
                <p class="text-lg">{{ $t('No goals yet') }}</p>
                <p class="mt-2 text-sm">
                    {{ $t('Pick a category and a target amount to start tracking.') }}
                </p>
                <LogerButton variant="primary" class="mt-4" @click="showCreateModal = true">
                    {{ $t('Create your first goal') }}
                </LogerButton>
            </section>

            <GoalFormModal
                v-model:show="showCreateModal"
                :categories="availableCategories"
                @created="refreshGoals"
            />
        </FinanceTemplate>
    </AppLayout>
</template>
