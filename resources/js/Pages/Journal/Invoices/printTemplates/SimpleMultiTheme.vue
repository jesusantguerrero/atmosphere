<script lang="ts" setup>
import { toDate, differenceInDays } from "date-fns";
import { reactive, computed, watch, toRefs, onMounted, useCssVars, ref } from "vue";
import { parseISO } from "date-fns";
import { router } from "@inertiajs/vue3";

import InvoiceTotals from "../Partials/InvoiceTotals.vue";
import InvoiceGrid from "../Partials/InvoiceGrid.vue";

import { IClient } from "@/Modules/clients/clientEntity";
import { formatDate, formatMoney } from "@/utils";
import { IInvoice, ILineItem } from "@/Modules/invoicing/entities";
import { ElMessageBox, ElNotification } from "element-plus";
import { usePaymentModal } from "@/Modules/transactions/usePaymentModal";
import { IPayment } from "@/Modules/loans/loanEntity";
import InvoiceHeader from "./InvoiceHeader.vue";
import InvoiceSubHeader from "./InvoiceSubHeader.vue";
import { useI18n } from "vue-i18n";
import SignaturePad from "../Partials/SignaturePad.vue";

interface InvoiceLayoutTheme {
  headerLogoPosition: "left" | "right";
  headerLogoSize?: "sm" | "md" | "lg";
  headerLogoOnly?: boolean;
  headerHideInvoiceDetails?: boolean;
  subheaderCards: string[];
  accentColor: string;
  accentColorBackground: string;
  tableHeaderCellDivider?: boolean;
  tableHeaderCellAlign?: "center" | "left" | "right";
}

defineEmits(["signature-clicked"]);

const props = withDefaults(
  defineProps<{
    imageUrl: string;
    type: string;
    user: Record<string, string>;
    businessData: Record<string, string>;
    products?: Record<string, string>[];
    clients?: IClient[];
    invoiceData: IInvoice;
    layoutTheme: InvoiceLayoutTheme;
  }>(),
  {
    type: "INVOICE",
    imageUrl: "/logo.png",
    layoutTheme: () => ({
      headerLogoPosition: "left",
      subheaderCards: ["contactInfo", "businessInfo"],
      accentColor: "text-pink-400",
      accentColorBackground: "text-white bg-pink-400",
    }),
  }
);

const state: any = reactive({
  totalValues: {},
  totals: {
    subtotalField: "subtotal",
    totalField: "amount",
    discountField: "discountTotal",
    subtotalFormula(row: ILineItem) {
      return row.quantity * row.price;
    },
    totalFormula(row: ILineItem) {
      return row.quantity * row.price;
    },
    discountFormula(row: ILineItem) {
      return row.quantity * row.price;
    },
  },
  invoice: {},
  selectedPayment: null,
  isPaymentDialogVisible: false,
  modals: {
    email: {
      value: false,
    },
  },
  tableData: [],
  client: null,
  imageUrl: "",
  dueDays: computed(() => {
    return differenceInDays(state.invoice.due_date, state.invoice.date);
  }),
});

const setInvoiceData = (data: Record<string, any>) => {
  if (data) {
    data.date = toDate(parseISO(data.date) || new Date());
    data.due_date = toDate(parseISO(data.due_date) || new Date());
    state.invoice = data;
    state.client = data.client;
    state.tableData =
      data.lines.sort((a: Record<string, string>, b: Record<string, string>) =>
        a.index > b.index ? 1 : -1
      ) || [];
  }
};

watch(
  () => props.invoiceData,
  (data) => {
    if (data) {
      setInvoiceData(data);
    }
  },
  { immediate: true }
);

const { tableData, invoice, totals, totalValues, dueDays } = toRefs(state);

const { t } = useI18n();
const availableCards = computed(() => {
  return {
    contactInfo: {
      type: "ContactInfo",
      client: state?.client,
      label: t("invoice to"),
      class: "invoice-secondary-text w-fit w-full",
    },
    businessInfo: {
      type: "BusinessInfo",
      business: props.businessData,
      label: t("invoice from"),
      class: "invoice-secondary-text",
    },
    totalDue: {
      type: "InvoiceTotalItem",
      label: t("total due"),
      value: formatMoney(invoice.value.debt),
      class: "invoice-accent-text capitalize",
      vertical: true,
      valueSize: "lg",
    },
    invoiceDetails: {
      type: "InvoiceDetails",
      logoUrl: props.imageUrl,
      invoiceDate: formatDate(invoice.value.date),
      logoPosition: props.layoutTheme.headerLogoPosition,
      hideInvoiceDetails: props.layoutTheme.headerHideInvoiceDetails,
      invoiceNumber: invoice.value.number,
      displayCompanyInfo: true,
      invoiceTitle: t("invoice"),
      invoiceTitleClass: "invoice-accent-text",
      class: "pt-0 mt-0",
    },
  };
});
const visibleHeaderCards = computed(() => {
  return props.layoutTheme.subheaderCards
    .map((name: string) => availableCards.value[name])
    .filter((value) => value);
});

