// import { format, parseISO } from "date-fns";
import format from "date-fns/format";
import parseISO from "date-fns/parseISO";
import { reactive, Ref, watch, nextTick, computed, ref, inject, toRaw, onMounted } from "vue";
import debounce from "lodash/debounce";

export interface IServerSearchData {
  filters: Record<string, string>;
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
}

interface IDateSpan {
  startDate: Date | null;
  endDate?: Date | null;
}

interface ISearchState {
  filters: Record<string, string | null>;
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

export const getGroupParams = (groupValue: string) => {
  return `group=${groupValue}`;
};

export const getRelationshipsParams = (relationships: string) => {
  return relationships && `relationships=${relationships}`;
};
export const getPaginationParams = (state: ISearchState) => {
  return state.page && `page=${state.page}&limit=${state.limit}`;
};

export const parseParams = (state: SearchState) => {
  const params = [
    filterParams("date", state.filters, state.dates),
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

function parseDateFilters(options: Ref<Partial<IServerSearchData>>) {
  const dates = options?.value?.filters?.date
    ? options.value.filters.date.split("~")
    : [null, null];
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
        const matches = key.match(regexString)
        if (matches) {
            searchState.filters = {
                ...(searchState.filters ?? {}),
                [matches[1]]: value
            }
        } else {
            searchState[key] = value
        }
        return searchState;
    }, {});
}

const setSearchState = (serverSearchData: Record<string, any>, dates: any) => {
    return {
        filters: {
            ...(serverSearchData.value ? serverSearchData.value?.filters : {}),
            date: null
        },
        dates:{
            startDate: dates.startDate,
            endDate: dates.endDate,
        },
        sorts:serverSearchData.value?.sorts ?? "",
        limit: serverSearchData.value?.limit ?? 0,
        relationships: serverSearchData.value?.relationships ?? "",
        search: serverSearchData.value?.search,
        page: serverSearchData.value?.page
    }
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
    const state = reactive<ISearchState>(setSearchState(serverSearchData.value, dates));
    const localRouter = inject(
        "router",
        // eslint-disable-next-line no-empty-pattern
        router ?? {get: (params, {}, {}) => {
            throw new Error(`provide an router with ${params}`)
            return;
        },
        }
    );

    const localUrlChange = onUrlChange ?? defaultSearchInertia.bind(null, localRouter);

    const updateSearch = (urlParams: string) => {
        const finalUrl = `${window.location.pathname}?${urlParams}`;
        return localRouter.get(finalUrl, undefined, {
        preserveState: true,
        });
    };

    localUrlChange(parseParams(state), updateSearch).then(() => {
        nextTick(() => {
            isLoaded.value = true
        })
    });

  const executeSearch = (delay?: number) => {
    nextTick(() => {
        if (!isLoaded.value)
        return
      const urlParams = parseParams(state);
      const currentUrl = getCurrentLocationParams();
      if (urlParams == currentUrl || !localUrlChange) return;

      if (!delay) {
        localUrlChange(urlParams, updateSearch);
      } else {
        nextTick(
          debounce(() => {
            const urlParams = parseParams(state);
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


  const setUrl = (urlParams: string) => {
    window.history.pushState(
        {},
        null,
        `${location.pathname}?${urlParams}`
      );
  }

  watch(() => state,
    debounce((paramsConfig) => {
       executeSearch();
    }, 200),
    { deep: true }
  );

  const reset = () => {
    state.search = "";
    state.filters = {};
    state.sorts = "";

    executeSearch();
  };

  const paginate = (page: number) => {
    state.page = page;
    executeSearch();
  };

  const changeSize = (limit: number) => {
    state.limit = limit;
    executeSearch();
  };

  const hasFilters = computed(() => {
    return Boolean(state.search?.length);
  });

  return {
    state,
    hasFilters,
    executeSearch,
    updateSearch,
    changeSize,
    paginate,
    reset,
    executeSearchWithDelay: (delay = 200) => executeSearch(delay),
  };
};
