import LogerInput from "../../Components/atoms/LogerInput.vue";
import formatMoney from "../../utils/formatMoney";
import { AtButton } from 'atmosphere-ui';
import { h } from "vue"
import { format, startOfMonth } from "date-fns";
import { Inertia } from "@inertiajs/inertia";
import BalanceInput from "@/Components/atoms/BalanceInput.vue";
import { createBudgetCategory } from "./createBudgetCategory";

export const budgetCols = (state) =>  {
    const isExpandedCategory = (category) => {
        return state.expandedCategory && state.expandedCategory.id == category.id
    };


    return [
    {
        type: 'selection',
        fixed: 'left'
    },
    {
        title: 'Category',
        key: 'name',
        className: 'flex items-center',
        render (row, index) {
            const tag = !row.parent_id ? 'div' : 'div';
            return h(tag, { class: 'flex items-center' },
                [
                    row.name,
                    h(AtButton, {
                        onclick: () => {
                            state.selectedBudget = row;
                        }},
                    'Edit'),
                    !row.parent_id && (!isExpandedCategory(row) ? h(AtButton, {
                        onclick: () => {
                            state.expandedCategory = row;
                        }},
                        'Add subcategory'
                    ) : h(LogerInput, {
                        modelValue: state.categoryForm.name,
                        withSave: true,
                        class: 'w-40',
                        placeholder: 'Add subcategory',
                        onInput: (e) => {
                            state.categoryForm.name = e.target.value;
                        },
                        onKeypress: (e) => {
                            if (e.ctrlKey && e.which == 10) {
                                createBudgetCategory(state.categoryForm, row.id)
                                state.expandedCategory = null
                            }
                        }
                }))])
        }
    },
    {
        title: 'Assigned',
        key: 'amount',
        render(row) {
            return !row.parent_id ? formatMoney(row.budgeted) : h(LogerInput, {
                modelValue: row.budgeted || 0,
                onInput: (e) => {
                    row.budgeted = e.target.value;
                },
                onBlur: (e) => {
                    const month = format(startOfMonth(new Date()), 'yyyy-MM-dd');
                    Inertia.post(`/budgets/${row.id}/months/${month}`, {
                        id: row.id,
                        budgeted: row.budgeted
                    }, {
                        preserveState: true,
                        preserveScroll: true
                    });
                }
            })
        }
    },
    {
        title: 'Activity',
        key: 'activity',
        render (row, index) {
            return h('span', formatMoney(row.activity))
        }
    },
    {
        title: 'Available',
        key: 'available',
        render (row, index) {
            return row.parent_id ? h(BalanceInput, { value: row.available, formatter: formatMoney }) : formatMoney(row.available)
        },
        class: 'text-right'
    }
]};
