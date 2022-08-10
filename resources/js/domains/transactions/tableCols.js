import formatMoney from "@/utils/formatMoney";
import { format, parseISO } from "date-fns"
import { h } from "vue"
import IconTransfer from "@/Components/icons/IconTransfer.vue";

export const tableCols = [
    {
        label: "Date",
        name: "date",
        width: 200,
        render(row) {
            return format(parseISO(row.date), "dd MMM, yyyy")
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
        label: "Amount",
        name: "total",
        render(row) {
            console.log(row)
            return formatMoney(row.total, row.currency_code)
        }
    },
    {
        label: "",
        name: "actions",
        width: 300,
        type: "custom",
    },
];
