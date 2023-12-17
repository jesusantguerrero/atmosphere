<script setup>
import { format as formatDate } from "date-fns";
import AppLayout from "@/Components/templates/AppLayout.vue";
import IconAdd from "@/Components/icons/IconAdd.vue";
import cols from "./cols";
import AccountModal from "@/Components/shared/AccountModal.vue";
import {
  ElTabs,
  ElTabPane,
  ElDropdown,
  ElDropdownItem,
  ElDropdownMenu,
} from "element-plus";
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import { AtButton } from "atmosphere-ui";
import { onMounted, ref, computed } from "vue";

import AtTable from "@/Components/AtTable.vue";
import AppButton from "@/Components/shared/AppButton.vue";

import AccountingSectionNav from "../Partials/AccountingSectionNav.vue";

const props = defineProps({
  accounts: {
    type: Array,
  },
  categories: {
    type: Array,
  },
});

const selectedAccount = ref(null);
const searchText = ref("");
const activeName = ref([]);
const activeAccountSection = ref("");
const accountsCategories = ref([]);

onMounted(() => {
  getAccounts();
});

const formatDateFilter = (date) => {
  return formatDate(date, "YYYY-MM-DD");
};

const section = computed(() => {
  return "accounts";
});

const mainCategories = computed(() => {
  return props.categories.map((group) => {
    group.name.replace(/[d]/i);
    return group;
  });
});

const getAccounts = () => {
  accountsCategories.value = props.categories;
  activeAccountSection.value =
    mainCategories.value && mainCategories.value.length ? mainCategories.value[0].id : "";
};

const editAccount = () => {};

const rowClick = (command, service) => {
  switch (command) {
    case "edit":
      editAccount(service);
      break;
    default:
      break;
  }
};

const isAccountModalOpen = ref(false);
const accountToEdit = ref({});
const openAccountModal = (account = {}) => {
  accountToEdit.value = account;
  isAccountModalOpen.value = true;
};
</script>

<template>
  <AppLayout title="Cuentas">
    <template #header>
      <AccountingSectionNav>
        <template #actions>
          <AppButton @click="isAccountModalOpen = true" variant="inverse">
            <IconAdd />
          </AppButton>
        </template>
      </AccountingSectionNav>
    </template>

    <div class="w-full py-10 mx-auto sm:px-6 lg:px-8">
      <div class="w-full px-5 py-5 bg-white rounded-md shadow-md">
        <ElTabs v-model="activeAccountSection">
          <ElTabPane
            :label="`${category.alias || category.name} (${
              category.subcategories.length
            })`"
            :title="`${category.alias || category.name} (${category.number})`"
            :name="category.id"
            v-for="category in mainCategories"
            :key="category.id"
            class="w-full bg-white"
          >
            <!-- subCategories -->
            <template v-if="category.subcategories.length">
              <Disclosure
                v-for="subCategory in category.subcategories"
                :key="subCategory.id"
              >
                <DisclosureButton
                  class="flex justify-between w-full px-4 py-2 text-sm font-medium text-left text-blue-900 bg-blue-100 hover:bg-blue-200 focus:outline-none focus-visible:ring focus-visible:ring-blue-500 focus-visible:ring-opacity-75"
                  v-slot="{ open }"
                >
                  <span class="text-lg font-bold">
                    {{ subCategory.number }} - {{ subCategory.alias || subCategory.name }}
                  </span>

                  <i
                    class="fa fa-chevron-down"
                    :class="open ? 'transform rotate-180' : ''"
                  />
                </DisclosureButton>
                <DisclosurePanel class="font-bold text-gray-500">
                  <!-- accounts  -->
                  <AtTable
                    :cols="cols(' ')"
                    :tableData="subCategory.accounts"
                    :empty-text="'No hay cuentas en esta categoria'"
                    hide-headers
                  >
                    <template v-slot:name="{ scope: { row } }">
                      <div>
                        <div class="font-bold">{{ row.alias || row.name }}</div>
                        <div class="italic font-normal" v-if="row.last_transaction_date">
                          Last transaction on: {{ row.last_transaction_date.date }}
                        </div>
                      </div>
                    </template>
                    <template v-slot:actions="{ scope: { row } }">
                      <button @click="openAccountModal(row)">Edit</button>
                    </template>
                  </AtTable>
                  <!-- accounts  -->
                </DisclosurePanel>
              </Disclosure>
            </template>
            <!-- subCategories -->
          </ElTabPane>
        </ElTabs>
      </div>

      <AccountModal
        v-if="isAccountModalOpen"
        :show="isAccountModalOpen"
        :max-width="modalMaxWidth"
        :form-data="accountToEdit"
        @close="isAccountModalOpen = false"
      />
    </div>
  </AppLayout>
</template>

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
