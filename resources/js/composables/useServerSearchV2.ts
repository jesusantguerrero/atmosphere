import { endOfMonth, startOfMonth, format, parseISO } from 'date-fns';
// import { format, parseISO } from "date-fns";
import { reactive, Ref, watch, nextTick, computed, ref, inject } from "vue";
import debounce from "lodash/debounce";
import { config } from '@/config';
import {  useLocalStorage } from "@vueuse/core"

export enum SearchFilterMode {
  Replace = 1,
  Add = 1
}
export interface IServerSearchData {
  filters: Record<string, string>;
  custom: Record<string, string>;
  dates: IDateSpan;
  limit?: number;
  relationships: string;
  search?: string;
  sorts?: string;
  page?: number;
}

interface IServerSearchOptions {
  manual?: boolean;
  mainDateField?: string;
  defaultDates?: string;
}

interface IDateSpan {
  startDate: Date | null;
  endDate?: Date | null;
}

interface ISearchState {
  filters: Record<string, string | null>;
  custom: Record<string, string | null>;
  dates: IDateSpan;
  sorts: string;
  limit: number;
  relationships: string;
  search?: string;
  page?: number;
}

export interface InertiaRouterType {
  get: (
    url: string,
    data: Partial<Record<string, any>> | undefined,
    config: Partial<Record<string, any>>
  ) => void;
}

export type UrlChangeCallback = (
  urlParams: string,
  UpdateSearch?: (params: string) => void
) => Promise<void>;
export const filterParams = (
  mainDateField: string,
  externalFilters: Record<string, string | null>,
  dates: IDateSpan
) => {
  const filters = [];
  if (externalFilters) {
    Object.entries(externalFilters).forEach(([name, value]) => {
      if (value) {
        filters.push(`filter[${name}]=${value}`);
      }
    });
  }

  if (dates?.startDate) {
    try {
      let dateFilterValue = format(dates.startDate, "yyyy-MM-dd");
      if (dates.endDate) {
        dateFilterValue += `~${format(dates.endDate, "yyyy-MM-dd")}`;
      }
      filters.push(`filter[${mainDateField}]=${dateFilterValue}`);
    } catch (e) {
      return filters.join("&");
    }
  }

  return filters.join("&");
};
export const customFilterParams = (
  externalFilters: Record<string, string | null>,
) => {
  const customFilters: any[] = [];
  if (externalFilters) {
    Object.entries(externalFilters).forEach(([name, value]) => {
      if (value) {
        customFilters.push(`custom[${name}]=${value}`);
      }
    });
  }

  return customFilters.join("&");
};

export const getGroupParams = (groupValue: string) => {
  return `group=${groupValue}`;
};

export const getRelationshipsParams = (relationships: string) => {
  return relationships && `relationships=${relationships}`;
};
export const getPaginationParams = (state: ISearchState) => {
  return state.page && `page=${state.page}&limit=${state.limit}`;
};

export const parseParams = (state: ISearchState) => {
  const params = [
    filterParams("date", state.filters, state.dates),
    customFilterParams(state.custom),
    getRelationshipsParams(state.relationships),
    getPaginationParams(state),
  ];

  if (state.search) {
    params.unshift(`search=${state.search}`);
  }

  return params
    .filter((value) => (typeof value == "string" && value?.trim()) || value)
    .join("&");
};

function parseDateFilters(options: Ref<Partial<IServerSearchData>>, setDefaultDate: boolean) {

    const defaultDates = setDefaultDate ? [
        format(startOfMonth(new Date()), 'yyyy-MM-dd'),
        format(endOfMonth(new Date()), 'yyyy-MM-dd')
    ] : [null, null]

   const dates = options?.value?.filters?.date
    ? options.value.filters.date.split("~")
    : defaultDates;

  return {
    startDate: dates[0] && parseISO(dates[0]),
    endDate:
      dates.length == 2 && dates[1] && dates[1] !== ""
        ? parseISO(dates[1])
        : null,
  };
}

const getCurrentLocationParams = () => {
  return location.search.toString();
};

const defaultSearchInertia = async (
  router: InertiaRouterType,
  urlParams: string,
  updateSearch?: (urlParams: string) => void
) => {
  const finalUrl = `${window.location.pathname}?${urlParams}`;
  if (finalUrl == window.location.toString()) return;
  return updateSearch
    ? updateSearch(urlParams)
    : router.get(finalUrl, undefined, {
        preserveScroll: true,
        preserveState: true,
      });
};

