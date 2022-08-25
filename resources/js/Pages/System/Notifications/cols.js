export default [
    {
        label: "Notification Message",
        name: "data",
        class: "text-left",
        sortable: true,
        headerClass: "text-left px-2",
        render(row) {
            return row.data.message || "N/D";
        },
        width: 300,
    },
    {
        label: "Actions",
        name: "actions",
        type: "custom",
        class: "text-right",
        headerClass: "text-right px-2",
        minWidth: 100,
    },
];
