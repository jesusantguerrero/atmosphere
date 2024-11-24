import { format, isAfter, parseISO, startOfDay } from "date-fns"
import { h } from "vue"
import IconTransfer from "@/Components/icons/IconTransfer.vue";
import { Link } from "@inertiajs/vue3";

export const tableAccountCols = (accountId: number, showSelects?: false) => [
    ...( showSelects ? [{
        label: "",
        name: "selection",
        width: 20,
        class: 'text-center',
        headerClass: 'text-center',
        render(row: any) {
            return h('input', { type: 'checkbox'})
        },
    }] : []),
    {
        label: "Date",
        name: "date",
        width: 150,
        align: "center",
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
        label: "Payee",
        name: "payee",
        class: 'w-full',
        width: 200,
        render(row: any) {
            try {
                const account = row.account_id === accountId ? row.counter_account : row.account
                const children = () => [
                    h(Link, { class: 'font-bold underline text-secondary', href: `/finance/accounts/${account.id}`}, `${account?.name}`),
                    h(IconTransfer, { class: 'fa fa-right-left'})
                ];
                return row.payee
                ? h(Link, { class: 'font-bold text-primary', href: `/finance/lines?filter[payee_id]=${row.payee.id}`}, row.payee.name)
                : h('div', { class: "flex justify-between items-center text-body-1 h-4"}, children() )
            } catch(e) {
                return ''
            }
        }
    },
    {
        label: "Description/category",
        name: "description",
        width: 220,
        render(row: any) {
            return h('div', [
                h('div', row.description),
                h('div', { class: 'text-primary font-bold' },  row.category?.name ?? row.category_name)
            ])
        }
    },
    {
        label: "Amount",
        name: "total",
        type: "custom",
        align: 'right',
        class: 'text-right',
        width: 200,
        headerClass: 'text-right',
    },
    {
        label: "",
        name: "actions",
        type: "custom",
        class: 'text-right'
    },
];
