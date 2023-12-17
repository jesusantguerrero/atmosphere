<template>
  <AppLayout>
    <div class="w-full py-10 mx-auto sm:px-6 lg:px-8">
      <div
        class="flex items-center justify-between py-2 mb-10 border-4 border-white rounded-md bg-gray-50"
      >
        <div class="px-5 font-bold text-gray-600">Transactions</div>

        <div
          class="flex space-x-2 overflow-hidden font-bold text-gray-500 rounded-t-lg max-w-min"
        >
          <AppButton @click="toggleShowAdd(true, true)" class="w-32">
            Add Income
          </AppButton>
          <AppButton @click="toggleShowAdd(false, true)" class="w-36">
            Add Expense
          </AppButton>
        </div>
      </div>

      <div class="w-full rounded-md bg-base-lvl-3">
        <AtTable
          :cols="cols"
          :tableData="transactions.data"
          :show-prepend="true"
          class="text-gray-500"
        >
          <!-- Quick Add -->
          <template #prepend>
            <div class="grid grid-cols-6 px-2 py-2 bg-white" v-if="showAdd">
              <div class="w-full px-2 overflow-hidden">
                <input
                  type="date"
                  class="w-full border-gray-200 rounded-md"
                  v-model="formData.date"
                />
              </div>
              <div class="w-full px-2 overflow-hidden">
                <input
                  type="text"
                  class="w-full border-gray-200 rounded-md"
                  v-model="formData.description"
                />
              </div>
              <div class="w-full px-1">
                <jet-select
                  v-model="formData.account_id"
                  v-model:selected="formData.account"
                  :options="categories"
                  placeholder="Pick an account"
                  class="w-full"
                  group-label="name"
                  group-items="accounts"
                  label="name"
                  key-track="id"
                >
                </jet-select>
              </div>
              <div class="w-full px-1">
                <jet-select
                  v-model="formData.category_id"
                  v-model:selected="formData.category"
                  :options="categories"
                  placeholder="Pick a category"
                  class="w-full"
                  group-label="name"
                  group-items="accounts"
                  label="name"
                  key-track="id"
                >
                </jet-select>
              </div>
              <div class="w-full">
                <input
                  type="number"
                  class="w-full border-gray-200 rounded-md"
                  v-model="formData.total"
                />
              </div>
              <div class="flex w-full px-5 space-x-2 text-center">
                <at-button
                  class="w-full text-white bg-gray-400"
                  @click="toggleShowAdd(false, false)"
                  title="cancel"
                >
                  <i class="fa fa-trash"></i>
                </at-button>
                <at-button
                  class="w-full text-white bg-green-400"
                  @click="saveQuickTransaction()"
                  title="save transaction"
                >
                  <i class="fa fa-save"></i>
                </at-button>
              </div>
            </div>
          </template>
          <!-- /Quick Add -->

          <!-- Table Data -->
          <template v-slot:name="{ scope }">
            <div>
              <div class="font-bold">{{ scope.row.name }}</div>
              <div class="italic font-normal" v-if="scope.row.last_transaction_date">
                Last transaction on: {{ scope.row.last_transaction_date.date }}
              </div>
            </div>
          </template>

          <template v-slot:description="{ scope: { row } }">
            <span
              class="font-bold text-blue-400 border-b border-blue-400 border-dashed cursor-pointer"
            >
              {{ row.description }} #{{ row.number }}
            </span>
          </template>

          <template v-slot:account="{ scope: { row } }">
            <div class="font-bold">{{ row }}</div>
          </template>

          <template v-slot:category="{ scope: { row } }">
            <div>{{ row.category ? row.category.name : "" }}</div>
          </template>

          <template v-slot:total="{ scope: { row } }">
            <div
              class="font-bold"
              :class="{
                'text-red-500': row.direction == 'WITHDRAW',
                'text-green-500': row.direction == 'DEPOSIT',
              }"
            >
              {{ formatMoney(row.total) }}
            </div>
          </template>

          <template v-slot:actions="{ scope: { row } }">
            <div class="space-x-2 w-14">
              <button><i class="fa fa-edit"></i></button>
              <button>
                <i class="fa fa-trash" @click="removeTransaction(row.id)"></i>
              </button>
            </div>
          </template>
          <!-- / Table data-->
        </AtTable>
      </div>
    </div>
  </AppLayout>
</template>

<script lang="ts" setup>
import { formatMoney } from "@/utils";
import { format } from "date-fns";
import cols from "./cols";
import { ElNotification } from "element-plus";

import AppLayout from "@/Components/templates/AppLayout.vue";
import AppButton from "@/Components/shared/AppButton.vue";
import AtTable from "@/Components/shared/BaseTable.vue";

const props = defineProps({
  transactions: {
    type: Array,
  },
  categories: {
    type: Array,
  },
});

const isAccountDialogVisible = false;
const selectedAccount = null;
const showAdd = false;
const isIncome = false;
const isLoading = false;
const formData = {};
const searchText = "";
const activeName = [];
const activeAccountSection = "";
const accountsCategories = [];
const section = "accounts";

function toggleShowAdd(isIncome = true, show = null) {
  this.showAdd = show == null ? !this.showAdd : show;
  this.formData.date = format(new Date(), "yyyy-MM-dd");
  this.isIncome = isIncome;
}

function rowClick(command, service) {
  switch (command) {
    case "edit":
      this.editAccount(service);
      break;
    default:
      break;
  }
}

function setRequestData(data) {
  const requestData = {
    ...data,
  };
  requestData.direction = this.isIncome ? "DEPOSIT" : "WITHDRAW";
  requestData.resource_type_id = "MANUAL";
  return requestData;
}

function saveQuickTransaction() {
  if (this.isLoading) {
    return;
  }

  const formData = this.setRequestData(this.formData);
  this.isLoading = true;
  if (!formData.description || !formData.account_id || !formData.category_id) {
    ElNotification({
      message: "All fields are required",
      title: "Fill the fields",
      type: "info",
    });
    this.isLoading = false;
    return;
  }
  axios.post("/transactions", formData).then(() => {
    this.toggleShowAdd(false, false);
    this.isLoading = false;
    this.$inertia.reload();
  });
}

function removeTransaction(id) {
  if (this.isLoading) {
    return;
  }
  this.isLoading = true;
  this.$inertia.delete(`/transactions/${id}`, {
    onSuccess() {
      this.$inertia.reload();
    },
  });
}
</script>

<style lang="scss" scoped>
.body-section {
  background: white;
  padding: 15px;
}

.el-table th {
  font-weight: bolder;
  color: #222 !important;
}

.section-actions {
  display: flex;

  .app-search__container {
    width: 80%;
    margin-right: 15px;
  }

  .action-buttons {
    width: 20%;
    display: flex;

    button {
      margin-left: auto;
    }
  }
}
</style>
