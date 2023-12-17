<script lang="ts" setup>
import { toDate, differenceInDays } from "date-fns";
import { reactive, computed, watch, toRefs } from "vue";
import { parseISO } from "date-fns";
import { router } from "@inertiajs/vue3";

import InvoiceTotals from "../Partials/InvoiceTotals.vue";
import InvoiceGrid from "../Partials/InvoiceGrid.vue";
import ClientCard from "./InvoiceClientCard.vue";
import BusinessCard from "./BusinessCard.vue";

import { IClient } from "@/Modules/clients/clientEntity";
import { formatDate } from "@/utils";
import { IInvoice, ILineItem } from "@/Modules/invoicing/entities";
import { ElMessageBox, ElNotification } from "element-plus";
import { usePaymentModal } from "@/Modules/transactions/usePaymentModal";
import { IPayment } from "@/Modules/loans/loanEntity";

interface InvoiceLayoutTheme {
  headerLogoPosition: "left" | "right";
}

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

const { tableData, client, invoice, totals, totalValues, dueDays } = toRefs(state);

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

const hideLogo = computed(() => {
  return Boolean(Number(props.businessData.invoice_hide_company_logo));
});
</script>

<template>
  <section class="relative w-full rounded-md simple-template">
    <div class="relative section-body">
      <header class="pt-4 text-sm print:pt-0 invoice__header">
        <section class="flex justify-between w-full px-4 invoice-details">
          <article class="flex items-center w-full">
            <img
              :src="imageUrl"
              v-if="imageUrl && !hideLogo"
              class="w-40 rounded-md mr-2"
            />
            <BusinessCard :business="businessData" class="w-full text-left" />
          </article>

          <article class="w-full text-right">
            <h4 class="px-5 text-2xl font-bold text-primary">
              Factura {{ invoice.series }}-{{ invoice.number }}
            </h4>
            <h5 class="text-md">
              <span class="font-bold">Concepto:</span> {{ invoice.concept }}
            </h5>
            <p>
              <span class="font-bold">Fecha</span>
              {{ formatDate(invoice.date) }}
            </p>
            <p>
              <span class="font-bold">Fecha Limite</span>
              {{ formatDate(invoice.due_date) }}
            </p>
          </article>
        </section>

        <section class="flex px-4 space-x-4">
          <ClientCard class="w-8/12 text-left" label="Cliente" :client="client" />
        </section>
      </header>

      <InvoiceGrid
        :tableData="tableData"
        :is-editing="false"
        :hidden-cols="['quantity', 'discount']"
        class="w-full mt-5 main-grid"
      >
        <template #prepend>
          <div class="py-1 text-base font-bold text-center text-body-1">
            {{ invoice.concept }} {{ invoice.description }}
          </div>
        </template>
      </InvoiceGrid>

      <div class="flex justify-end px-4 mt-5 text-gray-600">
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
          @edit-payment="editPayment"
          @delete-payment="removePayment"
        />
      </div>

      <div
        class="flex text-sm text-center invoice-footer-details mt-14"
        v-if="invoice.id"
      >
        <div class="w-full text-gray-600">
          <p class="font-bold text-gray-600">Gracias por su preferencia!</p>
          <div class="mt-5 font-bold text-gray-600" v-if="invoice.type == 'INVOICE'">
            Terminos y condiciones
          </div>
          <div v-if="invoice.type == 'INVOICE'">
            El pago debe ser dentro de {{ dueDays }} dias
          </div>
        </div>

        <section class="flex flex-col items-end justify-center w-full text-right">
          <div class="mx-auto mb-2 font-serif border-b-2 invoice__signature w-96" />
          <article class="justify-center w-full text-center">
            <div class="font-bold">{{ user?.name }}</div>
            <div>Firma</div>
          </article>
        </section>
      </div>

      <span class="mt-8 stamp is-approved" v-if="invoice.debt == 0">Pagado</span>
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

.section-body {
  padding: 0 15px;
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

.invoice-footer-details {
  display: grid;
  grid-template-columns: 300px 1fr;
  grid-column-gap: 15px;
  padding: 0 15px;
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
.main-grid {
  .el-table__body-wrapper td {
    font-size: 1em !important;
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

@media print {
  .section-body {
    width: 100%;
    display: block;
    font-size: 12px;
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
} ;
</style>