export const searchFromSearchString = (searchString: string) => {
    const parsedSearchState = Object.fromEntries(
        new URLSearchParams(searchString)
    )

    return Object.entries(parsedSearchState).reduce((searchState, [key, value]) => {
        const regexString = /filter\[([^)]+)\]/
        const filterMatches = key.match(regexString)

        const regexCString = /custom\[([^)]+)\]/
        const cFilterMatches = key.match(regexCString)

        const localSearchState: Record<string, any> = { ...searchState };
        if (filterMatches) {
            localSearchState.filters = {
                ...(localSearchState.filters ?? {}),
                [filterMatches[1]]: value
            }
        } else if (cFilterMatches) {
            localSearchState.custom = {
                ...(localSearchState.custom ?? {}),
                [cFilterMatches[1]]: value
            }
        } else {
            localSearchState[key] = value
        }

        return localSearchState;
    }, {});
}

const searchState = reactive<ISearchState>({
  filters: {},
  custom: {},
  dates: {
    startDate: new Date(),
    endDate: null
  },
  limit: 25,
  page: 1,
  relationships: "",
  sorts: "",
  search: ""

});

const setSearchState = (serverSearchData: Record<string, any>, dates: any) => {
    const state: Record<string, any> = {}
    state.filters = {
      ...(serverSearchData ? serverSearchData?.filters : {}),
      date: null
    };

    console.log(serverSearchData, state.filters)

    state.custom = {
      ...(serverSearchData ? serverSearchData?.custom : {}),
    };

    state.dates = {
      startDate: dates.startDate,
      endDate: dates.endDate,
    };

    state.sorts = serverSearchData?.sorts ?? "";
    state.limit = serverSearchData?.limit ?? 25;
    state.relationships = serverSearchData?.relationships ?? "";
    state.search = serverSearchData?.search ?? "";
    state.page = serverSearchData?.page ?? 1

    Object.assign(searchState, state)
}


export const useServerSearch = (
  _serverSearchData: Ref<Partial<IServerSearchData>>,
  options: IServerSearchOptions = {},
  onUrlChange?: UrlChangeCallback,
  router?: InertiaRouterType
) => {
    const serverSearchData = ref(searchFromSearchString(location.search));
    const dates = parseDateFilters(serverSearchData, options.defaultDates)
    const isLoaded = ref(false)
    const preventWatch = ref(true);

    setSearchState(serverSearchData.value, dates);
    const localRouter = inject(
      "router",
      // eslint-disable-next-line no-empty-pattern
      router ?? {get: (params, {}, {}) => {
        throw new Error(`provide an router with ${params}`)
      },
    }
    );

    const localUrlChange = onUrlChange ?? defaultSearchInertia.bind(null, localRouter);

    const updateSearch = (urlParams: string) => {
        const finalUrl = `${window.location.pathname}?${urlParams}`;
        return localRouter.get(finalUrl, undefined, {
          preserveState: true,
          preserveScroll: true,
        });
    };

    localUrlChange(parseParams(searchState), updateSearch).then(() => {
        setTimeout(() => {
            isLoaded.value = true
        }, 3000)
    });

  const executeSearch = (delay?: number) => {
    nextTick(() => {
      if (!isLoaded.value) return
      const urlParams = parseParams(searchState);
      const currentUrl = getCurrentLocationParams();
      if (urlParams == currentUrl || !localUrlChange) return;

      if (!delay) {
        localUrlChange(urlParams, updateSearch);
      } else {
        nextTick(
          debounce(() => {
            const urlParams = parseParams(searchState);
            window.history.pushState(
              {},
              null,
              `${location.pathname}?${urlParams}`
            );
            localUrlChange(urlParams, updateSearch);
          }, delay)
        );
      }
    });
  };

  preventWatch.value = false


  watch(() => searchState,
    debounce((newValue: any, oldValue: any) => {
      if (isLoaded.value && !preventWatch.value) {
        executeSearch();
      }
    }, 200),
    { deep: true }
  );

  const reset = () => {
    searchState.search = "";
    searchState.filters = {};
    searchState.custom = {};
    searchState.sorts = "";

    executeSearch();
  };

  const paginate = (page: number) => {
    searchState.page = page;
    executeSearch();
  };

  const changeSize = (limit: number) => {
    searchState.limit = limit;
    executeSearch();
  };

  const hasFilters = computed(() => {
    return Boolean(searchState.search?.length);
  });

  const toggleCustomFilter = (field: string, value: string, mode = SearchFilterMode.Replace) => {
    if (mode == SearchFilterMode.Replace) {
      searchState.custom = {
        [field]: value
      }
      executeSearch();
      return
    }

    if (searchState.custom[field] == value) {
      searchState.custom[field] = null
    } else {
      searchState.custom[field] = value
    }
    executeSearch();
  }

  const toggleFilter = () => {

  }

  return {
    state: searchState,
    hasFilters,
    toggleFilter,
    toggleCustomFilter,
    executeSearch,
    updateSearch,
    changeSize,
    paginate,
    reset,
    executeSearchWithDelay: (delay = 200) => executeSearch(delay),
  };
};
