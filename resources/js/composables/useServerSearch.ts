import { Inertia } from "@inertiajs/inertia";
import { format, parseISO } from "date-fns";
import { reactive, Ref, watch }  from "vue"

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
        Inertia.visit(finalUrl, {
            preserveState: true,
        })
    }
}

export const useServerSearch = (serverSearchData: Ref<IServerSearchData>, options: IServerSearchOptions = {}) => {
    const dates = parseDateFilters(serverSearchData)
    const state = reactive<ISearchState>({
        filters: {
            ...serverSearchData.value.filters,
            date: null
        },
        dates: {
            startDate: dates.startDate,
            endDate: dates.endDate,
        },
        sorts: serverSearchData.value.sorts,
        limit: serverSearchData.value.limit,
        relationships: serverSearchData.value.relationships,
        search: serverSearchData.value.search,
        page: serverSearchData.value.page
    });

    if (!options.manual) {
        watch(
            () => state,
            () => {
                updateSearch(state);
            },
            { deep: true }
        );
    }

    const executeSearch = () => {
        updateSearch(state);
    }

    function parseDateFilters(options: Ref<IServerSearchData>) {
        const dates = options?.value.filters?.date ? options.value.filters.date.split('~') : [null, null]

        return {
            startDate: parseISO(dates[0]),
            endDate: dates.length == 2 ? parseISO(dates[1]) : null
        }
    }

    return {
        state,
        executeSearch
    }
}
