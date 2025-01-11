<script setup lang="ts">
import { computed, inject, onUnmounted, reactive, ref, watch } from 'vue';
import IconCheck from "@/components/icons/IconCheck.vue";
import IconLoading from "@/components/icons/IconLoading.vue";
import IconChange from "@/components/icons/IconChange.vue";
import { useModal } from 'vue-final-modal';
import FileSelection from './FileSelection.vue';
import { useI18n } from 'vue-i18n';
import { getToken } from '@/composables/useToken';
import { AssistantTools, useApplicationStore, type HighlightRecord } from '@/stores/application.store';
import { useFileComparisonStore } from '@/stores/fileComparison.store';
import IconClose from '../icons/IconClose.vue';
import IconRefresh from '../icons/IconRefresh.vue';
import FileDiff from './FileDiff.vue';

export interface Coord {x: number; y: number};

const emit = defineEmits({
    addComment(payload: { coords: Coord, value: string }): void {},
    addComments(payload: { coords: Coord, value: string }[]): void {},
    loaded(payload: HighlightRecord[]): void {},
    close(): void {},
});

const applicationStore = useApplicationStore();

const file = inject("file", ref(null));
const endpoint = inject("endpoint", ref("/"));

const state = useFileComparisonStore();
const props = withDefaults(defineProps<{
    processing?: boolean;
    auto?: boolean;
    defaultFile?: {
        asset_file_name: string;
        asset_file_alias: string;
        asset_file_thumbnail: string;
    };
    externalResults?: any[];
    isExpanded?: boolean;
}>(), {
    externalResults: () => ([])
});

const isLoading = ref(props.processing);


watch(() => props.processing, (value) => {
    isLoading.value = value;
}, { immediate: true })

watch(() => state.processing, (value) => {
    isLoading.value = value;
}, { immediate: true })


watch(() => props.defaultFile, () => {
    state.fileToCompare = props.defaultFile ?? null
})

const results = ref<any[]>([]);
watch(() => ([props.externalResults, state.lastResponse]), ([external, faultResponse]) => {
    results.value = [
        ...(external ? external : []),
        ...(faultResponse ? faultResponse : []),
    ];
}, { immediate: true });



const closeOption = () => {
    // state.reset();
    // emit('close');
    state.clearFileToCompare()
    
}

const assetUrl = inject('cfUrl', ref(""));
const hostname = inject('hostname', ref(""));

const extractAssetCode = (str: string): string => {
  const parts = str.split('/');
  return parts.length > 3 ? parts[4] : '';
}
const fetchAssetFiles = () => {
  if (!file.value || !assetUrl.value) return;
  const token = getToken();

  let assetInternalCode = decodeURIComponent(assetUrl.value)
  assetInternalCode = extractAssetCode(assetInternalCode);

  const requestOptions = {
    headers: {
        'Authorization': `Bearer ${token}`,
    }
  };

  state.isFetchingFiles = true;

  return fetch(`${endpoint.value}/core/assets/${assetInternalCode}/files?hostname=${hostname.value}`, requestOptions)
    .then(async (response) => {
      if (!response.ok) {
       console.log(await response.json());
      } 
      return response.json();
    })
    .then((result) => {
      state.assetFiles = result.fileAssetsVersionTags
      state.asset = result.show
    })
    .finally(() => {
        state.isFetchingFiles = false;
    })
};


const getAssetFiles = computed(() => {
    return state.assetFiles;
}) 

const getAsset = computed(() => {
    return state.asset;
})

const isFetchingFiles = computed<boolean>(() => {
    return state.isFetchingFiles;
}) 

const { t } = useI18n()


const emitData = (data: any) => {
    const hl = data.reduce((files, file) => {
        if (file.explanation) {
            const document = file.documents?.find(file => file.document !== fileName.value)
            files.push({
                text: document.text
            })
        }
        return files; 
    }, [])
    emit('loaded',hl)
}

const submit = () => {
    state.submitFiles(file.value, {}, endpoint.value, applicationStore.fileAlias)
    .then(emitData)
}

const { open, close } = useModal({
    component: FileSelection,
    attrs: {
      title: t('compareFilesModal.title'),
      files: getAssetFiles,
      //@ts-expect-error: computed
      asset: getAsset,
      //@ts-expect-error: computed
      processing: isFetchingFiles,
      async onConfirm(fileB) {
        close()
        const fileData = await useApplicationStore().fetchFile(`${fileB.asset_file_alias}${fileB.asset_file_extension}`, fileB.cloudflow_url);
        state.setFileToCompare(fileB, fileData)
        state.submitFiles(file.value, {}, endpoint.value, applicationStore.fileAlias)
        .then(emitData)
      },
      onClose() {
        close()
      }
    },
    slots: {
      default: '<p>UseModal: The content of the modal</p>',
    },
})

const openModal = async (forceOpen: boolean) => {
    if (!state.fileToCompare?.asset_file_name|| forceOpen) { 
        open()
    }
    
    if (!state.assetFiles.length) {
        await fetchAssetFiles()
    }
}

const currentDiffs = ref({});

const { open: openDiff, close: closeDiffModal } = useModal({
    component: FileDiff,
    attrs: {
      title: t('fileDiffsModal.title'),
      files: currentDiffs,
      //@ts-expect-error: computed
      asset: getAsset,
      //@ts-expect-error: computed
      processing: isFetchingFiles,
      async onConfirm(fileB) {
        closeDiffModal()
      },
      onClose() {
        closeDiffModal()
      }
    },
    slots: {
      default: '<p>UseModal: The content of the modal</p>',
    },
})

