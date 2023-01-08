import { defineStore } from "pinia";
import { getIsMobile } from "@/utils";
import { useFullscreen, useWindowSize } from "@vueuse/core"
import { ref, watch } from "vue";

export const useAppContextStore = defineStore('context', () => {
    const isMobile = ref(getIsMobile())
    const { width } = useWindowSize()

    const isMoreOptionsModalOpen = ref(false)
    const toggleOptionsModal = (isOpen = null) => {
        const state = isOpen ?? !isMoreOptionsModalOpen.value
        isMoreOptionsModalOpen.value = state
    }

    watch(width, () => {
        isMobile.value = getIsMobile()
    })


    const { isFullscreen, enter, exit, toggle } = useFullscreen()
    watch(isMobile, (isMobileSize) => {
        if (!isMobileSize) {
            enter()
        } else {
            exit()
        }
    })
    return {
        isMobile,
        // Modals
        isMoreOptionsModalOpen,
        toggleOptionsModal,
        // full screen
        toggleFullscreen: toggle,
        enterFullscreen: enter,
        exitFullscreen: exit,
        isFullscreen
    }
})
