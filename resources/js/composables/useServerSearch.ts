import { router } from "@inertiajs/vue3";
import { format, parseISO } from "date-fns";
import { debounce } from "lodash";
import { nextTick, reactive, ref, Ref, watch }  from "vue"
interface IServerSearchData {
    filters: Record<string, string>
    dates: IDateSpan
    limit?: number
    relationships: string
    search?: string
    sorts?: string
    page?: number
}

interface IServerSearchOptions {
    manual?: Boolean,
    mainDateField?: string,
}

interface IDateSpan {
    startDate: Date,
    endDate?: Date
}

interface ISearchState {
    filters: Record<string, string>,
    dates: IDateSpan,
    sorts: string,
    limit: number,
    relationships: string,
    search?: string
    page?: number
}

export const filterParams = (mainDateField, externalFilters: Record<string, string>, dates: IDateSpan) => {
    let filters = [];
    if (externalFilters) {
        Object.entries(externalFilters).forEach(
            ([name, value]) => {
                if (value) {
                    filters.push(`filter[${name}]=${value}`);
                }
            }
        );
    }

    if (dates.startDate) {
      let dateFilterValue = format(dates.startDate, 'yyyy-MM-dd');
      if (dates.endDate) {
        dateFilterValue += `~${format(dates.endDate, 'yyyy-MM-dd')}`;
      }
      filters.push(`filter[${mainDateField}]=${dateFilterValue}`);
    }

    return filters.join("&");
}

export const getGroupParams = (groupValue:string) => {
    return `group=${groupValue}`;
}

export const getRelationshipsParams = (relationships: string) => {
    return relationships && `relationships=${relationships}`
}

export const updateSearch = (state: ISearchState) => {
    let params = [
        filterParams('date', state.filters, state.dates),
        getRelationshipsParams(state.relationships)
    ];

    const urlParams = params.filter(value => value?.trim()).join("&");
    const finalUrl = `${window.location.pathname}?${urlParams}`
    if (finalUrl != window.location.toString()) {
        router.visit(finalUrl, {
            preserveState: true,
        })
    }
}

export const parseDateFilters = (options: IServerSearchData) => {
    const dates = options?.filters?.date ? options.filters.date.split('~') : [null, null]

    return {
        startDate: parseISO(dates[0]),
        endDate: dates.length == 2 ? parseISO(dates[1]) : null
    }
}

const serverToState = (serverSearchData) => {
    const dates = parseDateFilters(serverSearchData)
    return {
        filters: {
            ...(serverSearchData?.filters ?? {}),
            date: null
        },
        dates: {
            startDate: dates.startDate,
            endDate: dates.endDate,
        },
        sorts: serverSearchData?.sorts,
        limit: serverSearchData?.limit,
        relationships: serverSearchData?.relationships,
        search: serverSearchData?.search,
        page: serverSearchData?.page
    }
}

export const useServerSearch = (serverSearchData: Ref<IServerSearchData> , options: IServerSearchOptions = {}) => {
    const state = reactive<ISearchState>(serverToState(serverSearchData.value));
    const lastState = ref(serverToState(serverSearchData.value));

    if (!options.manual) {
        watch(
            () => state,
            debounce((newState) => {
                if (JSON.stringify(newState) !== JSON.stringify(lastState.value)) {
                    lastState.value = serverToState(serverSearchData.value);
                    updateSearch(state);
                }
            }, 200),
            { deep: true}
        );
    }

    const executeSearch = (withDelay = false) => {
        if (!withDelay) {
            updateSearch(state);
        } else {
            nextTick(debounce(() => {
                executeSearch()
            }, 200))
        }
    }

    return {
        state,
        executeSearch,
        executeSearchWithDelay: executeSearch.bind(null, true)
    }
}
