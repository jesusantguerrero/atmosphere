import { getDateFromIso, updateSearch } from "@/utils";
import { startOfDay } from "date-fns";
import { computed, reactive, Ref, watch }  from "vue"

interface IServerSearchData {
    startDate: Date,
    endDate: Date,
    page?: Number,
    parent_id: Number,
}

interface IServerSearchOptions {
    manual?: Boolean,
}

export const useServerSearch = (serverSearchData: Ref<IServerSearchData>, options: IServerSearchOptions = {}) => {
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
            parent_id: "",
            date: {
                startDate: null,
                endDate: null,
            },
        },
        parent_id: serverSearchData.value.parent_id,
        date: startOfDay(serverSearchData.value?.startDate || new Date()),
        dateSpan: null,
        listType: "table",
    });

    const isSelectedList = (listTypeName) => {
      return state.listType == listTypeName;
    };

    Object.entries(serverSearchData.value).forEach(([key, value]) => {
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

    if (!options.manual) {
        watch(
            () => state.searchOptions,
            () => {
            updateSearch(state.searchOptions, state.dateSpan);
            },
            { deep: true }
        );
    }

    const executeSearch = () => {
        updateSearch(state.searchOptions, state.dateSpan);
    }

    return {
        state,
        executeSearch
    }
}
