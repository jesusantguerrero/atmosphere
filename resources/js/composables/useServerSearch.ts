import { router } from '@inertiajs/vue3';
import { format, parseISO } from "date-fns";
import { reactive, Ref, watch, nextTick, computed }  from "vue"
import { debounce } from 'lodash';

export interface IServerSearchData {
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
  filters: Record<string, string|null>,
  dates: IDateSpan,
  sorts: string,
  limit: number,
  relationships: string,
  search?: string
  page?: number
}

const DEFAULT_CONFIG = {
  search: "",
  filters: {},
  sorts: {
    title: {
      label: "Name",
      value: "",
      direction: "",
    },
    releaseYear: {
      label: "Year",
      value: "",
      direction: "",
    },
  },
};

export const filterParams = (mainDateField: string, externalFilters: Record<string, string|null>, dates: IDateSpan) => {
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
     try {
       let dateFilterValue = format(dates.startDate, 'yyyy-MM-dd');
       if (dates.endDate) {
         dateFilterValue += `~${format(dates.endDate, 'yyyy-MM-dd')}`;
       }
       filters.push(`filter[${mainDateField}]=${dateFilterValue}`);
     } catch (e) {
      return filters.join('&');
     }
    }

  return filters.join("&");
}

export const getGroupParams = (groupValue:string) => {
    return `group=${groupValue}`;
}

export const getRelationshipsParams = (relationships: string) => {
  return relationships && `relationships=${relationships}`
}
export const getPaginationParams = (state: ISearchState) => {
  return state.page && `page=${state.page}&limit=${state.limit}`
}

export const parseParams = (state: ISearchState) => {
  let params = [
      filterParams('date', state.filters, state.dates),
      getRelationshipsParams(state.relationships),
      getPaginationParams(state)
  ];

  if (state.search) {
    params.unshift(`search=${state.search}`);
  }


  return params.filter(value => typeof value == 'string' && value?.trim() || value).join("&");
}

const getCurrentLocationParams = () => {
    return location.search.toString();
}

export const updateSearch = (newUrl: string) => {
  return router.get(newUrl, undefined, {
    preserveState: true,
  })
}

export const defaultSearchInertia = (urlParams: string) => {
    const finalUrl = `${window.location.pathname}?${urlParams}`
    if (finalUrl == window.location.toString()) return
    return updateSearch(finalUrl);
}

export const useServerSearch = (serverSearchData: Ref<Partial<IServerSearchData>>, options: IServerSearchOptions = {}, onUrlChange = defaultSearchInertia) => {
    const dates = parseDateFilters(serverSearchData)
    const state = reactive<ISearchState>({
        filters: {
            ...(serverSearchData.value ? serverSearchData.value.filters : {}),
            date: null
        },
        dates: {
            startDate: dates.startDate,
            endDate: dates.endDate,
        },
        sorts: serverSearchData.value?.sorts ?? "",
        limit: serverSearchData.value?.limit ?? 0,
        relationships: serverSearchData.value?.relationships,
        search: serverSearchData.value?.search,
        page: serverSearchData.value?.page
    });


    watch(
        () => state,
        debounce((paramsConfig) => {
          const urlParams = parseParams(paramsConfig);
          const currentUrl = getCurrentLocationParams()

          if (urlParams != currentUrl && !options.manual && onUrlChange) {
            onUrlChange(urlParams);
          }
        }, 400),
        { deep: true }
    );

    const executeSearch = (delay?: number) => {
        nextTick(() => {      
            const urlParams = parseParams(state)
            const currentUrl = getCurrentLocationParams()
            if (urlParams == currentUrl || !onUrlChange) return
    
            if (!delay) {
                onUrlChange(urlParams);
            } else {
                nextTick(debounce(() => {
                    const urlParams = parseParams(state)
                    onUrlChange(urlParams);
                }, delay))
            }
        })
    }

    function parseDateFilters(options: Ref<IServerSearchData>) {
        if (!options.value) return {}
        const dates = options?.value.filters?.date ? options.value.filters.date.split('~') : [null, null]

        return {
            startDate: dates[0] && parseISO(dates[0]),
            endDate: dates.length == 2 && dates[1] ? parseISO(dates[1]) : null
        }
    }

    const reset = () => {
      state.search = "";
      state.filters = {};
      state.sorts = "";

      executeSearch()
    };

    const paginate = (page: number) => {
      state.page = page;
      executeSearch();
    }

    const changeSize = (limit: number) => {
      state.limit = limit;
      executeSearch();
    }

    const hasFilters = computed(() => {
      return Boolean(state.search?.length)
    })

    return {
        state,
        hasFilters,
        executeSearch,
        updateSearch,
        changeSize,
        paginate,
        reset,
        executeSearchWithDelay: (delay = 200) => executeSearch(delay)
    }
}
