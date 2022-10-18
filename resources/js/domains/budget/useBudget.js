import ExactMath from "exact-math";
import { cloneDeep, flatten } from "lodash";
import { computed, ref, watch, reactive } from "vue";
import { getCategoriesTotals, getGroupTotals } from './index';


export const useBudget = (budgets) => {
    const overspentCategories = ref([])
    const overAssignedCategories = ref([])
    const categories = ref([]);
    const budget = computed(() => {
        const cloned = cloneDeep(budgets.value)
        overspentCategories.value = [];
        overAssignedCategories.value = [];
        categories.value = [];

        return cloned.data.map(item => {
            const totals = getCategoriesTotals(item.subCategories, {
                onOverspent(category) {
                    overspentCategories.value.push(category)
                    category.hasOverspent = true;
                },
                onOverAssigned(category) {
                    overAssignedCategories.value.push(category)
                    category.overAssigned = true;
                }
            });

            categories.value = [...categories.value, ...item.subCategories]

            return {
                ...item,
                ...totals
            }
        });
    });

    const overspentFilter = ref(false)
    const visibleCategories = ref([]);

    watch(() => budgets.value, () => {
        visibleCategories.value = getVisibleCategories();
    }, { immediate: true, deep: true })

    const toggleOverspent = () => {
        overspentFilter.value = !overspentFilter.value
        visibleCategories.value = getVisibleCategories()
    }

    function getVisibleCategories() {
        return budget.value.reduce((groups, categoryGroup) => {
            let conditions = categoryGroup.name != 'Inflow'
            if (overspentFilter.value) {
                conditions = conditions && categoryGroup.hasOverspent
            }
            if (conditions) {
                categoryGroup.subCategories = categoryGroup.subCategories?.filter(
                    subCategory => overspentFilter.value ? subCategory.hasOverspent : true
                )
                groups.push(categoryGroup)
            }

            return groups
        }, [])
    }

    const inflow = computed(() => {
        return budget.value.find((category) => category.name == 'Inflow')
    })

    const outflow = computed(() => {
        return budget.value.filter((category) => category.name != 'Inflow')
    })

    const readyToAssign = computed(() => {
        const budgetTotals = getGroupTotals(outflow.value)
        const category = inflow.value?.subCategories[0] ?? {}
        const balance = category?.activity - budgetTotals.budgeted

        return {
            balance,
            inflow: inflow.value.activity,
            toAssign: category,
            ...budgetTotals,
        }
    })

    // Budget selection
    const selectedBudgetIds = reactive({
        id: null,
        groupId: null
    })

    const setSelectedBudget = (categoryId = null, groupId = null) => {
        selectedBudgetIds.id = categoryId;
        selectedBudgetIds.groupId = groupId;
    }

    const selectedBudget = computed(() => {
        return selectedBudgetIds.id
        ? categories.value.find(cat => cat.id == selectedBudgetIds.id)
        : null;
    })

    return {
        readyToAssign,
        budgetState: outflow,
        visibleCategories,
        overspentCategories,
        overspentFilter,
        toggleOverspent,
        setSelectedBudget,
        selectedBudget
    }
}
