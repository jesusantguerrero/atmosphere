export const getInvoiceTypeUrl = (invoice) => {
  const section = invoice.type == 'EXPENSE' ? 'bills' : 'invoices'
  return `/${section}/${invoice.id}/edit`;
}


