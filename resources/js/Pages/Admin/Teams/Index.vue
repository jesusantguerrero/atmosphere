<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { computed, toRefs } from "vue";
import { router } from "@inertiajs/core";

// @ts-ignore: its my template
import AtTable from "@/Components/shared/BaseTable.vue";
import AppButton from "@/Components/atoms/LogerButton.vue";

import cols from "./cols";
import { IRent } from "@/Modules/property/propertyEntity";
import AppSearch from "@/Components/AppSearch/AppSearch.vue";
import { IServerSearchData, useServerSearch } from "@/composables/useServerSearch";
import AdminTemplate from "../Partials/AdminTemplate.vue";

interface IPaginatedData {
  data: IRent[];
}

const props = defineProps<{
  teams: IRent[] | IPaginatedData;
  serverSearchOptions: IServerSearchData;
  user: Record<string, string>;
}>();

const { serverSearchOptions } = toRefs(props);
const {
  executeSearch,
  changeSize,
  paginate,
  reset,
  state: searchState,
} = useServerSearch(serverSearchOptions, { manual: false });

const listData = computed(() => {
  return Array.isArray(props.teams) ? props.teams : props.teams.data;
});

const tableConfig = {
  selectable: true,
  searchBar: true,
  pagination: true,
};

const approveTeam = (team: Record<string, string>) => {
  router.post(route("admin.teams.approve", team));
};

const deleteTeam = () => {};
</script>

<template>
  <AdminTemplate title="Teams">
    <main class="pb-16 mt-24">
      <section class="flex space-x-4">
        <AppSearch
          v-model.lazy="searchState.search"
          class="w-full md:flex"
          :has-filters="true"
          @clear="reset()"
          @blur="executeSearch"
        />
        <AppButton @click="router.visit(route('rents.create'))">
          {{ $t("add team") }}
        </AppButton>
      </section>
      <AtTable
        class="mt-4 bg-white rounded-md text-body-1"
        :table-data="listData"
        :cols="cols"
        :pagination="searchState"
        :total="teams.total"
        @search="executeSearch"
        @paginate="paginate"
        @size-change="changeSize"
        :config="tableConfig"
      >
        <template v-slot:actions="{ scope: { row } }" class="flex">
          <div class="flex items-center justify-end">
            <UnitTag :status="row.status" />

            <Link
              class="relative inline-block px-5 py-2 ml-4 overflow-hidden font-bold transition rounded-md cursor-pointer hover:bg-primary hover:text-white text-body focus:outline-none hover:bg-opacity-80 min-w-max"
              :href="`/admin/teams/${row.id}`"
            >
              <IMdiChevronRight />
            </Link>
            <AppButton
              variant="success"
              @click="approveTeam(row)"
              title="Remove team"
              v-if="!row.approved_at"
            >
              <IMdiCheck />
            </AppButton>
            <AppButton
              variant="neutral"
              class="flex flex-col items-center justify-center transition hover:text-error hover:border-red-400"
              @click="deleteTeam(row)"
              title="Approve team"
            >
              <IMdiTrash />
            </AppButton>
          </div>
        </template>
      </AtTable>
    </main>
  </AdminTemplate>
</template>
