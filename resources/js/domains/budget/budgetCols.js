import LogerInput from "../../Components/atoms/LogerInput.vue";
import formatMoney from "../../utils/formatMoney";
import { AtButton } from 'atmosphere-ui';
import { h } from "vue"

export const budgetCols = (state) =>  [
    {
        type: 'selection',
        fixed: 'left'
    },
    {
        title: 'Category',
        key: 'name',
        render (row, index) {
            const tag = !row.parent_id ? 'strong' : 'span';
            return h(tag, [
                    row.name,
                    h(AtButton, {
                        onclick: () => {
                            state.selectedBudget = row;
                        }},
                    'Edit'),
                    !row.parent_id && h(AtButton, {
                        onclick: () => {
                            state.expandedCategory = row;
                        }},
                        'Add subcategory'
                    )])
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
                    });
                   console.log("we are here")
                }
            })
        }
    },
    {
        title: 'Activity',
        key: 'activity',
        render (row, index) {
            return h('span', formatMoney(row.spent))
        }
    },
    {
        title: 'Available',
        key: 'available',
        render (row, index) {
            return h('span', formatMoney(row.available))
        }
    }
];