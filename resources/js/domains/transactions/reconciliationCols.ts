import { format, isAfter, parseISO, startOfDay } from "date-fns"
import { h } from "vue"

export const reconciliationCols = () => [
    {
        label: "Date",
        name: "date",
        width: 200,
        class: 'text-center',
        headerClass: 'text-center',
        render(row: any) {
            try {
                const date = parseISO(row.date)
                const hasPassed = isAfter(startOfDay(date), startOfDay(new Date()))
                return h('div', {class: hasPassed ? 'text-danger' : 'text-info cursor-pointer'} ,format(date, "dd MMM, yyyy"))
            } catch (e) {
                return h('div', {class:'text-info cursor-pointer'} , '--')
            }
        }
    },
    {
        label: "Diff",
        name: "difference",
        width: 300,
        type: "money"
    },
    {
        label: "Amount",
        name: "amount",
        type: "money",
        class: 'text-right',
        headerClass: 'text-right',
    },
    {
        label: "Status",
        name: "status",
        width: 300,
    },
    {
        label: "",
        name: "actions",
        width: 300,
        type: "custom",
        class: 'text-right'
    },
];

