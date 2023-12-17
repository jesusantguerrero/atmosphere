export default name => {
    return [
      {
        label: name || "Account Name",
        name: "display_id",
        width: 200
      },
      {
        label: "",
        name: "name",
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
