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
    class="hidden md:fixed top-0 right-0 z-50 md:flex h-screen overflow-hidden transition-all ease-linear border-t border-l border-secondary"
    :class="{ 'border-t border-l rounded-tl-lg': applicationStore.selectedSection }"
  >
  <Transition name="slide">
    <keep-alive>
      <article
        class="container px-4 py-4 duration-75 bg-white border-t border-l rounded-tl-lg mt-[60px] w-96"
        v-if="applicationStore.selectedSection?.name"
      >
        <header class="flex items-center">
          <button
            class="flex items-center h-full mr-2 text-lg font-bold"
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
    <section class="flex flex-col widget-main-menu bg-base-lvl-3">
      <section class="w-[64px] h-[60px] bg-primary flex items-center justify-center">

      </section>
      <section class="flex flex-col">
        <button
          class="py-2 transition-colors"
          v-for="section in topSections"
          :class="{ 'hover:bg-black/20': !section.disabled }"
          :disabled="section.disabled"
          @click="onSetSelectSection(section)"
          :title="section.title"
        >
          <span
            class="inline-flex items-center justify-center w-10 py-2 mx-auto rounded-md text-secondary"
            v-if="section.icon"
            :class="{
              'bg-primary-light': applicationStore.selectedSection && applicationStore.selectedSection.name == section.name,
            }"
          >
            <component :is="section.icon" class="h-[18px]" />
          </span>
          <span v-else>
            {{ section.label }}
          </span>
        </button>
      </section>

      <section class="flex flex-col">
        <button
          class="py-2 transition-colors text-secondary"
          :class="{ 'hover:bg-black/20': !section.disabled }"
          v-for="section in bottomSections"
          :disabled="section.disabled"
          @click="onSetSelectSection(section)"
        >
          <span
            class="inline-flex justify-center w-10 mx-auto rounded-md text-secondary hola"
            v-if="section.icon"
            :class="{
              'bg-primary-light': applicationStore.selectedSection && applicationStore.selectedSection.name == section.name,
            }"
          >
            <component :is="section.icon" class="text-secondary" />
          </span>
          <span v-else>
            {{ section.label }}
          </span>
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
