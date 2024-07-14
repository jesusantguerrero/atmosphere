import formatMoney from "@/utils/formatMoney";

export default [
  {
    label: "Descripción",
    name: "item",
    type: "custom",
    fixed: "true",
    class: "text-primary text-left",
    headerClass: 'text-left'
  },
  {
    label: "Fecha Limite",
    name: "due_date",
    width: "100",
    class: "text-left text-left",
    headerClass: 'text-left'
  },
  {
    label: "Monto total",
    name: "amount",
    width: "100",
    type: "money",
    class: "text-primary text-right",
    headerClass: 'text-right'
  },
  {
    label: "Monto Adeudado",
    name: "amount_due",
    width: "150",
    type: "money",
    class: "text-primary text-right",
    headerClass: 'text-right'
  },
  {
    label: "Pago",
    name: "payment",
    width: "120",
    type: "custom",
    class: "text-primary text-right",
    headerClass: 'text-right'
  },
];
