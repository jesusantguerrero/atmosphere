<script lang="ts" setup>
import { computed, inject, nextTick, onUnmounted, reactive, ref, watch } from "vue";
import IconSpinnerDots from "@/components/icons/IconSpinnerDots.vue";
import { useI18n } from "vue-i18n";
import IconSearch from "../icons/IconSearch.vue";
import IconClose from "../icons/IconClose.vue";
import { AssistantTools, useApplicationStore } from "@/store/application.store";

const emit = defineEmits(['loaded', 'addComment', 'addComments', 'close']);

const props = defineProps<{
  externalResults: any[];
  processing: boolean;
}>();

const text = ref("");

const file = inject("file", ref(null));
const endpoint = inject("endpoint", ref(null));

const SearchState = reactive<{
    processing: boolean;
    threadId?: string;
    fileId?: string;
    lastResponse: any[]|null;
    responseText: string;
    isCalled: boolean;
}>({
    isCalled: false,
    processing: props.processing,
    lastResponse: [],
    responseText: ''
});

const { t } = useI18n();

watch(() => ([props.externalResults]), ([external]) => {
    SearchState.lastResponse = [
        ...(external ? external : []),
    ];
}, { immediate: true });


watch(() => SearchState.lastResponse, (data) => {
  if (data.length) {
    nextTick(() => {
      document.querySelector('.highlight')?.scrollIntoView({
        behavior: 'smooth'
      });
    })
  }
})

const clearPreviousResponse = ( ) => {
  SearchState.isCalled = false;
  SearchState.lastResponse = [];
  emit('loaded', []);
}

const onClear = () => {
  text.value = "";
  clearPreviousResponse()
}
const onSubmit = () => {
  const formData = new FormData();
  if (!file.value || SearchState.processing || !text.value.trim().length) return;
  formData.append("file", file.value);
  formData.append("text", text.value);

  const hasFileId = SearchState.threadId && SearchState.fileId

  const requestOptions = {
    method: hasFileId ? "PUT" : "POST",
    body: formData,
  };

  const finalUrl = hasFileId
  ? `${endpoint.value}/assistant/search/${SearchState.fileId}/threads/${SearchState.threadId}`
  : `${endpoint.value}/assistant/search`

  SearchState.processing = true;
  clearPreviousResponse();

  fetch(finalUrl, requestOptions)
    .then(async (response) => {
      if (!response.ok) {
       console.log(await response.json());

      }
      return response.json();
    })
    .then((result) => {
      if (typeof result.data == 'string') {
        SearchState.lastResponse = [];
        SearchState.responseText = result.data;
        emit('loaded', []);
      } else {
        SearchState.lastResponse = result.data;
        SearchState.responseText = '';
        emit('loaded', result.data);
      }
      SearchState.isCalled = true;
      SearchState.fileId = result.fileId;
      SearchState.threadId = result.threadId;
      SearchState.processing = false;
    })
    .catch((error) => {
      SearchState.processing = false;
      console.error(error);
    });
};

const matches = computed(() => {
    return SearchState.lastResponse.reduce((matches, occurrence) => {
        if (occurrence.matchType === 'parcial') {
            matches.partial.push(occurrence)
        } else {
            matches.exact.push(occurrence)
        }
        return matches;
    }, {
        exact: [],
        partial: []
    })
})


const applicationStore = useApplicationStore();

const unsubscribe = applicationStore.$onAction(({ name, store, args}) => {
  if (name == 'onCloseWidget') {
    onClear()
  }
  if (name == 'onWidgetToolChanged' && args[0] !== AssistantTools.Search) {
    onClear()
  }
})
onUnmounted(() => unsubscribe())
</script>

<template>
  <article class="w-full py-2 mt-1 border rounded-md border-secondary">
    <section class="flex items-start border-2 rounded-md border-gray-light search-box">
      <input
        class="w-full px-2 mt-2 rounded-md resize-none focus:outline-none"
        rows="4"
        :placeholder="t('searchText.description')"
        v-model="text"
        @keypress.enter.prevent="onSubmit()"
      />
      <section class="flex items-center justify-end rounded-md">
        <button @click="onSubmit" :disabled="SearchState.processing" class="h-8 px-3 text-lg transition-colors hover:bg-gray-light/20">
          <span v-if="!SearchState.processing">
            <IconSearch />
          </span>
          <IconSpinnerDots class="text-secondary" v-else />
        </button>
        <button @click="onClear" class="items-center w-full h-8 px-3 text-lg transition-colors hover:bg-gray-light/20" :disabled="SearchState.processing" v-if="text.length">
          <span >
            <IconClose />
          </span>
        </button>
      </section>
    </section>
    <footer class="flex justify-between" v-if="SearchState.isCalled">
      <section class="text-gray-400">
        {{ SearchState.lastResponse?.length }} {{ t('commons.results')}}
      </section>
    </footer>
    <div class="space-y-2 overflow-auto max-h-52 h-min" v-if="!SearchState.responseText && SearchState.isCalled">
      <section v-for="(matchType, matchTypeName) in matches">
        <h4>
          {{ t(`searchText.${matchTypeName}ResultsFound`, {
            count: matchType.length,
        })}}

        </h4>
        <ul>
            <li
              v-for="(fault, index) in matchType" :key="`${fault.word} ${index}`"
              @click="$emit('addComment', fault)">
                <span class="font-bold text-primary">R{{index + 1}} - {{ t(matchTypeName) }}:</span>
                <span class="inline-block ml-1 font-semibold text-red-400 cursor-pointer">
                    {{ fault.text }}
                </span>
            </li>
        </ul>
      </section>
  </div>
  </article>
</template>

<style lang="scss">
.search-box {
  border: 2px solid  #D3D3D3;
}
</style>
