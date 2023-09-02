import { computed } from "vue";
import { useBreakpoints, breakpointsTailwind } from "@vueuse/core";

export const useResponsive = () => {
  const bp = useBreakpoints(breakpointsTailwind);

  const isMobile = computed(() => {
    return bp.isSmaller("md");
  });

  return {
    isMobile
  }
}
