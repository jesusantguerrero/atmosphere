
import { cloneDeep } from "lodash";
import { computed, watch, reactive, toRefs, Ref, ref, h } from "vue";
import { getCategoriesTotals, getGroupTotals } from './index';
import { IBudgetCategory } from "./models/budget";
import { format } from "date-fns";
import { router } from "@inertiajs/vue3";
import { ElNotification } from "element-plus";

// Surface for unexpected errors during budget mutations (network failures, 500s).
// Validation errors (422) flow through Inertia's $page.props.errors automatically.
// BudgetErrorBanner reads from both.
export const budgetMutationError = ref<string | null>(null);

const handleBudgetError = (errors: Record<string, string>) => {
    const firstError = Object.values(errors)[0];
    budgetMutationError.value = firstError ?? 'Could not save the change. Please try again.';
};

// Singleton handle so a fresh assign always replaces the previous undo toast.
// Without this a user clicking Deshacer on a stale toast could revert the wrong
// movement when two assigns land within the toast window.
let activeUndoNotification: { close: () => void } | null = null;

const showUndoToast = (movementId: number) => {
    if (activeUndoNotification) {
        activeUndoNotification.close();
        activeUndoNotification = null;
    }

    const notif = ElNotification({
        title: 'Saved',
        message: h('div', { class: 'flex items-center gap-3' }, [
            h('span', 'Asignación guardada'),
            h(
                'button',
                {
                    class: 'font-semibold text-primary hover:underline',
                    onClick: () => {
                        router.delete(`/budget-movements/${movementId}`, {
                            preserveScroll: true,
                            preserveState: true,
                            only: ['budgets'],
                        });
                        notif.close();
                    },
                },
                'Deshacer',
            ),
        ]),
        type: 'success',
        duration: 6000,
        onClose: () => {
            if (activeUndoNotification === notif) {
                activeUndoNotification = null;
            }
        },
    });

    activeUndoNotification = notif;
};

const handleAssignSuccess = (page: { props?: { flash?: { type?: string; movement_id?: number }, budgets?: any } }) => {
    const flash = page?.props?.flash;
    if (flash?.type === 'movement_created' && flash.movement_id) {
        showUndoToast(flash.movement_id);
    }
    // Force an immediate state re-sync from the fresh server props so the
    // "to assign" headline (and every budget-derived total) ticks down the
    // instant the assignment lands — the ZBB dopamine loop depends on seeing
    // the number drop live. Relying only on the deep prop-watch proved flaky
    // under Inertia partial reloads (preserveState) + heavy re-renders, leaving
    // "POR ASIGNAR" frozen until a manual refresh.
    const budgetData = page?.props?.budgets;
    if (budgetData) {
        setBudgetState(getBudget(budgetData.data));
        setVisibleCategories();
    }
};


interface IFilterGroups {
    overSpent: any[],
    overAssigned: any[],
    funded: any[],
    underFunded: any[],
}
interface IBudgetState {
    data: IBudgetCategory[];
    categories: any[];
    visibleCategories: any[],
    filters: {
        overspent: boolean,
        funded: boolean,
        underFunded: boolean,
    },
    filterGroups: IFilterGroups,
    visibleFilters: Record<string, any>;
    selectedBudgetIds: {
        id: number|null;
        groupId: number|null;
    },
    selectedBudget: any;
    inflow?: IBudgetCategory;
    outflow: IBudgetCategory[];
    budgetTotals: any;
    available: any;
    readyToAssign: any
}

