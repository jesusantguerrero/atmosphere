<script setup lang="ts">
import { AtButton, AtField, AtInput, AtSimpleSelect } from "atmosphere-ui";
import { format as formatDate } from "date-fns";
import { ElDatePicker, ElDialog, ElNotification } from "element-plus";
import { ref, watch, computed } from "vue";

import AppButton from "@/Components/shared/AppButton.vue";
import BaseSelect from "@/Components/shared/BaseSelect.vue";

import { MathHelper } from "@/Modules/loans/mathHelper";
import { paymentMethods } from "@/Modules/loans/constants";
import AppFormField from "@/Components/shared/AppFormField.vue";
import { IClient, IClientSaved } from "@/Modules/clients/clientEntity";
import { useResponsive } from "@/utils/useResponsive";
import { IProperty, IRent } from "@/Modules/properties/propertyEntity";

const defaultFormData = {
  amount: 0,
  account_id: "",
};

const props = withDefaults(
  defineProps<{
    modelValue: boolean;
    defaultConcept: string;
    defaultAmount: number;
    payment?: Record<string, any>;
    due: number;
    endpoint?: string;
    title: string;
    hideClientOptions?: boolean;
    clientId: string;
    rentId: string;
    propertyId: string;
    type: string;
    property?: IProperty;
    rent?: IRent;
  }>(),
  {
    type: "expense",
    title: "Crear factura",
    due: 0,
  }
);

const emit = defineEmits(["update:modelValue", "saved"]);

const formData = ref(generatePaymentData());

const setFormData = () => {
  formData.value = generatePaymentData();
};

function generatePaymentData() {
  return {
    ...defaultFormData,
    concept: props.defaultConcept,
    amount: props.due ?? 0,
    payment_method_id: paymentMethods[0].id,
    paymentMethod: paymentMethods[0],
    date: new Date(),
    is_paid_expense: false,
  };
}

watch(
  () => props.defaultConcept,
  (defaultConcept) => {
    formData.value.concept = defaultConcept;
  },
  {
    immediate: true,
  }
);

watch(
  () => props.due,
  (due) => {
    if (!formData.value.id) {
      formData.value.amount = due;
    }
  },
  {
    immediate: true,
  }
);

watch(
  props.payment,
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

const rentsUrl = computed(() => {
  return `/api/rents?filter[property_id]=${formData.value.property?.id}`;
});

const setRent = (rent: IRent) => {
  axios.get(rentsUrl).then(({ data }) => {
    if (data.length) {
      formData.value.rent = data.at(-1);
    } else {
      formData.value.rent = null;
    }
  });
};

const isLoading = ref(false);
const endpoints: Record<string, string> = {
  expense: "/properties/:property_id/transactions/expense",
  fee: "/rents/:rents_id/transactions/fee",
};
function onSubmit() {
  if (isLoading.value) return;
  if (!formData.value.amount) {
    ElNotification({
      type: "error",
      message: "should specify an amount",
    });
    return;
  }

  const data = {
    date: formatDate(formData.value.date || new Date(), "yyyy-MM-dd"),
    amount: formData.value.amount,
    concept: formData.value.concept,
    account_id: formData.value.account_id,
    client_id: formData.value.client?.id ?? props.clientId,
    property_id: formData.value.property?.id ?? props.propertyId,
    rent_id: formData.value.rent?.id ?? props.rentId,
    details: formData.value.notes,
    is_paid_expense: formData.value.is_paid_expense,
  };

  isLoading.value = true;
  const endpoint = endpoints[props.type] ?? endpoints.expense;
  const url = endpoint
    .replace(":property_id", data.property_id)
    .replace(":rents_id", data.rent_id);


  axios
    .post(url, data)
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

function resetForm(shouldClose) {
  formData.value = {
    ...defaultFormData,
    concept: props.defaultConcept,
  };
  if (shouldClose) {
    emitChange(false);
  }
}

function emitChange(value) {
  emit("update:modelValue", value);
}

const { isMobile } = useResponsive();

const propertyLabel = (property: IProperty) => {
  return `${property.name} [${property?.units?.length}] (${property.address}) `;
};
</script>

<template>
  <ElDialog
    @open="setFormData()"
    :model-value="modelValue"
    :fullscreen="isMobile"
    class="overflow-hidden"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template #header>
      <header
        class="border-b -mx-6 -mt-6 -mr-10 bg-secondary/80 text-white py-4 px-4 flex items-center justify-between"
      >
        <h4 class="font-bold text-xl">{{ title ?? defaultConcept }}</h4>
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
            <ElDatePicker v-model="formData.date" size="large" class="w-full" rounded />
          </AppFormField>

          <AppFormField class="w-6/12 text-left" label="Monto">

            <AtInput
              class="form-control"
              number-format
              v-model="formData.amount"
              rounded
              required
            >
              <template #suffix>
                <button
                  class="w-32 px-2 transition"
                  :class="[
                    formData.is_paid_expense
                      ? 'bg-success text-white font-bold'
                      : 'bg-base-lvl-2 text-body-1',
                  ]"
                  @click.stop="formData.is_paid_expense = !formData.is_paid_expense"
                >
                  {{ formData.is_paid_expense ? "Pagado" : "No Pagado" }}
                </button>
              </template>
            </AtInput>
          </AppFormField>

          <AppFormField
            label="Metodo de pago"
            class="w-full ml-4"
            v-if="formData.is_paid_expense"
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

      <section class="mt-4 flex space-x-4" v-if="!hideClientOptions">
        <AppFormField :label="$t('property')">
          <BaseSelect
            v-model="formData.property"
            endpoint="/api/properties"
            placeholder="Selecciona la propiedad"
            label="name"
            track-by="id"
            :custom-label="propertyLabel"
          />
        </AppFormField>
        <AppFormField label="Inquilino">
          <BaseSelect
            v-model="formData.rent"
            :endpoint="rentsUrl"
            placeholder="Selecciona el contrato"
            label="client_name"
            track-by="id"
            @update:model-value="setRent"
          />
        </AppFormField>
      </section>

      <AppFormField class="w-full text-left" label="Notes">
        <textarea
          class="w-full px-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:border-gray-400"
          v-model="formData.notes"
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
          :disabled="isLoading || !formData.amount"
          :title="!formData.amount && 'Debe ingresar un monto mayor a 0 para guardar'"
          variant="secondary"
          @click="onSubmit()"
        >
          Guardar
        </AppButton>
      </div>
    </template>
  </ElDialog>
</template>
