import { format, isAfter, parseISO, startOfDay } from "date-fns"
import { h } from "vue"
import IconTransfer from "@/Components/icons/IconTransfer.vue";
import { Link } from "@inertiajs/vue3";

export const tableCols = [
    {
        label: "Date",
        name: "date",
        width: 200,
        class: 'text-center',
        headerClass: 'text-center',
        render(row: any) {
            let date = new Date()
            try {
                date = parseISO(row.date)
            } catch (e) {
                console.log(e, row.date)
            }
            const hasPassed = isAfter(startOfDay(date), startOfDay(new Date()))
            return h('div', {class: hasPassed ? 'text-danger' : 'text-info cursor-pointer'} ,format(date, "dd MMM, yyyy"))
        }
    },
    {
        label: "Account",
        name: "account",
        render(row: Record<string, any>) {
            return h('div', {}, [
                h(Link, { class: 'font-bold border-b-2 border-body-1 border-dashed text-body-1', href: `/finance/accounts/${row.account_id}`}, row.account?.name ?? row.account_name),
            ])
        }
    },
    {
        label: "Payee",
        name: "payee",
        width: 700,
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
            } catch(e) {
                console.log(e)
                return ''
            }
        }
    },
    {
        label: "Description",
        name: "description",
        width: 300,
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
        width: 300,
        type: "custom",
        class: 'text-right'
    },
];