export const BudgetState: IBudgetState = reactive({
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
        ? BudgetState.categories.find((cat: IBudgetCategory) => cat.id == BudgetState.selectedBudgetIds.id)
        : null;
    }),
    // Balance
    inflow: computed(() =>BudgetState.data.find((category: IBudgetCategory) => category.name == 'Inflow')),
    outflow: computed(() => {
        return BudgetState.data?.filter((category: IBudgetCategory) => category.name != 'Inflow')
    }),
    budgetTotals: computed(() => {
        return getGroupTotals(BudgetState.outflow)
    }),

    available: computed(() => {
        return BudgetState.budgetTotals.budgetAvailable
    }),
    readyToAssign: computed(() => {
        const budgetTotals = BudgetState.budgetTotals;
        const category = BudgetState.inflow?.subCategories.at(0) ?? {}
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
    const filters: IFilterGroups = {
        overSpent: [],
        overAssigned: [],
        funded: [],
        underFunded: []
    }
    let categories: IBudgetCategory[] = [];
    let budgetData = cloneDeep(budgetRawData)

    budgetData = budgetData?.map((item: IBudgetCategory) => {
        const totals = getCategoriesTotals(item.subCategories, {
            onOverspent(category: IBudgetCategory) {
                filters.overSpent.push(category)
                category.hasOverspent = true;
            },
            onFunded(category: IBudgetCategory) {
                filters.funded.push(category)
                category.hasFunded = true;
            },
            onUnderFunded(category: IBudgetCategory) {
                filters.underFunded.push(category)
                category.hasUnderFunded = true;
            },
            onOverAssigned(category: IBudgetCategory) {
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

const setBudgetState = ({ filterGroups, categories, budgetData }:{
    filterGroups: IFilterGroups,
    categories: IBudgetCategory[],
    budgetData: any
    }
) => {
    BudgetState.data = cloneDeep(budgetData);
    if (filterGroups) {
        BudgetState.filterGroups = filterGroups;
    }
    if (categories) {
        BudgetState.categories = cloneDeep(categories);
    }
}

enum FilterNames {
    Overspent = "overspent",
    Funded = "funded",
    Underfunded = "underFunded"
}

const setVisibleCategories = () => {
    // @ts-ignore
    const visibleFilter = Object.keys(BudgetState.filters).find((name: string) => BudgetState.filters[name]) as FilterNames
    BudgetState.visibleCategories = getVisibleCategories(BudgetState.data, visibleFilter)
}

interface AssignBudgetProps {
    category: IBudgetCategory,
    categoryGroup: IBudgetCategory,
    month: string,
    budgeted: number
}

interface BudgetMovementProps {
    category?: IBudgetCategory,
    sourceCategoryId: number,
    destinationCategoryId: number,
    amount: number,
    date: string;
}


export const useBudget = (budgets: Ref<Record<string, any>>) => {
    if (budgets) {
        watch(() => budgets.value, (budgetServerData) => {
            setBudgetState(getBudget(budgetServerData.data));
            setVisibleCategories()
        }, { immediate: true, deep: true })
    }

    const setBudgetFilter = (filterName: string) => {
        Object.entries(BudgetState.filters).forEach(([filter, value]) => {
            // @ts-ignore:: its ok not to be ok
            BudgetState.filters[filter] = filterName == filter ? !value : false ;
        })
        setVisibleCategories();
    }

    const assignBudget = (assign: AssignBudgetProps) => {
        budgetMutationError.value = null;
        router.post(
            `/budgets/${assign.category.id}/months/${assign.month}`,
            {
                id: assign.category.id,
                budgeted: assign.budgeted,
                date: format(new Date(), 'yyyy-MM-dd'),
            },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['budgets', 'flash'],
                onSuccess: handleAssignSuccess,
                onError: handleBudgetError,
            }
        );
    }

    const moveBudget = (movementData: BudgetMovementProps) => {
        budgetMutationError.value = null;
        router.post(
            `/budgets/${movementData.category?.id}/months/${movementData.date}`,
            {
                amount: movementData.amount,
                source_category_id: movementData.sourceCategoryId,
                destination_category_id: movementData.destinationCategoryId,
                type: 'movement',
                date: movementData.date,
            },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['budgets', 'flash'],
                onSuccess: handleAssignSuccess,
                onError: handleBudgetError,
            }
        );
    }


    return {
        ...toRefs(BudgetState),
        budgetState: BudgetState.outflow,
        setBudgetFilter,
        setSelectedBudget,
        assignBudget,
        moveBudget
    }
}



const filterConditions = {
    overspent: (category: IBudgetCategory) =>  category.hasOverspent,
    funded: (category: IBudgetCategory) =>  category.hasFunded,
    underFunded: (category: IBudgetCategory) =>  category.hasUnderFunded,
}

const evaluateFilterCondition  = (category: IBudgetCategory, filterName?: FilterNames) => {
    return filterName
    ? filterConditions[filterName](category)
    : true ;
}

function getVisibleCategories(budgetData: Record<string, any>, filterName?: FilterNames) {
    const data = cloneDeep(budgetData);
    const visibleGroups = data.reduce((groups: any, group: IBudgetCategory) => {
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
