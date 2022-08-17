import autoAnimate from "@formkit/auto-animate";
import { computed, onMounted, ref } from "vue";


export const useCollapse = (element, collapsed = false) => {

    const isCollapsed = ref(collapsed);
    const toggleCollapse = () => {
        isCollapsed.value = !isCollapsed.value
    }

    const icon = computed(() => {
        return isCollapsed.value ? 'fas fa-angle-down' : 'fas fa-angle-up'
    })

    onMounted(() => {
        autoAnimate(element.value)
    })

    return {
        isCollapsed,
        toggleCollapse,
        icon
    }
}
