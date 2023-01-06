import { cloneDeep, flatten } from "lodash";
import { computed, ref, watch, reactive, toRefs } from "vue";
import { getCategoriesTotals, getGroupTotals } from './index';

export const BudgetState = reactive({
    data: [],
    categories: [],
    visibleCategories: [],
    overSpentCategories: [],
    overAssignedCategories: [],
    // Filters
    filters: {
        overspent: false,
    },
    visibleFilters: {
        overspent: computed(() => BudgetState.filters.overspent || BudgetState.overSpentCategories.length > 0)
    },
    // Budget selection
    selectedBudgetIds: {
        id: null,
        groupId: null
    },

    selectedBudget: computed(() => {
        return BudgetState.selectedBudgetIds.id
        ? BudgetState.categories.find(cat => cat.id == BudgetState.selectedBudgetIds.id)
        : null;
    }),
    // Balance
    inflow: computed(() => {
        return BudgetState.data.find((category) => category.name == 'Inflow')
    }),
    outflow: computed(() => {
        return BudgetState.data.filter((category) => category.name != 'Inflow')
    }),
    readyToAssign: computed(() => {
        const budgetTotals = getGroupTotals(BudgetState.outflow)
        const category = BudgetState.inflow?.subCategories[0] ?? {}
        const balance = category?.activity - budgetTotals.budgeted

        return {
            balance,
            inflow: BudgetState.inflow?.activity,
            toAssign: category,
            ...budgetTotals,
        }
    }),
});

const getBudget = (budgetRawData) => {
    const overSpentCategories = []
    const overAssignedCategories = []
    let categories = [];
    let budgetData = cloneDeep(budgetRawData)

    budgetData = budgetData.map(item => {
        const totals = getCategoriesTotals(item.subCategories, {
            onOverspent(category) {
                overSpentCategories.push(category)
                category.hasOverspent = true;
            },
            onOverAssigned(category) {
                overAssignedCategories.push(category)
                category.overAssigned = true;
            }
        });

        categories = [...categories, ...item.subCategories]

        return {
            ...item,
            ...totals
        }
    });

    return {
        overSpentCategories,
        overAssignedCategories,
        categories,
        budgetData,
    }
}

const setBudgetState = ({  overSpentCategories, overAssignedCategories, categories, budgetData}) => {
    BudgetState.data = budgetData;
    BudgetState.overSpentCategories = overSpentCategories;
    BudgetState.overAssignedCategories = overAssignedCategories;
    BudgetState.categories = categories;
}
export const useBudget = (budgets) => {
    if (budgets) {
        watch(() => budgets.value, (budgetServerData) => {
            setBudgetState(getBudget(budgetServerData.data));
            BudgetState.visibleCategories = getVisibleCategories(BudgetState.data, BudgetState.filters.overspent);
        }, { immediate: true, deep: true })
    }

    const toggleOverspent = () => {
        BudgetState.filters.overspent = !BudgetState.filters.overspent
        BudgetState.visibleCategories = getVisibleCategories(BudgetState.data, BudgetState.filters.overspent)
    }

    return {
        ...toRefs(BudgetState),
        budgetState: BudgetState.outflow,
        toggleOverspent,
        setSelectedBudget,
    }
}

function getVisibleCategories(budgetData, isOverSpentFilterActive) {
    return budgetData.reduce((groups, categoryGroup) => {
        let conditions = categoryGroup.name != 'Inflow'
        if (isOverSpentFilterActive) {
            conditions = conditions && categoryGroup.hasOverspent
        }
        if (conditions) {
            categoryGroup.subCategories = categoryGroup.subCategories?.filter(
                subCategory => isOverSpentFilterActive ? subCategory.hasOverspent : true
            )
            groups.push(categoryGroup)
        }

        return groups
    }, [])
}

const setSelectedBudget = (categoryId = null, groupId = null) => {
    BudgetState.selectedBudgetIds.id = categoryId;
    BudgetState.selectedBudgetIds.groupId = groupId;
}
