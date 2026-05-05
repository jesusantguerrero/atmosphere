// CustomTable cols. Labels intentionally blank — the page renders a notification
// FEED via slots, not a tabular spreadsheet, so column headers would be noise.
// Widths kept loose so the message column flexes to fill the row.
export default [
    {
        label: '',
        name: 'data',
        class: 'text-left',
        headerClass: 'text-left px-2',
    },
    {
        label: '',
        name: 'actions',
        type: 'custom',
        class: 'text-right',
        headerClass: 'text-right px-2',
        minWidth: 120,
    },
];
