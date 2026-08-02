<script setup lang="ts">
import {
  onMounted,
  onBeforeUnmount,
  ref,
  shallowRef,
  computed,
  watch,
} from "vue";
import { usePage } from "@inertiajs/vue3";
import ToolsAccountsWidget from "./ToolsAccountsWidget.vue";
import { THEME_FINI } from "@/utils/constants";
import { setTheme } from "@/composables/useTheme";
import { useApplicationStore, type AssistantSection } from "@/store/application.store";
import { useI18n } from "vue-i18n";
import ToolsCreditCardWidget from "./ToolsCreditCardWidget.vue";
import ToolsWatchlistWidget from "./ToolsWatchlistWidget.vue";
import ToolsShoppingListWidget from "./ToolsShoppingListWidget.vue";
import ToolsBudgetWidget from "./ToolsBudgetWidget.vue";
import OouiWatchlistLtr from '~icons/ooui/watchlist-ltr';
import MdiWallet from '~icons/mdi/wallet';
import MdiCreditCard from '~icons/mdi/credit-card';
import MdiPiggyBank from '~icons/mdi/piggy-bank';
import MdiClose from '~icons/mdi/close';
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

// Cross-pillar quick-access widgets in the right rail. Things you reach for
// constantly but that live inside a pillar — surfaced here so they're one click
// from any page. (Order roughly matches reach frequency.)
const sections = computed<AssistantSection[]>(() => ([
  {
    name: "budget",
    label: "$",
    title: t("budgetTools.title"),
    container: "top",
    component: shallowRef(ToolsBudgetWidget),
    icon: MdiPiggyBank,
    hideMargin: true,
  },
  {
    name: "credit-cards",
    label: "Aa",
    title: t("creditCardTools.title"),
    container: "top",
    component: shallowRef(ToolsCreditCardWidget),
    icon: MdiCreditCard,
    hideMargin: true,
  },
  {
    name: "accounts",
    label: "bot",
    title: t("accountTools.title"),
    container: "top",
    component: shallowRef(ToolsAccountsWidget),
    icon: MdiWallet,
    hideMargin: true
  },
  {
    name: "watchlist",
    label: "bot",
    title: t("watchlistTools.title"),
    container: "top",
    component: shallowRef(ToolsWatchlistWidget),
    icon: OouiWatchlistLtr,
    hideMargin: true
  },
  {
    name: "shopping-list",
    label: "shop",
    title: t("listTools.title"),
    container: "top",
    component: shallowRef(ToolsShoppingListWidget),
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


const closePanel = () => {
  applicationStore.selectedSection = null;
  applicationStore.onCloseWidget();
  emit('update:is-expanded', false);
};

const onSetSelectSection = async (newSection?: AssistantSection | null, index?: number) => {
  const isSameSection = newSection
    && applicationStore.selectedSection
    && applicationStore.selectedSection.name === newSection.name
    && applicationStore.selectedSection.title === newSection.title;

  if (!newSection || isSameSection) {
    closePanel();
    return;
  }

  applicationStore.selectedSection = { ...newSection, _index: index } as AssistantSection;
  emit('update:is-expanded', true);
};

const isActiveSection = (section: AssistantSection, index: number) => {
  const selected = applicationStore.selectedSection as (AssistantSection & { _index?: number }) | null;
  if (!selected) return false;
  if (selected._index !== undefined) return selected._index === index;
  return selected.name === section.name && selected.title === section.title;
};

// Docked panel, not a popover: it stays open while working on the page.
// Close is explicit — X button, Escape, or toggling the rail icon.
const panelRef = ref<HTMLElement | null>(null);

const onKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && applicationStore.selectedSection) {
    closePanel();
  }
};

// Deep-link support: navigating to any page with ?panel=<name> auto-opens that
// right-rail panel. Powers the onboarding wizard ("Add accounts" → the Accounts
// panel opens for you) instead of dropping the user on the page to hunt for it.
// A URL watch (not just onMounted) is required because AppLayout — and this
// widget — persist across Inertia navigations, so onMounted fires only once.
const page = usePage();
watch(
  () => page.url,
  (url) => {
    const query = (url || '').split('?')[1] || '';
    const panel = new URLSearchParams(query).get('panel');
    if (!panel) return;
    const idx = sections.value.findIndex((sec) => sec.name === panel);
    if (idx >= 0 && applicationStore.selectedSection?.name !== panel) {
      onSetSelectSection(sections.value[idx], idx);
    }
  },
  { immediate: true }
);

onMounted(() => {
  document.addEventListener('keydown', onKeydown);
  if (props.isExpanded) {
    onSetSelectSection(sections.value[0], 0);
  }
});

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
  <main
    ref="panelRef"
    class="hidden md:fixed top-0 right-0 z-50 md:flex h-screen overflow-hidden transition-all ease-linear"
    :class="{ 'rounded-tl-lg': applicationStore.selectedSection }"
  >
  <Transition name="slide">
      <article
        class="container flex flex-col h-[calc(100vh-60px)] px-4 py-4 duration-75 bg-base-lvl-3 border-l border-base shadow-xl rounded-tl-lg mt-[60px] w-96"
        v-if="applicationStore.selectedSection?.name"
      >
        <header class="flex items-center justify-between border-b border-base pb-3 mb-3 shrink-0">
          <h4 class="font-bold text-primary">{{ applicationStore.selectedSection.title }}</h4>
          <button
            type="button"
            class="inline-flex items-center justify-center h-8 w-8 rounded-md text-body-1/60 hover:bg-base-lvl-2 hover:text-body-1 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
            :aria-label="$t('Close')"
            :title="$t('Close')"
            @click="closePanel"
          >
            <MdiClose class="h-5 w-5" />
          </button>
        </header>
        <keep-alive>
          <component
            :class="[
              !applicationStore.selectedSection.hideMargin && 'mt-11',
              'flex-1 min-h-0 flex flex-col'
            ]"
            :is="applicationStore.selectedSection.component"
            :accounts="accounts"
            :auto="true"
          />
        </keep-alive>
      </article>
  </Transition>
    <!--
      Quick-access rail. Each button stacks an icon + a tiny text label so a
      first-time user can tell what each panel does without hovering (which
      previously required waiting ~1.5s for the native title tooltip).
      The rail is deliberately kept slim (~76px) — labels are 10px so they
      don't shout, but they're visible in a glance.
    -->
    <section class="flex flex-col widget-main-menu bg-base-lvl-3 border-l border-base pt-[60px] w-[76px]">
      <section class="flex flex-col gap-1 px-1 py-3">
        <button
          v-for="(section, index) in topSections"
          :key="`top-${index}`"
          class="group relative flex flex-col items-center justify-center gap-1 py-2 mx-auto w-full rounded-lg transition-all duration-150"
          :class="[
            isActiveSection(section, index)
              ? 'bg-primary/10 text-primary'
              : 'text-body-1/60 hover:bg-base-lvl-2 hover:text-primary',
            { 'opacity-40 cursor-not-allowed': section.disabled }
          ]"
          :disabled="section.disabled"
          @click="onSetSelectSection(section, index)"
          :title="section.title"
          :aria-label="section.title"
          :aria-pressed="isActiveSection(section, index)"
        >
          <span
            v-if="isActiveSection(section, index)"
            class="absolute -left-1 top-1/2 -translate-y-1/2 h-5 w-1 rounded-r-full bg-primary"
            aria-hidden="true"
          />
          <component v-if="section.icon" :is="section.icon" class="h-[18px] w-[18px]" />
          <span v-else class="text-xs font-semibold">{{ section.label }}</span>
          <span class="text-[10px] leading-tight font-medium text-center px-1 line-clamp-2">
            {{ section.title }}
          </span>
        </button>
      </section>

      <section v-if="bottomSections.length" class="mt-auto flex flex-col gap-1 px-1 py-3 border-t border-base">
        <button
          v-for="(section, index) in bottomSections"
          :key="`bottom-${index}`"
          class="group relative flex flex-col items-center justify-center gap-1 py-2 mx-auto w-full rounded-lg transition-all duration-150"
          :class="[
            isActiveSection(section, topSections.length + index)
              ? 'bg-primary/10 text-primary'
              : 'text-body-1/60 hover:bg-base-lvl-2 hover:text-primary',
            { 'opacity-40 cursor-not-allowed': section.disabled }
          ]"
          :disabled="section.disabled"
          @click="onSetSelectSection(section, topSections.length + index)"
          :title="section.title"
          :aria-label="section.title"
          :aria-pressed="isActiveSection(section, topSections.length + index)"
        >
          <span
            v-if="isActiveSection(section, topSections.length + index)"
            class="absolute -left-1 top-1/2 -translate-y-1/2 h-5 w-1 rounded-r-full bg-primary"
            aria-hidden="true"
          />
          <component v-if="section.icon" :is="section.icon" class="h-[18px] w-[18px]" />
          <span v-else class="text-xs font-semibold">{{ section.label }}</span>
          <span class="text-[10px] leading-tight font-medium text-center px-1 line-clamp-2">
            {{ section.title }}
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
  padding-right: 76px;
}
</style>
