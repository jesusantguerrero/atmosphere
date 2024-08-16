<script setup lang="ts">
import { AtButton, AtInput } from "atmosphere-ui";
import { format as formatDate, parseISO } from "date-fns";
import { ElDialog, ElNotification } from "element-plus";
import { ref, watch, computed, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import axios from "axios";

import BaseDatePicker from "@/Components/shared/BaseDatePicker.vue";
import BaseSelect from "@/Components/shared/BaseSelect.vue";
import DepositSelect from "@/Components/shared/DepositSelect.vue";
import AppButton from "@/Components/atoms/LogerButton.vue";
import PaymentGrid from "./PaymentGrid.vue";

import { MathHelper } from "@/domains/transactions/mathHelper";
import { paymentMethods } from "@/domains/transactions/constants";
import AccountSelect from "@/Components/shared/AccountSelect.vue";
import AppFormField from "@/Components/shared/AppFormField.vue";
import { useResponsive } from "@/utils/useResponsive";

const { t } = useI18n();

const defaultPaymentForm = {
  amount: 0,
  account_id: "",
  payment_method_id: {
    id: "cash",
  },
};

const props = withDefaults(
  defineProps<{
    modelValue: boolean;
    defaultConcept?: string;
    defaultAmount?: string;
    due?: number;
    resourceId?: number;
    endpoint?: string;
    payment?: Record<string, any> | null;
    transaction?: Record<string, any> | null;
    title?: string;
    documents?: any[];
    accountsEndpoint?: string;
    hideAccountSelector?: boolean;
  }>(),
  {
    accountsEndpoint: "/api/accounts",
  }
);

const emit = defineEmits(["update:modelValue", "saved"]);

const paymentForm = ref(generatePaymentData());

const setFormData = () => {
  nextTick(() => {
    paymentForm.value = generatePaymentData();
  });
};

function generatePaymentData() {
  return {
    ...defaultPaymentForm,
    ...(props.payment?.id ? props.payment : {}),
    concept: props.defaultConcept,
    amount: props.due,
    payment_method_id: paymentMethods[0].id,
    paymentMethod: paymentMethods[0],
    payment_date: props.payment?.payment_date
      ? parseISO(props.payment?.payment_date)
      : new Date(),
    documents: props.documents ?? [],
  };
}

watch(
  () => props.defaultConcept,
  (defaultConcept) => {
    paymentForm.value.concept = defaultConcept;
  },
  {
    immediate: true,
  }
);

const setPaymentAmount = (amount: number, source: string) => {
  console.log("source: ", source);
  nextTick(() => {
    paymentForm.value.amount = amount;
  });
};

watch(
  () => props.due,
  (due) => {
    if (!paymentForm.value?.id && props.due) {
      setPaymentAmount(due, `change of due ${props.due}`);
    }
  },
  {
    immediate: true,
  }
);

watch(
  () => paymentForm.value.depositSource,
  (depositSource: Record<string, any>) => {
    if (!paymentForm.value?.id && depositSource) {
      setPaymentAmount(depositSource.amount, `change of due ${props.due}`);
    }
  },
  {
    immediate: true,
  }
);

watch(
  () => props.payment,
  (payment) => {
    if (payment) {
      setFormData();
    }
  },
  {
    deep: true,
    immediate: true,
  }
);

const documentTotal = computed(() => {
  return paymentForm.value.documents?.reduce(
    (total: number, payment: Record<string, any>) => {
      return MathHelper.sum(total, parseFloat(payment.total?? 0));
    },
    0
  );
});

watch(
  () => documentTotal.value,
  (total) => {
    setPaymentAmount(total, `change of document total ${total}`);
  }
);
const isMultiple = computed(() => {
  return paymentForm.value?.documents?.length;
});

const isLoading = ref(false);

function onSubmit() {
  if (isLoading.value) return;
  if (paymentForm.value.payment_method_id.id == "deposit") {
    applyDeposit();
    return;
  }

  if (!paymentForm.id) {
    createPayment();
    return;
  }

  isLoading.value = true;

  const formData = {
    payment_date: formatDate(paymentForm.value.payment_date || new Date(), "yyyy-MM-dd"),
    amount: paymentForm.value.amount,
    concept: paymentForm.value.concept,
    payment_method_id: paymentForm.value.payment_method,
    account_id: paymentForm.value.account_id,
    reference: paymentForm.value.reference,
    notes: paymentForm.value.notes,
    documents: paymentForm.value.documents?.filter((doc) => doc.payment),
  };

  axios
    .post(props.endpoint, formData)
    .then(() => {
      resetForm(true);
      emit("saved");
    })
    .catch((err) => {
      ElNotification({
        type: "error",
        message: err.response ? err.response.data.status.message : "Ha ocurrido un error",
      });
    })
    .finally(() => {
      isLoading.value = false;
    });
}

function createPayment() {
  if (!paymentForm.value.amount) {
    ElNotification({
      type: "error",
      message: "should specify an amount",
    });
    return;
  }

  const requiredFields = [
    "payment_date",
    "paymentMethod",
    "amount",
    ...(props.hideAccountSelector ? [] : ["account_id"]),
  ];
  const fieldsMapper = Object.entries(paymentForm.value).filter(([fieldName]) =>
    requiredFields.includes(fieldName)
  );

  const hasErrors = fieldsMapper
    .map(([fieldName, value]) => value)
    .some((value) => !value);

  if (hasErrors) {
    ElNotification({
      message: t("Check all required fields"),
      type: "error",
    });
    return;
  }

  isLoading.value = true;

  const formData = {
    resource_id: props.resourceId,
    payment_date: formatDate(paymentForm.value.payment_date || new Date(), "yyyy-MM-dd"),
    amount: paymentForm.value.amount,
    concept: paymentForm.value.concept,
    payment_method_id: paymentForm.value.payment_method,
    account_id: paymentForm.value.account_id,
    reference: paymentForm.value.reference,
    notes: paymentForm.value.notes,
    documents: paymentForm.value.documents?.filter((doc: any) => doc.payment),
  };

  axios({
    method: paymentForm.value?.id ? "put" : "post",
    url: props.endpoint,
    data: formData,
  })
    .then(() => {
      resetForm(true);
      emit("saved");
    })
    .catch((err) => {
      console.log(err);
      ElNotification({
        type: "error",
        message: err.response ? err.response.data.status.message : "Ha ocurrido un error",
      });
    })
    .finally(() => {
      isLoading.value = false;
    });
}
function applyDeposit() {
  if (!paymentForm.value.amount) {
    ElNotification({
      type: "error",
      message: "should specify an amount",
    });
    return;
  }

  const requiredFields = ["payment_date", "paymentMethod", "amount"];
  const fieldsMapper = Object.entries(paymentForm.value).filter(([fieldName]) =>
    requiredFields.includes(fieldName)
  );

  const hasErrors = fieldsMapper
    .map(([fieldName, value]) => value)
    .some((value) => !value);

  if (hasErrors) {
    ElNotification({
      message: t("Check all required fields"),
      type: "error",
    });
    return;
  }

  isLoading.value = true;

  const { transaction_id: transactionId } = paymentForm.value.depositSource

  const formData = {
    total: paymentForm.value.amount,
    payment_date: formatDate(paymentForm.value.payment_date || new Date(), "yyyy-MM-dd"),
    concept: paymentForm.value.concept,
    payment_method_id: paymentForm.value.payment_method,
    reference: paymentForm.value.reference,
    notes: paymentForm.value.notes,
    transaction_id: transactionId
  };

  const endpoint = `/api/billing-cycles/${props.resourceId}/transactions/${transactionId}/link-payments/`;
  axios({
    method: paymentForm.value?.id ? "put" : "post",
    url: endpoint,
    data: formData,
  })
    .then(() => {
      resetForm(true);
      emit("saved");
    })
    .catch((err) => {
      console.log(err);
      ElNotification({
        type: "error",
        message: err.response ? err.response.data.status.message : "Ha ocurrido un error",
      });
    })
    .finally(() => {
      isLoading.value = false;
    });
}

function resetForm(shouldClose) {
  paymentForm.value = {
    ...defaultPaymentForm,
    concept: props.defaultConcept,
  };
  if (shouldClose) {
    emitChange(false);
  }
}

function emitChange(value) {
  emit("update:modelValue", value);
}

const savePaymentText = computed(() => {
  return paymentForm.value.id ? "Update payment" : "Save payment";
});

const { isMobile } = useResponsive();

const dialogWidth = computed(() => {
  return isMobile.value ? "100%" : "50%";
});
</script>

<template>
  <ElDialog
    class="rounded-lg overflow-hidden"
    @open="setFormData()"
    :width="dialogWidth"
    :fullscreen="isMobile"
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template #header>
      <header
        class="border-b -mr-10 text-white py-4 px-4 flex items-center justify-between"
      >
        <h4 class="font-bold text-xl">{{ title ?? defaultConcept }}</h4>
        <button class="hover:text-danger" @click="close()">
          <IMdiClose />
        </button>
      </header>
    </template>
    <div class="">
      <section class="md:flex md:space-x-4">
        <AppFormField
          required
          class="w-full text-left"
          label="Concepto"
          v-model="paymentForm.concept"
        />

        <AppFormField
          class="w-full text-left"
          label="Referencia"
          v-model="paymentForm.reference"
          rounded
        />

        <AppFormField class="w-full text-left" :label="$t('amount received')" required>
          <AtInput
            class="form-control"
            number-format
            v-model="paymentForm.amount"
            rounded
            :disabled="documentTotal"
            required
          />
        </AppFormField>
      </section>

      <section class="md:flex md:space-x-4">
        <AppFormField
          v-if="!hideAccountSelector && paymentForm.payment_method_id?.id != 'deposit'"
          class="w-4/12 mb-5 text-left"
          label="Cuenta de Pago"
          required
        >
          <AccountSelect
            :endpoint="accountsEndpoint"
            v-model="paymentForm.account"
            placeholder="Selecciona una cuenta"
            @update:modelValue="paymentForm.account_id = $event?.id"
          />
        </AppFormField>
        <AppFormField
          v-else-if="paymentForm.payment_method_id?.id == 'deposit' && documents?.length"
          class="w-4/12 mb-5 text-left"
          label="Deposit"
          required
        >
          <DepositSelect
            :account-id="documents.at(0).account_id"
            category-name="security_deposits"
            v-model="paymentForm.depositSource"
            placeholder="Selecciona una cuenta"
          />
        </AppFormField>
        <AppFormField class="md:w-4/12 mb-5 text-left" label="Metodo de Pago" required>
          <BaseSelect
            v-model="paymentForm.payment_method_id"
            :client-id="payment?.client_id"
            :options="paymentMethods"
            placeholder="Forma pago"
            class="w-full"
            label="name"
            track-by="id"
          />
        </AppFormField>
        <AppFormField :label="$t('Payment date')" class="md:w-4/12 w-full" required>
          <BaseDatePicker v-model="paymentForm.payment_date" />
        </AppFormField>
      </section>

      <AppFormField class="w-full text-left" label="Notes" v-if="false">
        <textarea
          class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:border-gray-400"
          v-model="paymentForm.notes"
          cols="3"
          rows="3"
        />
      </AppFormField>

      <PaymentGrid
        :table-data="paymentForm.documents"
        :available-taxes="[]"
      />
    </div>

    <template #footer>
      <div class="space-x-2 dialog-footer flex justify-end">
        <AtButton
          :disabled="isLoading"
          @click="emitChange(false)"
          class="bg-white border rounded-md text-gray"
        >
          Cancel
        </AtButton>
        <AppButton
          @click="onSubmit()"
          :processing="isLoading"
          :disabled="isLoading"
          :loading="isLoading"
        >
          {{ $t(savePaymentText) }}
        </AppButton>
      </div>
    </template>
  </ElDialog>
</template>

<style lang="scss">
.el-dialog.is-dialog footer.el-dialog__footer {
  position: fixed !important;
  width: 100%;
  bottom: 0;
}
</style>
