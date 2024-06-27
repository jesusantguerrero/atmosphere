<script lang="ts" setup>
import axios from "axios";
import { ref, watchEffect } from "vue";

import { formatMoney } from "@/utils";
import BaseSelect from "./BaseSelect.vue";

const { accountId, categoryName, modelValue } = defineProps<{
  modelValue: string;
  accountId: number;
  categoryName: string;
}>();

const emit = defineEmits(["update:modelValue"]);

function getDepositBalance(accountId: number, categoryName: string) {
  return axios
    .get(`/api/accounts/${accountId}/unlinked-payments`)
    .then(({ data }) => {
      return data;
    });
}

const options = ref();
watchEffect(async () => {
  const results = await getDepositBalance(accountId, categoryName);
  options.value = results;
  emit("update:modelValue", results[0]);
});
</script>

<template>
  <BaseSelect
    :options="options"
    :modelValue="modelValue"
    @update:modelValue="$emit('update:modelValue', $event)"
    :placeholder="$t('Select a deposit')"
    label="concept"
    track-by="transaction_id"
  >
    <template v-slot:singleLabel="{ option }">
      <span class="option__title">{{ option.concept }}</span>
      <span class="option__small ml-2"
        >({{ formatMoney(Math.abs(option.amount)) }})
      </span>
    </template>
    <template v-slot:option="{ option }">
      <div class="option__desc">
        <span class="option__title">{{ option.cat_alias ?? option.cat_name }}</span>
        <span class="option__small ml-2"
          >({{ formatMoney(Math.abs(option.balance)) }})
        </span>
      </div>
    </template>
  </BaseSelect>
</template>
