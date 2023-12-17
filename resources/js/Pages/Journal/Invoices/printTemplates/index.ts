const invoiceLayouts = {
  store: {
    headerLogoPosition: "left",
    accentColor: "rgb(244 114 182)",
    secondaryColor: "rgb(147 51 234)",
    subheaderCards: ["contactInfo", "businessInfo"],
  },
  school: {
    headerLogoPosition: "right",
    accentColor: "#0A1B6A",
    secondaryColor: "#E60C15",
    subheaderCards: ["contactInfo", "totalDue"],
  },
  freelanceBlack: {
    headerLogoPosition: "right",
    accentColor: "#293379",
    secondaryColor: "rgb(37 99 235)",
    subheaderCards: ["contactInfo", "totalDue"],
  },
  premium: {
    headerLogoPosition: "left",
    headerHideInvoiceDetails: true,
    accentColor: "rgb(59 130 246)",
    secondaryColor: "rgb(51 65 85)",
    subheaderCards: ["contactInfo", "invoiceDetails"],
    tableHeaderCellDivider: true,
    tableHeaderCellAlign: "center",
  },
  premiumLeft: {
    headerLogoPosition: "right",
    headerLogoSize: "sm",
    headerLogoOnly: true,
    headerHideInvoiceDetails: true,
    accentColor: "rgb(59 130 246)",
    secondaryColor: "rgb(51 65 85)",
    subheaderCards: ["invoiceDetails", "contactInfo"],
    tableHeaderCellDivider: true,
    tableHeaderCellAlign: "center",
    lineItemImage: false,
  }
}

export const getInvoiceLayout = (layoutName: string) => {
  const appProfileToInvoiceTemplate = {
    school: 'school',
    store: 'store'
  }
  return invoiceLayouts[appProfileToInvoiceTemplate[layoutName]] ?? invoiceLayouts.store;
}