const { openModal } = usePaymentModal();
const editPayment = (payment: IPayment) => {
  const url =
    props.invoiceData.invoiceable_type === "App\\Domains\\Properties\\Models\\Rent"
      ? `/rents/${props.invoiceData.invoiceable_id}/invoices/${payment.payable_id}/payments/${payment.id}`
      : `/invoices/${payment.payable_id}/payments/${payment.id}`;

  openModal({
    data: {
      title: `Pagar ${props.invoiceData.concept}`,
      payment: payment,
      endpoint: url,
      due: payment.amount,
      account_id: payment.account_id,
      defaultConcept: payment.concept,
    },
  });
};

const removePayment = async (payment: Record<string, string>) => {
  const isConfirmed = await ElMessageBox.confirm(
    `Estas seguro de eliminar el pago ${payment.concept}?`,
    "Eliminar pago"
  );

  if (!isConfirmed) return;
  router.delete(`/payments/${payment.id}`, {
    onSuccess() {
      ElNotification({
        message: `Pago ${payment.due_date} borrada con exito`,
        title: "Pago eliminada",
        type: "success",
      });
    },
  });
};

const invoiceTemplateRef = ref();
onMounted(() => {
  invoiceTemplateRef.value.style?.setProperty(
    "--invoice-accent-color",
    props.layoutTheme.accentColor
  );
  invoiceTemplateRef.value.style?.setProperty(
    "--invoice-table-header-cell-align",
    props.layoutTheme.tableHeaderCellAlign
  );
});
</script>

<template>
  <section
    class="relative w-full rounded-md simple-template shein-store-invoice"
    ref="invoiceTemplateRef"
  >
    <div class="relative section-body">
      <InvoiceHeader
        :logo-url="imageUrl"
        :logo-size="layoutTheme.headerLogoSize"
        :logo-only="layoutTheme.headerLogoOnly"
        :business-data="businessData"
        :invoice-date="formatDate(invoice.date)"
        :logo-position="layoutTheme.headerLogoPosition"
        :hide-invoice-details="layoutTheme.headerHideInvoiceDetails"
        :invoice-number="invoice.number"
        :display-company-info="true"
        :invoice-title="$t('invoice')"
        invoice-title-class="invoice-accent-text"
        class="px-5"
      />

      <InvoiceSubHeader class="px-10" :cards="visibleHeaderCards" />

      <InvoiceGrid
        :tableData="tableData"
        :is-editing="false"
        :hidden-cols="['taxes']"
        class="w-full pb-4 mt-5 border-b main-grid"
        :class="{
          'header-cell-dividers': layoutTheme.tableHeaderCellDivider,
          'header-cell-align': layoutTheme.tableHeaderCellAlign,
        }"
      >
        <template v-slot:concept="{ row, col }">
          <div class="flex px-5 py-1 text-base font-bold text-body-1">
            <div
              class="flex items-center justify-center mr-4 overflow-hidden font-bold text-gray-400 border border-gray-300 rounded-md w-14 h-14 bg-gray-50"
            >
              <img
                :src="row.product_image"
                alt=""
                v-if="row.product_image"
                style="
                  min-width: 100%;
                  min-height: 100%;
                  object-fit: cover;
                  object-position: top;
                "
              />
              <i class="text-xl fa fa-images" v-else />
            </div>

            <span>
              {{ row.concept }}
            </span>
          </div>
        </template>
      </InvoiceGrid>

      <div class="flex justify-end px-4 text-gray-600">
        <InvoiceTotals
          :tableData="tableData"
          :subtotal-field="totals.subtotalField"
          :discount-field="totals.discountField"
          :payments="invoice.payments"
          :total-values="totalValues"
          :total-field="totals.totalField"
          :subtotalFormula="totals.subtotalFormula"
          :discountFormula="totals.discountFormula"
          :totalFormula="totals.totalFormula"
          :hide-debt="true"
          :hide-payments="true"
          :hide-separators="true"
          total-class="bg"
          @edit-payment="editPayment"
          @delete-payment="removePayment"
        >
          <template v-slot:total="{ value, label }">
            <section
              class="grid grid-cols-2 gap-2 px-2 py-1.5 font-bold divide-x rounded-sm invoice-accent-background"
            >
              <span class="py-1 uppercase">
                {{ label }}
              </span>
              <span class="py-1 pl-4">
                {{ value }}
              </span>
            </section>
          </template>
        </InvoiceTotals>
      </div>

      <div class="flex text-sm text-center px-14 mt-14" v-if="invoice.id">
        <section class="w-6/12 text-left text-gray-600">
          <p class="font-bold text-gray-600">Gracias por su preferencia!</p>
          <div class="mt-5 font-bold text-gray-600" v-if="invoice.type == 'INVOICE'">
            Terminos y condiciones
          </div>
          <p class="mb-2 whitespace-pre-line w-full">
            {{ businessData.invoice_payment_instructions }}
          </p>
          <div v-if="invoice.type == 'INVOICE'">
            El pago debe ser dentro de {{ dueDays }} dias
          </div>
        </section>

        <section
          class="flex flex-col items-end justify-center w-6/12 text-right cursor-pointer"
        >
          <section
            @click="$emit('signature-clicked')"
            class="px-2 pt-5 transition rounded-md hover:bg-base-lvl-2 w-full"
          >
            <SignaturePad
              :signatures="invoice.signatures"
              :entity="invoice"
              :label="user?.name"
            />
          </section>
        </section>
      </div>

      <span class="mt-8 stamp is-approved" v-if="invoice.debt == 0">{{
        $t("paid")
      }}</span>
    </div>
  </section>
