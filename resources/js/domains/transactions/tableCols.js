import formatMoney from "@/utils/formatMoney";
import { format, isAfter, parseISO, startOfDay } from "date-fns"
import { h } from "vue"
import IconTransfer from "@/Components/icons/IconTransfer.vue";

const TRANSACTION_TYPES = {
    WITHDRAW: 'outflow',
    DEPOSIT: 'inflow'
}

export const tableCols = [
    {
        label: "Date",
        name: "date",
        width: 200,
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
            return row.payee?.name || h('div', { class: "flex justify-between items-center text-body-1"} , [h('div', `Transfer: ${row.category.name}`), h(IconTransfer, { class: 'fa fa-right-left'})])
        }
    },
    {
        label: "Category",
        name: "transactionCategory",
        render(row) {
            return row.transactionCategory?.name
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
        render(row) {
            return TRANSACTION_TYPES[row.direction];
        },
    },
    {
        label: "Amount",
        name: "total",
        type: "custom"
    },
    {
        label: "Status",
        name: "status",
        width: 200,
    },
    {
        label: "",
        name: "actions",
        width: 300,
        type: "custom",
    },
];
