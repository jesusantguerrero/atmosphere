import ExactMath from "exact-math";
import { computed, ref, watch } from "vue";

export const useBudget = (budgets) => {

    const budget = computed(() => {
        return budgets.value.data.map(item => {
            const totals = Object.values(item.subCategories).reduce((acc, cur) => {
                acc.budgeted = ExactMath.add(acc.budgeted, cur.budgeted || 0)
                acc.spent =  ExactMath.add(acc.spent, cur.spent || 0)
                acc.available = ExactMath.add(acc.available, cur.available || 0)
                return acc;
            }, {
                budgeted: 0,
                spent: 0,
                available: 0
            });
            item.budgeted = totals.budgeted;
            item.spent = totals.spent;
            item.available = totals.available;
            return item
        });

    });

    const budgetState = ref([]);

    watch(budgets, () => {
        budgetState.value = getBudgetState();
    }, { immediate: true })

    function getBudgetState() {
        return budget.value.filter((category) => category.name != 'Inflow')
    }

    const inflow = computed(() => {
        return budget.value.find((category) => category.name == 'Inflow')
    })

    const readyToAssign = computed(() => {
        const budgeted = budgetState.value.reduce((acc, category) => {
            return ExactMath.add(category.budgeted, acc || 0)
        }, 0)
        return ExactMath.sub(inflow.value.spent, budgeted)
    })

    return {
        readyToAssign,
        budgetState
    }
}
