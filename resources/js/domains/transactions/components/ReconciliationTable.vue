<script setup lang="ts">
import { reactive, ref } from "vue";
import { NDropdown } from "naive-ui";

import CustomTable from "@/Components/shared/BaseTable.vue";

import { tableCols } from "@/domains/transactions";
import formatMoney from "@/utils/formatMoney";
import { ITransaction, TransactionConfig } from "@/domains/transactions/models";

withDefaults(defineProps<{
    transactions: ITransaction[],
    serverSearchOptions: Record<string, any>,
    title?: string,
    cols?: Record<string, any>[],
    isLoading: boolean;
}>(), {
    cols: () => tableCols
});

const emit = defineEmits(["removed", "edit", "approved"]);

const isTransferModalOpen = ref(false);

const transferConfig = reactive<TransactionConfig>({
  recurrence: false,
  automatic: false,
  transactionData: null,
});

const handleEdit = (transaction: ITransaction) => {
  transferConfig.transactionData = transaction;
  isTransferModalOpen.value = true;
};

const options = (row: Record<string, any>) => {
  const defaultOptions = [
    {
      name: "edit",
      label: "Edit",
    },
    {
      name: "removed",
      label: "Remove",
    },
    {
      name: "findLinked",
      label: "Find Linked",
      hide: row.status != "draft",
    },
  ];

  return defaultOptions.filter((option) => !option.hide);
};

type ItemAction = "edit" | "approved" | "removed"
const handleOptions = (option: ItemAction, transaction: ITransaction) => {
  emit(option, transaction);
};

const getTransactionColor = (row: ITransaction) => {
// @ts-ignore
  if (row.payee?.name || row.payee_name) {
    return row.direction == "WITHDRAW" ? "text-red-400" : "text-green-500";
  }
  return "text-body-1";
};
</script>

<template>
  <div class="pb-20 mt-5 bg-base-lvl-3">
    <CustomTable
      :cols="cols"
      :show-prepend="true"
      :table-data="transactions"
      :is-loading="isLoading"
      :selectable="true"
      @edit="handleEdit"
      :height="580"
    >
      <template v-slot:total="{ scope: { row }, col }">
        <div class="font-bold" :class="[getTransactionColor(row), col?.class]">
          {{ formatMoney(row.total, row.currency_code) }}
        </div>
      </template>

      <template v-slot:description="{ scope: { row } }">
        <span class="text-xs capitalize">
          {{ row.description }}
          {{ row.linked }}
        </span>
      </template>

      <template v-slot:actions="{ scope: { row } }">
        <div class="flex justify-end w-full text-right">
            <button
                class="flex items-center justify-center border-2 rounded-full w-7 h-7 "
                :class="[row.is_matched ? 'border-primary bg-secondary text-white' : 'border-body-1 text-body-1' ]"
                @click="$emit('toggleCheck', row)"

            >
                <IMdiCheck />
            </button>
          <NDropdown
            trigger="click"
            key-field="name"
            :options="options(row)"
            :on-select="(optionName) => handleOptions(optionName, row)"
            @click.stop
          >
            <button class="px-2 hover:bg-base-lvl-3 ">
                <IIonEllipsisVertical />
            </button>
          </NDropdown>
        </div>
      </template>
    </CustomTable>
  </div>
</template>

