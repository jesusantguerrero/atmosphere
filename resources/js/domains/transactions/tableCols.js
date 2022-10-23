import formatMoney from "@/utils/formatMoney";
import { format, isAfter, parseISO, startOfDay } from "date-fns"
import { h } from "vue"
import IconTransfer from "@/Components/icons/IconTransfer.vue";
import { AtBadge } from "atmosphere-ui"
import { messageDark } from "naive-ui";

const TRANSACTION_TYPES = {
    WITHDRAW: 'outflow',
    DEPOSIT: 'inflow'
}

export const tableCols = [
    {
        label: "Date",
        name: "date",
        width: 200,
        class: 'text-center',
        headerClass: 'text-center',
        render(row) {
            const date = parseISO(row.date)
            const hasPassed = isAfter(startOfDay(date), startOfDay(new Date()))
            return h('div', {class: hasPassed ? 'text-danger' : 'text-info cursor-pointer'} ,format(date, "dd MMM, yyyy"))
        }
    },
    {
        label: "Payee",
        name: "payee",
        width: 400,
        class: 'w-full',
        render(row) {
            try {
                const children = () => [
                    h('div', `Transfer: ${row.counterAccount?.name}`),
                    h(IconTransfer, { class: 'fa fa-right-left'})
                ];
                return row.payee?.name ?? h('div', { class: "flex justify-between items-center text-body-1 h-4"}, children )
            } catch(e) {
                return ''
            }
        }
    },
    {
        label: "Category",
        name: "category",
        render(row) {
            return row.category?.name
        }
    },
    {
        label: "Description",
        name: "description",
        width: 300,
    },
    {
        label: "Type",
        name: "direction",
        width: 300,
        class: 'text-center',
        headerClass: 'text-center',
        render(row) {
            return TRANSACTION_TYPES[row.direction];
        },
    },
    {
        label: "Amount",
        name: "total",
        type: "custom",
        class: 'text-right',
        headerClass: 'text-right',
    },
    {
        label: "Status",
        name: "status",
        width: 200,
        class: 'py-1 flex items-center justify-center',
        headerClass: 'text-center',
        render(row) {
            const statusColors = {
                draft: 'secondary',
                planned: 'warning',
                verified: 'success',
            }
            return () => h(AtBadge, { type: statusColors[row.status]}, () => row.status)
        }
    },
    {
        label: "",
        name: "actions",
        width: 300,
        type: "custom",
        class: 'text-right'
    },
];
