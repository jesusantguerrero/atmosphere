import { defineStore } from "pinia";
import { getIsMobile } from "@/utils";
import { useFullscreen, useWindowSize } from "@vueuse/core"
import { ref, watch } from "vue";

export const useAppContextStore = defineStore('context', () => {
    const isMobile = ref(getIsMobile())
    const { width } = useWindowSize()

    watch(width, () => {
        isMobile.value = getIsMobile()
    })


    const { isFullscreen, enter, exit, toggle } = useFullscreen()
    watch(isMobile, (isMobileSize) => {
        if (!isMobileSize) {
            console.log('aqui estamos')
            enter()
        } else {
            exit()
        }
    })
    return {
        isMobile,
        // full screen
        toggleFullscreen: toggle,
        enterFullscreen: enter,
        exitFullscreen: exit,
        isFullscreen
    }
})
