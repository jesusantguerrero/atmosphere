export default name => {
    return [
      {
        label: name || "Account Name",
        name: "display_id",
        width: 200,
        type: "custom",
        render(row) {
          return `${row.number} - ${row.alias || row.name}`
        }
      },
      {
        label: "",
        name: "display_id",
        width: 300
      },
      {
        label: "",
        name: "description"
      },
      {
        label: "",
        name: "actions",
        width: 300,
        type: "custom"
      }
    ];
  };
