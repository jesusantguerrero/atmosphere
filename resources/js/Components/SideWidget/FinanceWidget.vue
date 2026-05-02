<script setup lang="ts">
import {
  onMounted,
  shallowRef,
  computed,
} from "vue";

import ToolsAccountsWidget from "./ToolsAccountsWidget.vue";
import { THEME_FINI } from "@/utils/constants";
import IconClose from "../icons/IconClose.vue";
import { setTheme } from "@/composables/useTheme";
import { useApplicationStore, type AssistantSection } from "@/store/application.store";
import { useI18n } from "vue-i18n";
import ToolsCreditCardWidget from "./ToolsCreditCardWidget.vue";
import ToolsWatchlistWidget from "./ToolsWatchlistWidget.vue";
import ToolsListWidget from "./ToolsListWidget.vue";
import OouiWatchlistLtr from '~icons/ooui/watchlist-ltr';
import MdiWallet from '~icons/mdi/wallet';
import MdiCreditCard from '~icons/mdi/credit-card';
import MdiPiggyBank from '~icons/mdi/piggy-bank';
import HugeiconsShoppingBasketAdd03 from '~icons/hugeicons/shopping-basket-add-03'


defineOptions({
  name: "AssistantWidget",
});


const emit = defineEmits(['update:is-expanded'])

const applicationStore = useApplicationStore();

const props = defineProps<{
  isExpanded: boolean;
  showAssistantButton?: boolean;
  accounts: Record<string, any>[];
}>();

const { t } = useI18n();

const sections = computed<AssistantSection[]>(() => ([
  {
    name: "text-tools",
    label: "Aa",
    title: t("creditCardTools.title"),
    container: "top",
    component: shallowRef(ToolsCreditCardWidget),
    icon: MdiCreditCard,
    hideMargin: true,
  },
  {
    name: "Assistant",
    label: "bot",
    title: t("accountTools.title"),
    container: "top",
    component: shallowRef(ToolsAccountsWidget),
    icon: MdiWallet,
    hideMargin: true
  },
  {
    name: "Watchlist",
    label: "bot",
    title: t("watchlistTools.title"),
    container: "top",
    component: shallowRef(ToolsWatchlistWidget),
    icon: OouiWatchlistLtr,
    hideMargin: true
  },
  {
    name: "Watchlist",
    label: "bot",
    title: t("watchlistTools.title"),
    container: "top",
    component: shallowRef(ToolsWatchlistWidget),
    icon: MdiPiggyBank,
    hideMargin: true
  },
  {
    name: "text-tools",
    label: "Aa",
    title: t("listTools.title"),
    container: "top",
    component: shallowRef(ToolsListWidget),
    icon: HugeiconsShoppingBasketAdd03,
    hideMargin: true,
  },
]));

const containerSections = (container: "top" | "bottom") => {
  return sections.value.filter((section) => section.container == container);
};
const topSections = computed(() => {
  return containerSections("top");
});

const bottomSections = computed(() => {
  return containerSections("bottom");
});

onMounted(() => {
    document.querySelector('.nixps-SplitView')?.classList.add('custom-split')
    setTheme(THEME_FINI)
})


const onSetSelectSection = async (newSection?: string|null) => {
  applicationStore.selectedSection = newSection;

  if (newSection) {
    //   startReplacer()
    emit('update:is-expanded', true)
} else {
    applicationStore.onCloseWidget()
    emit('update:is-expanded', false)
    }
}

onMounted(() => {
    if (props.isExpanded) {
        onSetSelectSection(sections.value[0]);
    }
})
</script>

<template>
  <main
    class="hidden md:fixed top-0 right-0 z-50 md:flex h-screen overflow-hidden transition-all ease-linear"
    :class="{ 'rounded-tl-lg': applicationStore.selectedSection }"
  >
  <Transition name="slide">
    <keep-alive>
      <article
        class="container px-4 py-4 duration-75 bg-white border-l border-base shadow-xl rounded-tl-lg mt-[60px] w-96"
        v-if="applicationStore.selectedSection?.name"
      >
        <header class="flex items-center">
          <button
            class="flex items-center h-full mr-2 text-lg font-bold text-body-1/60 hover:text-body-1 transition"
            @click="onSetSelectSection(null)"
          >
            <IconClose />
          </button>
          <h4 class="font-bold text-primary">{{ applicationStore.selectedSection.title }}</h4>
        </header>
        <keep-alive>
          <component
            :class="!applicationStore.selectedSection.hideMargin && 'mt-11'"
            :is="applicationStore.selectedSection.component"
            :accounts="accounts"
            :auto="true"
          />
        </keep-alive>
      </article>
    </keep-alive>
  </Transition>
    <section class="flex flex-col widget-main-menu bg-base-lvl-3 border-l border-base pt-[60px]">
      <section class="flex flex-col gap-1 px-2 py-3">
        <button
          v-for="section in topSections"
          :key="section.name + section.title"
          class="group relative flex items-center justify-center h-10 w-10 mx-auto rounded-lg transition-all duration-150"
          :class="[
            applicationStore.selectedSection?.name === section.name
              ? 'bg-primary/10 text-primary'
              : 'text-body-1/60 hover:bg-base-lvl-2 hover:text-primary',
            { 'opacity-40 cursor-not-allowed': section.disabled }
          ]"
          :disabled="section.disabled"
          @click="onSetSelectSection(section)"
          :title="section.title"
          :aria-label="section.title"
          :aria-pressed="applicationStore.selectedSection?.name === section.name"
        >
          <span
            v-if="applicationStore.selectedSection?.name === section.name"
            class="absolute -left-2 top-1/2 -translate-y-1/2 h-5 w-1 rounded-r-full bg-primary"
            aria-hidden="true"
          />
          <component v-if="section.icon" :is="section.icon" class="h-[18px] w-[18px]" />
          <span v-else class="text-xs font-semibold">{{ section.label }}</span>
        </button>
      </section>

      <section v-if="bottomSections.length" class="mt-auto flex flex-col gap-1 px-2 py-3 border-t border-base">
        <button
          v-for="section in bottomSections"
          :key="section.name + section.title"
          class="group relative flex items-center justify-center h-10 w-10 mx-auto rounded-lg transition-all duration-150"
          :class="[
            applicationStore.selectedSection?.name === section.name
              ? 'bg-primary/10 text-primary'
              : 'text-body-1/60 hover:bg-base-lvl-2 hover:text-primary',
            { 'opacity-40 cursor-not-allowed': section.disabled }
          ]"
          :disabled="section.disabled"
          @click="onSetSelectSection(section)"
          :title="section.title"
          :aria-label="section.title"
          :aria-pressed="applicationStore.selectedSection?.name === section.name"
        >
          <span
            v-if="applicationStore.selectedSection?.name === section.name"
            class="absolute -left-2 top-1/2 -translate-y-1/2 h-5 w-1 rounded-r-full bg-primary"
            aria-hidden="true"
          />
          <component v-if="section.icon" :is="section.icon" class="h-[18px] w-[18px]" />
          <span v-else class="text-xs font-semibold">{{ section.label }}</span>
        </button>
      </section>
    </section>
  </main>
</template>


<style lang="scss">
#lacia-custom-container {
  padding-right: 448px;
}

.custom-split {
  padding-right: 64px;
}
</style>
