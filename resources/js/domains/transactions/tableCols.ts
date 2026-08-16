import { isAfter, parseISO, startOfDay } from "date-fns"
import { formatDate } from "@/utils"
import { h } from "vue"
import IconTransfer from "@/Components/icons/IconTransfer.vue";
import { Link } from "@inertiajs/vue3";

export const tableCols = [
    {
        label: "Date",
        name: "date",
        width: 150,
        class: 'text-center',
        headerClass: 'text-center',
        render(row: any) {
            let date = new Date()
            try {
                date = parseISO(row.date)
            } catch {
                // fallback to current date
            }
            const hasPassed = isAfter(startOfDay(date), startOfDay(new Date()))
            return h('div', {class: hasPassed ? 'text-danger' : 'text-info cursor-pointer'} ,formatDate(date, undefined, "dd MMM, yyyy"))
        }
    },
    {
        label: "Account",
        name: "account",
        minWidth: 140,
        render(row: Record<string, any>) {
            return h('div', {}, [
                h(Link, { class: 'font-medium text-body-1/80 hover:text-primary hover:underline transition-colors', href: `/finance/accounts/${row.account_id}`}, row.account?.name ?? row.account_name),
            ])
        }
    },
    {
        // Fixed widths (700 here + 200/300/300 on siblings) overflowed the
        // container and ElTable's table-fixed layout painted the Account and
        // Payee headers on top of each other. min-widths flex instead.
        label: "Payee",
        name: "payee",
        minWidth: 220,
        class: 'w-full',
        render(row: Record<string, any>) {
            const payeeName = row.payee?.name ?? row.payee_name;
            try {
                const counterAccountName = row.counterAccount?.name ?? row.counter_account_name
                const children = () => [
                    h(Link, { class: 'font-bold underline text-secondary', href: `/finance/accounts/${row.counter_account_id}`}, counterAccountName),
                    h(IconTransfer, { class: 'fa fa-right-left'})
                ];
                return payeeName ?? h('div', { class: "flex justify-between items-center text-secondary text-body-1 h-4"}, children() )
            } catch {
                return ''
            }
        }
    },
    {
        label: "Description",
        name: "description",
        minWidth: 180,
        render(row: any) {
            return h('div', [
                h('div', row.description),
                h('div', row.category?.name ?? row.category_name)
            ])
        }
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
        width: 110,
        type: "custom",
        class: 'text-right'
    },
];
