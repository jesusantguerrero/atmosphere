import { ref } from "vue";

export const useTabs = (options: any[], defaultValue = "general") => {
  const tabs = ref(options);
  const selectedTab = ref(defaultValue);

  const isTab = (tabName: string) => {
    return selectedTab.value == tabName
  }

  return {
    selectedTab,
    tabs,
    isTab
  }
}