</template>

<style lang="scss" scoped>
.totals-container {
  background: white;
  display: flex;
  justify-content: flex-end;
}

.invoice-title {
  padding-left: 15px;
}

.invoice-actions {
  margin-bottom: 15px;

  .btn {
    height: 38px;
  }

  [class*="col-md"] {
    padding: 0 0 0 0;

    &:first-child {
      padding-left: 15px;
    }

    &:last-child {
      padding-right: 15px;
    }
  }

  .btn,
  button,
  input {
    border-radius: 0 0 0 0 !important;
  }

  .btn-primary {
    background: dodgerblue;
  }
}

.main-grid {
  thead th {
    @apply text-white;
  }
  .el-table__body-wrapper td {
    font-size: 0.8em !important;
  }
}

.invoice-totals {
  font-size: 12px;

  &__add-payment {
    width: 100%;
    height: 34px;
    background: dodgerblue;
    color: white;
    border: none;
    font-weight: bolder;
    transition: all ease 0.3s;

    &:hover {
      font-size: 1.01em;
    }

    &:focus {
      outline: none;
    }
  }
}

.invoice-form-row {
  display: inline-grid;
  width: 100%;
  grid-column-gap: 0;
  grid-template-columns: 20% 80%;

  label {
    display: flex;
    align-items: center;
  }
}

.invoice-header-container {
  position: inherit;
}

.el-collapse {
  margin-bottom: 15px;
}

.simple-template {
  padding-bottom: 25px;
  background: white;
}
</style>

<style lang="scss">
:root {
  --invoice-accent-color: #333;
  --invoice-table-header-cell-align: "center";
}

.invoice-accent-background {
  background-color: var(--invoice-accent-color);
  color: white;
}

.invoice-accent-text {
  color: var(--invoice-accent-color);
}

.main-grid {
  .el-table__body-wrapper td {
    font-size: 1em !important;
  }

  &.header-cell-align {
    thead th {
      text-align: var(--invoice-table-header-cell-align) !important;
    }
  }

  &.header-cell-dividers {
    thead th {
      border-left: 2px solid white;
      border-right: 2px solid white;
    }
  }
}

.stamp {
  transform: rotate(12deg);
  color: #555;
  font-size: 2rem;
  font-weight: 700;
  border: 0.25rem solid #555;
  display: inline-block;
  padding: 0.25rem 1rem;
  text-transform: uppercase;
  border-radius: 1rem;
  font-family: "Courier";
  mask-image: url("/grunge.png");
  -webkit-mask-image: url("/grunge.png");
  mask-size: 944px 604px;
  -webkit-mask-size: 944px 604px;
  mix-blend-mode: multiply;
  position: absolute;
  top: 120px;
  right: 25px;
}

.is-approved {
  color: #0a9928;
  border: 0.5rem solid #0a9928;
  mask-position: 13rem 6rem;
  -webkit-mask-position: 13rem 6rem;
  transform: rotate(-14deg);
  font-size: 2rem;
  border-radius: 0;
}

.is-draft {
  color: #c4c4c4;
  border: 1rem double #c4c4c4;
  transform: rotate(-5deg);
  font-size: 1.5rem;
  font-family: "Open sans", Helvetica, Arial, sans-serif;
  border-radius: 0;
  padding: 0.5rem;
}
.shein-store-invoice {
  thead,
  thead th {
    @extend .invoice-accent-background !optional;
  }
}

@media print {
  .section-body {
    width: 100%;
    display: block;
    font-size: 12px;
    max-width: 210mm;
    width: 510mm;
    min-height: 265mm;
    height: auto;
  }
  @page {
    marks: cross;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
  }

  tbody,
  .client-card {
    font-size: 12px;
  }
}
</style>
