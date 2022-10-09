import ExactMath from "exact-math";
import { cloneDeep } from "lodash";
import { computed, ref, watch } from "vue";
import { getCategoriesTotals, getGroupTotals } from './index';


export const useBudget = (budgets) => {
    const overspentCategories = ref([])
    const overAssignedCategories = ref([])
    const budget = computed(() => {
        overspentCategories.value = [];
        overAssignedCategories.value = [];
        const cloned = cloneDeep(budgets.value)

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
        console.log(inflow.value?.activity | 0, budgetTotals.budgeted || 0 , "Balance")
        const balance = ExactMath.sub(inflow.value?.activity | 0, budgetTotals.budgeted || 0)
        const category = inflow.value?.subCategories[0] ?? {}

        return {
            balance,
            ...category,
            ...budgetTotals,
        }
    })

    return {
        readyToAssign,
        budgetState: outflow,
        visibleCategories,
        overspentCategories,
        overspentFilter,
        toggleOverspent
    }
}
