<script lang="ts" setup>
import { format as formatDate } from "date-fns";
import parseISO from "date-fns/esm/fp/parseISO/index.js";
import { useForm, router } from "@inertiajs/vue3";
import { AtInput, AtField, AtFieldCheck } from "atmosphere-ui";
import { computed, reactive, toRefs, watch, toRaw } from "vue";

import AppButton from "@/Components/shared/AppButton.vue";
import InvoiceTotals from "./InvoiceTotals.vue";
import InvoiceGrid from "./InvoiceGrid.vue";

import { usePaymentModal } from "@/Modules/transactions/usePaymentModal";
import {
  ElCollapse,
  ElCollapseItem,
  ElDatePicker,
  ElMessageBox,
  ElNotification,
} from "element-plus";
import { IPayment } from "@/Modules/loans/loanEntity";
import AccountSelect from "@/Components/shared/Selects/AccountSelect.vue";
import BaseSelect from "@/Components/shared/BaseSelect.vue";

const props = defineProps({
  type: {
    type: String,
    default: "INVOICE",
  },
  user: Object,
  products: Array,
  clients: Array,
  invoiceData: [Object, null],
  availableTaxes: [Array, null],
  isEditing: Boolean,
});

const labels = {
  expense: {
    contact: "Proveedor",
    documentNumber: "Bill Number",
    orderNumber: "P.O/S.O Number",
  },
  invoice: {
    contact: "Cliente",
    documentNumber: "No. factura",
    orderNumber: "No. Orden",
  },
};

const getLabel = (key: string) => labels[props.type.toLowerCase()][key];

const state: any = reactive({
  totalValues: {},
  totals: {
    subtotalField: "subtotal",
    totalField: "amount",
    discountField: "discountTotal",
    subtotalFormula(row: Record<string, number>) {
      return row.quantity * row.price;
    },
    totalFormula(row: Record<string, number>) {
      return row.quantity * row.price;
    },
    discountFormula(row: Record<string, number>) {
      return row.quantity * row.price;
    },
  },
  invoice: useForm({
    id: null,
    number: null,
    concept: null,
    date: new Date(),
    due_date: new Date(),
    client: null,
    client_id: null,
    footer: null,
    notes: null,
    payments: [],
    debt: 0,
    status: "DRAFT",
    created_at: null,
    updated_at: null,
    taxes_included: false,
  }),
  selectedPayment: null,
  isPaymentDialogVisible: false,
  modals: {
    email: {
      value: false,
    },
  },
  activeSections: [],
  tableData: [],
  imageUrl: "",
  isDraft: computed(() => {
    return !state.invoice.status || state.invoice.status.toLowerCase() == "draft";
  }),
  section: computed(() => {
    return props.type.toLowerCase();
  }),
});

const setInvoiceData = (data: Record<string, any>) => {
  if (data) {
    data.date = parseISO(data.date) || new Date();
    data.due_date = parseISO(data.due_date) || new Date();

    Object.keys(data).forEach((key) => {
      state.invoice[key] = data[key];
    });

    state.tableData =
      data.lines
        .sort((a, b) => (a.index > b.index ? 1 : -1))
        .map((line) => {
          const itemTaxes = line.taxes?.length ? toRaw(line.taxes) : [];
          line.taxes = [...itemTaxes, { id: "new" }];
          return line;
        }) || [];
  }
};

watch(
  () => props.invoiceData,
  (invoiceData) => {
    setInvoiceData(invoiceData);
  },
  { immediate: true }
);

