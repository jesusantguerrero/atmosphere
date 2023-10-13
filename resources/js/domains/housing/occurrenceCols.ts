import { differenceInDays, format, parseISO } from "date-fns";
import { h } from "vue";

export const occurrenceCols = [
    {
        label: "Name",
        name: "name",
        width: 400,
        class: 'w-full capitalize'
    },
    {
        label: "Last Occurrence",
        name: "last_date",
        render(row) {
            const date = row.last_date && parseISO(row.last_date)
            return h('div', {class:'text-info cursor-pointer'} , date  && format(date, "dd MMM, yyyy"))
        }
    },
    {
        label: "Duration",
        name: "current_days_count",
        width: 300,
        render(row) {
            if (row.last_date) {
                const date = row.last_date && parseISO(row.last_date)
                return differenceInDays(new Date(), date);
            }
            return 0;
        }
    },
    {
        label: "Last Duration",
        name: "previous_days_count",
        width: 300,
        class: 'text-center',
        headerClass: 'text-center',
    },
    {
        label: "AVG Days",
        name: "avg_days_passed",
        type: "custom",
        class: 'text-right',
        headerClass: 'text-right',
    },
    {
        label: "Status",
        name: "status",
        width: 200,
        class: 'py-1 flex items-center justify-center',
        headerClass: 'text-center'
    },
    {
        label: "",
        name: "actions",
        width: 300,
        type: "custom",
        class: 'text-right'
    },
];