const openDiffsModal = (diffs: any) => {
    currentDiffs.value = diffs;
    openDiff();
}

defineExpose({
    openModal
})

const currentIcon = computed(() => {
    return state.processing ? IconLoading : IconCheck;
})

const currentText = computed(() => {
    return state.processing ? t("grammar.loadingText") : t("compareFilesModal.noErrors");
})


const validResults = computed(() => {
    return results.value.filter(fault => fault.explanation)
})
const hasResults = computed(() => {
    return validResults.value.length
})

const fileName = computed(() => {
    const name = state.fileToCompare?.asset_file_alias ?? state.fileToCompare?.asset_file_name
    return `${name}${state.fileToCompare?.asset_file_extension}`
}) 

const clampText = (text: string = "") => {
    return text.length > 50 ? text.slice(0, 50) + '...' : text;
}

const matches = computed(() => {
    return validResults.value.reduce((matches, occurrence) => {
        const matchType = occurrence.documents?.find(file => {
            return file.document == fileName.value
        })?.matchType ?? "";
        if (matchType === 'parcial') {
            matches.partials.push(occurrence)
        } else {
            matches.exact.push(occurrence)
        }
        return matches;
    }, {
        exact: [],
        partials: []
    })
}) 



const onClear = () => {
  state.clearFileToCompare();
  state.reset();
  emit('loaded', []);
} 
const unsubscribe = applicationStore.$onAction(({ name, store, args}) => {
  if (name == 'onCloseWidget') {
    onClear()
  }
  if (name == 'onWidgetToolChanged' && args[0] !== AssistantTools.FileComparison) {
    onClear()
  }
})

onUnmounted(() => unsubscribe())
</script>

<template>
    <section>
        <main v-if="state.file">
            <header>
                <section class="flex justify-between mt-2" v-if="state.fileToCompare">
                    <h4 class="font-semibold text-accent">
                        {{ t('compareFilesModal.comparedWith')}}:
                    </h4>
                    <section class="flex items-center space-x-2">
                        <button  @click.prevent.stop="openModal(true)">
                            <IconChange />
                        </button>
                        <button @click.prevent.stop="submit()" class="text-lg" v-if="results.length" :class="{'animate-spin': state.processing }">
                            <IconRefresh />
                        </button>
                    </section>
                </section>
                <section class="flex items-center justify-between w-full mt-1" v-if="state.fileToCompare">
                <section class="flex items-center" >
                    <img :src="state.fileToCompare?.asset_file_thumbnail" class="object-contain w-8 h-8" />
                    <span>
                        {{  fileName }}
                    </span>
                </section>
                    <button @click="closeOption" class="text-lg transition-colors ease-in hover:text-danger">
                        <IconClose />
                    </button>
                </section>
            </header>
            <div v-if="state.processing" class="flex flex-col items-center justify-center w-full mt-5 text-center rounded-md cursor-pointer py-11 px-11 bg-gray-light" >
                <component :is="currentIcon" :class="{'animate-spin': state.processing }" />
                <span class="font-semibold text-primary">
                  {{ currentText }}
                </span>
            </div>
        </main>
        <section v-if="state.isCalled && !state.processing && state.fileToCompare">
            <h4 class="flex justify-between mt-1 font-semibold text-gray-400" v-if="state.isCalled">
                <span>
                    {{ t('compareFilesModal.results', {
                        count: validResults.length,
                        docName: fileName
                    })}}
                </span>
            </h4>
            <div  class="items-center justify-center w-full py-2 mt-1 border-2 rounded-md cursor-pointer border-secondary" v-if="matches.exact.length">
                <h4 class="flex justify-between font-semibold text-gray-400">
                    <span>
                        {{ t('compareFilesModal.resultsFound', {
                            count: matches.exact.length,
                            docName: fileName
                        })}}
                    </span>
                </h4>
                <section class="mt-4" >
                    <div class="overflow-auto text-xs h-52">
                        <ul class="space-y-2">
                            <li 
                                v-for="(fault, index) in matches.exact" class="items-baseline" 
                                :key="`${fault.word} ${index}`"
                                @click="openDiffsModal(fault)"
                            >
                                <div class="font-bold text-primary hover:underline">
                                    <p>
                                        R{{index + 1}} - {{  t('exactCoincidence') }}:

                                        {{ clampText(fault.documents?.at(0).text) }}
                                    </p>                   
                                </div>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
            <div  class="items-center justify-center w-full py-2 mt-2 border-2 rounded-md cursor-pointer border-secondary" v-if="matches.partials.length">
                <h4 class="flex justify-between font-semibold text-gray-400">
                    <span>
                        {{ t('compareFilesModal.resultsFound', {
                            count: matches.partials.length,
                            docName: fileName
                        })}}
                    </span>
                </h4>
                <section class="mt-1" >
                    <div class="overflow-auto text-xs h-52">
                        <ul class="space-y-2">
                            <li v-for="(fault, index) in matches.partials" 
                                class="items-baseline" 
                                :key="`${fault.word} ${index}`"
                                @click="openDiffsModal(fault)"
                            >
                                <div class="relative inline-block font-semibold hover:underline text-primary group">
                                    <p>R{{index + 1}} - {{  t('partialCoincidence') }}: {{ clampText(fault.documents?.at(0).text) }}</p>
                        
                                </div>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
        </section>
    </section>
</template>