const { openModal } = usePaymentModal();
const editPayment = (payment: IPayment) => {
  openModal({
    data: {
      title: `Pagar ${invoice.concept}`,
      payment: payment,
      endpoint: `/invoices/${payment.payable_id}/payment/${payment.id}`,
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

const setRequestData = (data: Record<string, any>): Record<string, any> => {
  const requestData = {
    ...data,
    items: state.tableData
      .map((item, index) => {
        item.index = index;
        item.quantity = parseFloat(item.quantity);
        item.price = parseFloat(item.price);
        return item;
      })
      .filter((item) => item.concept),
  };
  requestData.date = formatDate(data.date || new Date(), "yyyy-MM-dd");
  requestData.due_date = formatDate(data.due_date || requestData.date, "yyyy-MM-dd");

  const type = props.type != "INVOICE" ? "EXPENSE" : props.type;
  requestData.resource_type_id = type;
  requestData.type = type;
  requestData.concept = requestData.concept || state.section;

  requestData.total = state.totalValues.total;
  requestData.discount = state.totalValues.discountTotal;
  requestData.subtotal = state.totalValues.subtotal;
  requestData.taxes = state.totalValues.taxes;
  delete requestData.lines;
  delete requestData.paymentDocs;
  delete requestData.client;

  return requestData;
};

const sendRequest = (
  method: string,
  url: string,
  formData: Record<string, any>,
  message: string
) => {
  return router[method](url, formData, {
    onSuccess() {
      const section = formData.type == "EXPENSE" ? "bills" : "invoices";
      router.replace(`/${section}/${formData.id}`);
    },
  });
};

const saveForm = (status: number) => {
  const formData = setRequestData({
    ...state.invoice,
    type: props.type,
  });
  if (status) {
    formData.status = 2;
  }
  let message = "Invoice created";
  let method = "post";
  let url = `/invoices`;

  if (state.invoice.id) {
    url = `/invoices/${state.invoice.id}`;
    message = "Invoice updated";
    method = "put";
  }

  sendRequest(method, url, formData, message);
};

const cloneInvoice = (status: number) => {
  const formData = setRequestData(state.invoice);
  if (status) {
    formData.status = 2;
  }

  let message = "Invoice created";
  let method = "post";
  let url = `/invoices/${state.invoice.id}/clone`;

  sendRequest(method, url, formData, message)
    .then((invoice) => {
      getInvoice(invoice.id);
    })
    .catch((err) => {
      console.log(err);
    });
};

const handleImageChange = (file: File) => {
  state.imageUrl = URL.createObjectURL(file.raw);
  state.invoice.logo = file;
};

const onTaxesUpdated = ({ rowIndex, taxes }: { rowIndex: number; taxes: any[] }) => {
  state.tableData[rowIndex].taxes = taxes;
};

const { invoice, totals, tableData, totalValues, isPaymentDialogVisible } = toRefs(state);

defineExpose({
  saveForm,
  cloneInvoice,
});
</script>

<template>
  <section class="w-full py-2 rounded-md section">
    <div class="section-body relative">
      <div class="invoice-body">
        <ElCollapse v-model="activeSections" class="w-full">
          <ElCollapseItem
            :title="$t('Logo, concept and description')"
            name="header"
            :expanded="true"
          >
            <div class="invoice-header-details">
              <el-upload
                class="avatar-uploader"
                :v-model="invoice.logo"
                :show-file-list="false"
                :on-change="handleImageChange"
                :auto-upload="false"
              >
                <img v-if="state.imageUrl" :src="state.imageUrl" class="avatar" />
                <IMdiPlus v-else class="avatar-uploader-icon" />
              </el-upload>

              <div class="space-y-4 invoice-details">
                <div class="form-group">
                  <AtInput
                    type="text"
                    class="form-control"
                    name="invoice-description"
                    id="invoice-description"
                    v-model="invoice.description"
                    rounded
                  >
                    <template #prefix>
                      <div
                        class="h-full flex items-center capitalize text-normal font-bold text-primary"
                      >
                        <span>
                          {{ $t("description") }}
                        </span>
                      </div>
                    </template>
                  </AtInput>
                </div>
                <div class="form-group">
                  <AtInput
                    type="text"
                    class="form-control"
                    name="invoice-description"
                    id="invoice-description"
                    v-model="invoice.concept"
                    rounded
                  >
                    <template #prefix>
                      <div
                        class="h-full flex items-center capitalize text-normal font-bold text-primary"
                      >
                        <span>
                          {{ $t("concept") }}
                        </span>
                      </div>
                    </template>
                  </AtInput>
                </div>
              </div>
            </div>
          </ElCollapseItem>
        </ElCollapse>

        <div class="flex space-x-4">
          <div class="w-6/12 text-left">
            <AtField :label="getLabel('contact')">
              <BaseSelect
                v-model="invoice.client"
                @update:model-value="invoice.client_id = $event.id"
                endpoint="/api/clients"
                placeholder="Selecciona cliente"
                label="display_name"
                track-by="id"
              />
            </AtField>
            <div v-if="invoice.client">
              <p>
                {{ invoice.client.fullName }}
              </p>
              <p v-if="invoice.client.country">{{ invoice.client.country }}</p>
              <p v-if="invoice.client.tax_number">
                <strong>CIF/NIF:</strong> <span>{{ invoice.client.tax_number }}</span>
              </p>
              <p>
                {{ invoice.client.email }}
              </p>
            </div>
            <AtField label="Cuenta" class="w-full">
              <AccountSelect
                endpoint="/api/accounts"
                v-model="invoice.account"
                @update:model-value="invoice.account_id = $event.id"
                class="md:w-full"
              />
            </AtField>
          </div>

          <div class="flex justify-between w-6/12 space-x-4 text-left">
            <div class="w-full">
              <AtField label="Fecha" class="flex flex-col">
                <ElDatePicker
                  v-if="invoice.date"
                  v-model="invoice.date"
                  size="large"
                  id="invoice-date"
                  type="date"
                  title="seleccione una fecha"
                  placeholder="selecciona una fecha"
                />
              </AtField>

              <AtField label="Fecha Limite" class="mt-2 flex flex-col">
                <ElDatePicker
                  v-if="invoice.due_date"
                  v-model="invoice.due_date"
                  size="large"
                  id="invoice-due-date"
                  type="date"
                  title="seleccione una fecha"
                  placeholder="selecciona una fecha"
                />
              </AtField>
            </div>

            <div class="w-full">
              <AtField :label="getLabel('documentNumber')">
                <AtInput
                  v-model="invoice.number"
                  type="text"
                  name="invoice-number"
                  id="invoice-number"
                  class="border-gray-100"
                  rounded
                />
              </AtField>
              <AtField :label="getLabel('orderNumber')" class="mt-2">
                <AtInput
                  v-model="invoice.order_number"
                  type="text"
                  name="invoice-order-number"
                  id="invoice-order-number"
                  class="border-gray-100"
                  rounded
                />
              </AtField>
            </div>
          </div>
        </div>
      </div>

      <InvoiceGrid
        :tableData="tableData"
        :products="products"
        :taxes="taxes"
        :is-editing="isEditing"
        :available-taxes="availableTaxes"
        @taxes-updated="onTaxesUpdated"
        class="mt-10"
      />
      <div class="totals-container">
        <div>
          <AtFieldCheck label="Taxes included" v-model="invoice.taxes_included" />
        </div>
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
          :invoice-taxes="invoiceTaxes"
          :is-tax-included="invoice.taxes_included"
          @edit-payment="editPayment"
          @delete-payment="removePayment"
        >
          <template v-slot:add-payment v-if="!state.isDraft">
            <div>
              <AppButton class="w-full" @click="isPaymentDialogVisible = true">
                Add Payment
              </AppButton>
            </div>
          </template>
        </InvoiceTotals>
      </div>
      <div class="flex text-left invoice-footer-details" v-if="invoice.id">
        <div class="w-full">
          <label for=""> Footer </label>
          <textarea
            name=""
            class="w-full border border-gray-200 rounded-md focus:outline-none focus:border-gray-400"
            id=""
            cols="30"
            rows="5"
            v-model="invoice.footer"
          ></textarea>
        </div>

        <div class="w-full">
          <label for="" class="block"> Notes </label>
          <textarea
            name=""
            class="w-full border border-gray-200 rounded-md focus:outline-none focus:border-gray-400"
            id=""
            cols="30"
            rows="5"
            v-model="invoice.notes"
          ></textarea>
        </div>
      </div>
    </div>
  </section>
</template>

<style lang="scss" scoped>
.totals-container {
  background: white;
  display: flex;
  justify-content: flex-end;
  margin-right: 20px;
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

.invoice-header-details,
.invoice-footer-details {
  display: grid;
  grid-template-columns: 300px 1fr;
  grid-column-gap: 15px;
  padding: 0 15px;
}

.el-collapse {
  margin-bottom: 15px;
}

section {
  padding-bottom: 25px;
  background: white;
}

.avatar-uploader .el-upload {
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}
.avatar-uploader .el-upload:hover {
  border-color: #409eff;
}
.avatar-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 178px;
  height: 178px;
  line-height: 178px;
  text-align: center;
}
.avatar {
  width: 178px;
  height: 178px;
  display: block;
}
</style>
