import ExactMath from "exact-math";
import { computed, ref, watch } from "vue";

export const useBudget = (budgets) => {
    const overspentCategories = ref([])
    const budget = computed(() => {
        overspentCategories.value = [];
        return budgets.value.data.map(item => {
            const totals = Object.values(item.subCategories).reduce((acc, category) => {
                acc.budgeted = ExactMath.add(acc.budgeted, category.budgeted || 0)
                acc.spent =  ExactMath.add(acc.spent, category.spent || 0)
                acc.available = ExactMath.add(acc.available, category.available || 0)
                if (Number(category.available) < 0) {
                    overspentCategories.value.push(category);
                    category.hasOverspent = true;
                    acc.hasOverspent = true;
                }
                return acc;
            }, {
                budgeted: 0,
                spent: 0,
                available: 0
            });
            item.budgeted = totals.budgeted;
            item.spent = totals.spent;
            item.available = totals.available;
            item.hasOverspent = totals.hasOverspent;
            return item
        });

    });

    const overspentFilter = ref(false)
    const visibleCategories = ref([]);

    watch(budgets, () => {
        visibleCategories.value = getVisibleCategories();
    }, { immediate: true })

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
                categoryGroup.subCategories = categoryGroup.subCategories.filter(subCategory => overspentFilter.value ? subCategory.hasOverspent : true)
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
        const budgeted = outflow.value.reduce((acc, category) => {
            return ExactMath.add(category.budgeted, acc || 0)
        }, 0)
        return ExactMath.sub(inflow.value?.spent | 0, budgeted || 0)
    })

    return {
        readyToAssign,
        budgetState: outflow,
        visibleCategories,
        overspentCategories,
        toggleOverspent,
        overspentFilter
    }
}
