import { format, isAfter, parseISO, startOfDay } from "date-fns"
import { h } from "vue"
import IconTransfer from "@/Components/icons/IconTransfer.vue";
import { Link, router } from "@inertiajs/vue3";

const TRANSACTION_TYPES = {
    WITHDRAW: 'outflow',
    DEPOSIT: 'inflow'
}

export const tableAccountCols = (accountId) => [
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
                const account = row.account_id === accountId ? row.counter_account : row.account
                const children = () => [
                    h(Link, { class: 'font-bold underline text-secondary', href: `/finance/accounts/${account.id}`}, `${account?.name}`),
                    h(IconTransfer, { class: 'fa fa-right-left'})
                ];
                return row.payee?.name ?? h('div', { class: "flex justify-between items-center text-body-1 h-4"}, children() )
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
        label: "",
        name: "actions",
        width: 300,
        type: "custom",
        class: 'text-right'
    },
];

export const removeTransaction = (transaction) => {
    if (confirm("Are you sure you want to remove this transaction?")) {
        router.delete(`/transactions/${transaction.id}`, {
            onSuccess() {
                router.reload();
            }
        })
    }
}