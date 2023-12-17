<script setup lang="ts">
import { AtButton, AtField, AtInput, AtSimpleSelect } from "atmosphere-ui";
import { format as formatDate, parseISO } from "date-fns";
import { ElDatePicker, ElDialog, ElNotification } from "element-plus";
import { ref, watch, computed } from "vue";

import AppButton from "@/Components/shared/AppButton.vue";

import { paymentMethods } from "@/Modules/loans/constants";
import AppFormField from "@/Components/shared/AppFormField.vue";
import { useForm } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { useResponsive } from "@/utils/useResponsive";

const props = withDefaults(
  defineProps<{
    modelValue: boolean;
    invoiceData?: IInvoice;
    due: number;
    endpoint?: string;
    title: string;
    hideClientOptions?: boolean;
    clientId: string;
    rentId: string;
    type: string;
  }>(),
  {
    type: "expense",
    title: "Crear factura",
    due: 0,
  }
);

const emit = defineEmits(["update:modelValue", "saved"]);

const formData = useForm({
  concept: "",
  description: "",
  total: 0,
  due_date: new Date(),
});

watch(
  () => props.invoiceData,
  (data) => {
    formData.concept = data?.concept;
    formData.total = data?.total;
    formData.due_date = parseISO(data?.due_date);
    formData.description = data?.description;
  },
  {
    immediate: true,
  }
);

const isLoading = ref(false);

const { t } = useI18n();
function onSubmit() {
  if (isLoading.value) return;
  if (!+formData.total) {
    ElNotification({
      type: "error",
      message: t("should specify an amount"),
    });
    return;
  }

  const data = {
    ...formData.data(),
    due_date: formatDate(formData.due_date || new Date(), "yyyy-MM-dd"),
  };

  isLoading.value = true;
  const url = `/rents/${props.invoiceData.invoiceable_id}/invoices/${props.invoiceData?.id}/simple-update`;

  axios
    .put(url, data)
    .then(() => {
      resetForm(true);
      emit("saved");
    })
    .catch((err: any) => {
      ElNotification({
        type: "error",
        message: err.response ? err.response.data.status.message : "Ha ocurrido un error",
      });
    })
    .finally(() => {
      isLoading.value = false;
    });
}

function resetForm(shouldClose = false) {
  formData.reset();
  if (shouldClose) {
    emitChange(false);
  }
}

function emitChange(value: boolean) {
  emit("update:modelValue", value);
}

const modalTitle = computed(() => {
  return props.invoiceData?.id ? "Edit invoice" : "Create invoice";
});

const { isMobile } = useResponsive();
</script>

<template>
  <ElDialog
    :model-value="modelValue"
    class="overflow-hidden"
    :fullscreen="isMobile"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template #header>
      <header
        class="border-b -mx-6 -mt-6 -mr-10 bg-secondary/80 text-white py-4 px-4 flex items-center justify-between"
      >
        <h4 class="font-bold text-xl">{{ $t(modalTitle) }}</h4>
        <button class="hover:text-danger" @click="close()">
          <IMdiClose />
        </button>
      </header>
    </template>
    <div class="-mt-10">
      <section class="flex space-x-4">
        <AtField class="w-full text-left" label="Concepto">
          <AtInput
            type="text"
            class="form-control"
            name="invoice-description"
            id="invoice-description"
            v-model="formData.concept"
            rounded
          />
        </AtField>
      </section>

      <section>
        <section class="flex space-x-4">
          <AppFormField label="Fecha limite" class="w-6/12">
            <ElDatePicker
              v-model="formData.due_date"
              size="large"
              class="w-full"
              rounded
            />
          </AppFormField>

          <AppFormField class="w-6/12 text-left" label="Monto Recibido">
            <AtInput
              class="form-control"
              number-format
              v-model="formData.total"
              rounded
              required
            >
            </AtInput>
          </AppFormField>

          <AppFormField
            label="Metodo de pago"
            class="w-full ml-4"
            v-if="formData.is_paid"
          >
            <AtSimpleSelect
              v-model="formData.payment_method_id"
              v-model:selected="formData.paymentMethod"
              :options="paymentMethods"
              placeholder="Seleccione metodo de pago"
              class="w-full"
              label="name"
              key-track="id"
            />
          </AppFormField>
        </section>
      </section>

      <AppFormField class="w-full text-left" :label="$t('Description')">
        <textarea
          class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:border-gray-400"
          v-model="formData.description"
          cols="3"
          rows="3"
        />
      </AppFormField>
    </div>

    <template #footer>
      <div class="space-x-2 flex justify-end">
        <AtButton
          :disabled="isLoading"
          @click="emitChange(false)"
          class="bg-white border rounded-md text-gray"
        >
          Cancel
        </AtButton>
        <AppButton
          :processing="isLoading"
          :disabled="isLoading || !formData.total"
          :title="!formData.total && 'Debe ingresar un monto mayor a 0 para guardar'"
          variant="secondary"
          @click="onSubmit()"
        >
          Guardar
        </AppButton>
      </div>
    </template>
  </ElDialog>
</template>
