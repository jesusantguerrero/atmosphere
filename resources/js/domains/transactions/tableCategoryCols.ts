import { format, isAfter, parseISO, startOfDay } from "date-fns"
import { h } from "vue"
import { Link } from "@inertiajs/vue3";

export const tableCategoryCols = (categoryId: number, showSelects?: false) => [
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
                return row.payee && h(Link, { class: 'font-bold text-primary', href: `/finance/lines?filter[payee_id]=${row.payee.id}`}, row.payee.name)
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
            const mainLine = row.splits ? row.splits.find((line: any) => line.category_id == categoryId) : null;
            const category = mainLine?.category ?? row.category;

            return h('div', [
                h('div', mainLine?.concept ?? row.description),
                h(Link, { class: 'text-primary font-bold', href: `/finance/lines?filter[category_id]=${category.id ?? row.category_id}`},  category?.name ?? row.category_name)
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
