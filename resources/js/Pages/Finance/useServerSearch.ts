import { getDateFromIso, updateSearch } from "@/utils";
import { startOfDay } from "date-fns";
import { computed, reactive, Ref, watch }  from "vue"

interface IServerSearchOptions {
    startDate: Date,
    endDate: Date,
    page?: Number,
}
export const useServerSearch = (serverSearchOptions: Ref<IServerSearchOptions>) => {
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
        date: startOfDay(serverSearchOptions.value?.startDate || new Date()),
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

    Object.entries(serverSearchOptions.value).forEach(([key, value]) => {
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

    return state
}
