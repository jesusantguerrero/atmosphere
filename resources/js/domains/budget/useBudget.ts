
import { cloneDeep } from "lodash";
import { computed, watch, reactive, toRefs, Ref } from "vue";
import { getCategoriesTotals, getGroupTotals } from './index';
import { ICategory } from "../transactions/models";

export const BudgetState = reactive({
    data: [],
    categories: [],
    visibleCategories: [],
    // Filters
    filters: {
        overspent: false,
        funded: false,
        underFunded: false,
    },
    filterGroups: {
        overSpent: [],
        overAssigned: [],
        funded: [],
        underFunded: [],
    },
    visibleFilters: {
        overspent: computed(() => BudgetState.filters.overspent || BudgetState.filterGroups.overSpent.length > 0)
    },
    // Budget selection
    selectedBudgetIds: {
        id: null,
        groupId: null
    },

    selectedBudget: computed(() => {
        return BudgetState.selectedBudgetIds.id
        ? BudgetState.categories.find((cat: ICategory) => cat.id == BudgetState.selectedBudgetIds.id)
        : null;
    }),
    // Balance
    inflow: computed(() => {
        return BudgetState.data.find((category: ICategory) => category.name == 'Inflow')
    }),
    outflow: computed(() => {
        return BudgetState.data?.filter((category: ICategory) => category.name != 'Inflow')
    }),
    budgetTotals: computed(() => {
        return getGroupTotals(BudgetState.outflow)
    }),

    available: computed(() => {
        return BudgetState.budgetTotals.budgetAvailable
    }),
    readyToAssign: computed(() => {
        const budgetTotals = BudgetState.budgetTotals;
        const category = BudgetState.inflow?.subCategories[0] ?? {}
        const creditCardFunded = parseFloat(budgetTotals?.fundedSpendingPreviousMonth ?? 0)
        const availableForFunding = parseFloat(category.activity ?? 0);
        const fundedSpending = parseFloat(category?.funded_spending ?? 0);
        // const assigned = parseFloat(category.budgeted ?? 0);
        const assigned = parseFloat(budgetTotals.budgeted ?? 0);
        const leftOver = parseFloat(category.left_from_last_month ?? 0)
        const movedFromLastMonth = parseFloat(category.moved_from_last_month ?? 0)
        const balance = (parseFloat(category.activity ?? 0) + parseFloat(category.left_from_last_month ?? 0) - assigned)

       return {
            availableForFunding,
            movedFromLastMonth,
            leftOver,
            assigned,
            creditCardFunded,
            fundedSpending,
            inflow: BudgetState.inflow?.activity,
            toAssign: category,
            ...budgetTotals,
            balance,
        }
    }),
});

const getBudget = (budgetRawData: any) => {
    const filters = {
        overSpent: [],
        overAssigned: [],
        funded: [],
        underFunded: []
    }
    let categories = [];
    let budgetData = cloneDeep(budgetRawData)

    budgetData = budgetData?.map(item => {
        const totals = getCategoriesTotals(item.subCategories, {
            onOverspent(category) {
                filters.overSpent.push(category)
                category.hasOverspent = true;
            },
            onFunded(category) {
                filters.funded.push(category)
                category.hasFunded = true;
            },
            onUnderFunded(category) {
                filters.underFunded.push(category)
                category.hasUnderFunded = true;
            },
            onOverAssigned(category) {
                filters.overAssigned.push(category)
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
        filterGroups: filters,
        categories,
        budgetData,
    }
}

const setBudgetState = ({ filterGroups, categories, budgetData}) => {
    BudgetState.data = cloneDeep(budgetData);
    if (filterGroups) {
        BudgetState.filterGroups = filterGroups;
    }
    if (categories) {
        BudgetState.categories = cloneDeep(categories);
    }
}
export const useBudget = (budgets: Ref<Record<string, any>>) => {
    if (budgets) {
        watch(() => budgets.value, (budgetServerData) => {
            setBudgetState(getBudget(budgetServerData.data));
            BudgetState.visibleCategories = getVisibleCategories(BudgetState.data);
        }, { immediate: true, deep: true })
    }

    const toggleFilter = (filterName: string) => {
        Object.entries(BudgetState.filters).forEach(([filter, value]) => {
            BudgetState.filters[filter] = filterName == filter ? !value : false ;
        })
        const visibleFilter = Object.keys(BudgetState.filters).find(name => BudgetState.filters[name])
        BudgetState.visibleCategories = getVisibleCategories(BudgetState.data, visibleFilter)
    }

    return {
        ...toRefs(BudgetState),
        budgetState: BudgetState.outflow,
        toggleFilter,
        setSelectedBudget,
    }
}

const filterConditions = {
    overspent: (category: ICategory) =>  category.hasOverspent,
    funded: (category: ICategory) =>  category.hasFunded,
    underFunded: (category: ICategory) =>  category.hasUnderFunded,
}

const evaluateFilterCondition  = (category: ICategory, filterName: string) => {
    return !filterName ? true : filterConditions[filterName](category);
}

function getVisibleCategories(budgetData: Record<string, any>, filterName?: string) {
    const data = cloneDeep(budgetData);
    const visibleGroups = data.reduce((groups, group) => {
        const groupHasFilter = group.name != 'Inflow' && evaluateFilterCondition(group, filterName)
        if (groupHasFilter) {
            group.subCategories = group.subCategories?.filter(subCategory => filterName ? evaluateFilterCondition(subCategory, filterName) : true)
            groups.push(group)
        }
        return groups
    }, [])

    return visibleGroups;
}

const setSelectedBudget = (categoryId = null, groupId = null) => {
    BudgetState.selectedBudgetIds.id = categoryId;
    BudgetState.selectedBudgetIds.groupId = groupId;
}
