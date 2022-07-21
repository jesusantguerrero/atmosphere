<template>
<AppLayout @back="$inertia.visit(route('finance'))" :show-back-button="true">
    <template #header>
        <FinanceSectionNav>
            <template #actions>
                <div class="flex items-center w-full space-x-2">
                    <AtDatePager
                        class="h-12 w-full bg-base-600 text-gray-200 border-none"
                        v-model="state.date"
                        v-model:dateSpan="state.dateSpan"
                        v-model:startDate="state.searchOptions.date.startDate"
                        v-model:endDate="state.searchOptions.date.endDate"
                        controlsClass="bg-transparent text-gray-200 hover:bg-base-600"
                        next-mode="month"
                    />
                    <NSelect
                    filterable
                    clearable
                    size="large"
                    placeholder="Group by"
                    :options="state.filterOptions"
                    v-model:value="state.searchOptions.group"
                    />
                    <div
                    class="flex text-white rounded-md bg-primary-400 h-10 min-w-max divide-x-2 divide-white overflow-hidden"
                    >
                    <button
                        class="px-5"
                        :class="{ 'bg-primary-700': isSelectedList('table') }"
                        @click="state.listType = 'table'"
                    >
                        <svg
                        xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink"
                        aria-hidden="true"
                        role="img"
                        class="iconify iconify--ic"
                        width="32"
                        height="32"
                        preserveAspectRatio="xMidYMid meet"
                        viewBox="0 0 24 24"
                        >
                        <path
                            fill="currentColor"
                            d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"
                        ></path>
                        </svg>
                    </button>
                    <button
                        class="px-5"
                        :class="{ 'bg-primary-700': isSelectedList('graph') }"
                        @click="state.listType = 'graph'"
                    >
                        <svg
                        xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink"
                        aria-hidden="true"
                        role="img"
                        class="iconify iconify--uim"
                        width="32"
                        height="32"
                        preserveAspectRatio="xMidYMid meet"
                        viewBox="0 0 24 24"
                        >
                        <path
                            fill="currentColor"
                            d="M6 23H2a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1Z"
                            opacity=".25"
                        ></path>
                        <path
                            fill="currentColor"
                            d="M14 23h-4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v20a1 1 0 0 1-1 1Z"
                        ></path>
                        <path
                            fill="currentColor"
                            d="M22 23h-4a1 1 0 0 1-1-1V10a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1Z"
                            opacity=".5"
                        ></path>
                        </svg>
                    </button>
                    </div>
                </div>
            </template>
        </FinanceSectionNav>
    </template>
    <FinanceTemplate title="Transactions" :accounts="accounts">
        <Component :is="listComponent"
            :transactions="transactions"
            :server-search-options="serverSearchOptions"
            :with-teleport="true"
        />
    </FinanceTemplate>
</AppLayout>
</template>

<script setup>
    import { NSelect } from "naive-ui";
    import { AtDatePager } from "atmosphere-ui";
    import AppLayout from '@/Layouts/AppLayout.vue'
    import { computed, reactive, watch } from 'vue';
    import TransactionSearch from '@/Components/templates/TransactionSearch.vue';
    import FinanceTemplate from '@/Components/templates/FinanceTemplate.vue';
    import TransactionTemplate from '@/Components/templates/TransactionTemplate.vue';
    import FinanceSectionNav from '@/Components/templates/FinanceSectionNav.vue';
    import { updateSearch, getDateFromIso } from "@/utils";
    import { startOfDay } from "date-fns";

    const props = defineProps({
        transactions: {
            type: Array,
            default: () => []
        },
        accounts: {
            type: Array,
            default: () => []
        },
        serverSearchOptions: {
            type: Object,
            default: () => ({})
        },
        accountId: {
            type: [Number, null],
        }
    })

    const state = reactive({
        filterOptions: [
            {
            label: "Accounts",
            value: "accounts",
            },
            {
            label: "Descriptions",
            value: "description",
            },
        ],
        searchOptions: {
            group: "",
            date: {
            startDate: null,
            endDate: null,
            },
        },
        date: startOfDay(props.serverSearchOptions?.date?.startDate || new Date()),
        dateSpan: null,
        listType: "table",
    });

const isSelectedList = (listTypeName) => {
  return state.listType == listTypeName;
};

watch(
  () => state.searchOptions,
  () => {
    updateSearch(state.searchOptions, state.dateSpan);
  },
  { deep: true }
);

Object.entries(props.serverSearchOptions).forEach(([key, value]) => {
  if (key === "date") {
    state.searchOptions.date.startDate = startOfDay(getDateFromIso(value.startDate));
    state.searchOptions.date.endDate = startOfDay(getDateFromIso(value.endDate));
    state.date = startOfDay(getDateFromIso(value.startDate));
  } else {
    state.searchOptions[key] = Object.values(state.filterOptions)
      .map((filter) => filter.value)
      .includes(value)
      ? value
      : "";
  }
});

    const listComponent = computed(() => {
        return props.accountId ? TransactionTemplate : TransactionSearch
    })
</script>
