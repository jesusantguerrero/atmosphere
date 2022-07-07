import formatMoney from "@/utils/formatMoney";

export const tableCols = [
    {
        label: "Date",
        name: "date",
        width: 200,
    },
    {
        label: "Payee",
        name: "payee",
        width: 200,
        render(row) {
            return row.payee?.name
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
            return formatMoney(row.total)
        }
    },
    {
        label: "",
        name: "actions",
        width: 300,
        type: "custom",
    },
];
