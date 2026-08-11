<script setup lang="ts">
import { reactive, ref } from "vue";
import { NDropdown } from "naive-ui";
import { useI18n } from "vue-i18n";

import CustomTable from "@/Components/atoms/CustomTable.vue";

import { tableCols } from "@/domains/transactions";
import formatMoney from "@/utils/formatMoney";
import { ITransaction, TransactionConfig } from "@/domains/transactions/models";

const { t } = useI18n();

withDefaults(defineProps<{
    transactions: ITransaction[],
    serverSearchOptions: Record<string, any>,
    title?: string,
    cols?: Record<string, any>[],
    isLoading: boolean;
    emptyText?: string;
    rowClass?: (row: any, index: number) => string;
}>(), {
    cols: () => tableCols,
    emptyText: 'No data found',
});

const emit = defineEmits(["removed", "edit", "approved", "duplicate", "sort"]);

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
      name: "approved",
      label: t("Approve"),
      hide: row.status !== "draft",
    },
    {
      name: "edit",
      label: t("Edit"),
    },
    {
      name: "duplicate",
      label: t("Duplicate"),
    },
    {
      name: "removed",
      label: t("Remove"),
    },
    {
      name: "findLinked",
      label: t("Find Linked"),
      hide: row.status !== "draft",
    },
  ];

  return defaultOptions.filter((option) => !option.hide);
};

type ItemAction = "edit" | "approved" | "removed"
const handleOptions = (option: ItemAction, transaction: ITransaction) => {
  emit(option, transaction);
};

// Explicit +/- so direction reads without relying on color alone (also helps
// color-blind users). Transfers stay unsigned: the sign depends on which side of
// the transfer you're looking at, which getTransactionColor already handles.
const amountSign = (row: ITransaction) => {
  if (row.is_transfer || row.counter_account_id) {
    return '';
  }
  return row.direction === 'WITHDRAW' ? '−' : '+';
};

const moneyParts = (value: any, currency?: string) => {
  const s = formatMoney(value, currency);
  const i = s.lastIndexOf('.');
  return i === -1 ? { main: s, cents: '' } : { main: s.slice(0, i), cents: s.slice(i + 1) };
};

const getTransactionColor = (row: ITransaction) => {
// @ts-ignore
  if (row.payee?.name || row.payee_name) {
    return row.direction == "WITHDRAW" ? "text-body" : "text-green-500";
  }
  // Transfers: if this account is the source (account_id), money is leaving (red)
  // If this account is the destination (counter_account_id), money is coming in (green)
  if (row.is_transfer || row.counter_account_id) {
    const viewingAccountId = (row as any)._viewingAccountId;
    if (viewingAccountId) {
      return row.account_id === viewingAccountId ? "text-body" : "text-green-500";
    }
    return row.direction == "WITHDRAW" ? "text-body" : "text-green-500";
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
      :empty-text="emptyText"
      :row-class="rowClass"
      @edit="handleEdit"
      @sort="emit('sort', $event)"
    >
      <template v-if="$slots.empty" #empty>
        <slot name="empty" />
      </template>

      <template v-slot:total="{ scope: { row } }">
        <div class="flex items-center justify-end gap-2">
          <button
            v-if="row._isDraft"
            @click.stop="emit('approved', row)"
            class="px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 rounded-full hover:bg-green-100 hover:text-green-700 transition-colors"
            :title="$t('Click to approve')"
          >
            {{ $t('Approve') }}
          </button>
          <div class="font-bold tabular-nums" :class="[getTransactionColor(row)]">
            <span v-if="amountSign(row)" class="mr-0.5">{{ amountSign(row) }}</span>{{ moneyParts(row.total, row.currency_code).main }}<span v-if="moneyParts(row.total, row.currency_code).cents" class="text-[0.72em] align-top opacity-60">.{{ moneyParts(row.total, row.currency_code).cents }}</span>
          </div>
        </div>
      </template>

      <!-- <template v-slot:description="{ scope: { row } }">
        <div class="text-xs capitalize">
          <p>
              {{ row.description }}
          </p>
          <p class="text-primary">
              {{ row.category?.name ?? row.category_name }}
          </p>
        </div>
      </template> -->

      <template v-slot:status="{ scope: { row } }">
        <span v-if="row.is_reconciled" class="text-green-600" title="Conciliado"><IMdiCheckCircle /></span>
        <span v-else class="text-body-1/25" title="Sin conciliar">—</span>
      </template>

      <template v-slot:actions="{ scope: { row } }">
        <div>
          <span><IMdiLink v-if="row.linked_transaction_id" /></span>
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

