<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/core";

// @ts-ignore: its my template
import AtTable from "@/Components/shared/BaseTable.vue";
import AppButton from "@/Components/shared/AppButton.vue";

import AppSearch from "@/Components/shared/AppSearch/AppSearch.vue";
import AdminTemplate from "./Partials/AdminTemplate.vue";

defineProps<{
  data: Record<string, string>[];
  user: Record<string, string>;
}>();

const tableConfig = {
  selectable: true,
  searchBar: true,
  pagination: true,
};

const searchText = ref();
const reset = () => {};
const executeSearch = () => {};
</script>

<template>
  <AdminTemplate title="Commands">
    <main class="pb-16">
      <section class="flex space-x-4">
        <AppSearch
          v-model.lazy="searchText"
          class="w-full md:flex"
          :has-filters="true"
          @clear="reset()"
          @blur="executeSearch"
        />
      </section>
      <AtTable
        class="bg-white rounded-md text-body-1 mt-4"
        :table-data="data"
        :cols="[
          {
            label: 'Command',
            name: 'label',
          },
          {
            name: 'actions',
            label: 'Action',
          },
        ]"
        @search="() => {}"
        :config="tableConfig"
      >
        <template v-slot:actions="{ scope: { row } }" class="flex">
          <div class="flex justify-end items-center">
            <AppButton
              class="hover:text-primary transition items-center flex justify-center hover:border-primary-400"
              variant="neutral"
              @click="
                router.post(`/admin/commands`, {
                  command: row.command,
                })
              "
            >
              <IMdiPlay /> run
            </AppButton>
          </div>
        </template>
      </AtTable>
    </main>
  </AdminTemplate>
</template>
